<?php

namespace App\Exports;

use App\Models\User;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\KalenderBlok;
use App\Models\LaporanHarian;
use App\Models\MasterHariKerja;
use Carbon\Carbon; // Import Carbon
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Import untuk format angka

class LaporanSesiExport implements WithEvents
{
    protected $bulan;
    protected $tahun;
    protected $namaBulan;
    protected $laporanData;

    public function __construct(int $bulan, int $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->namaBulan = Carbon::create()->month($bulan)->locale('id_ID')->isoFormat('MMMM');
        $this->laporanData = $this->getLaporanData();
    }

    /**
     * Logika utama untuk mengambil dan memproses data laporan.
     */
    private function getLaporanData()
    {
        // --- 1. PENGATURAN TANGGAL & DATA AWAL ---
        $awalBulan = Carbon::create($this->tahun, $this->bulan, 1)->startOfMonth();
        $akhirBulan = $awalBulan->clone()->endOfMonth();
        $today = now('Asia/Jakarta')->startOfDay();

        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($awalBulan, $akhirBulan) {
                $query->whereBetween('tanggal', [$awalBulan, $akhirBulan]);
            }])
            ->orderBy('name', 'asc')->get();

        $hariLibur = HariLibur::whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->pluck('tanggal')->map(fn ($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());

        $kalenderBlokBulanIni = KalenderBlok::where(function ($query) use ($awalBulan, $akhirBulan) {
            $query->where('tanggal_mulai', '<=', $akhirBulan)
                  ->where('tanggal_selesai', '>=', $awalBulan);
        })->get();

        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        $laporanPerSesi = collect();

        // --- 2. LOOPING PER GURU ---
        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0; $totalHadir = 0; $totalTepatWaktu = 0; $totalTerlambat = 0;
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
            $daysInMonth = $awalBulan->daysInMonth;

            // --- 3. LOOPING PER HARI ---
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = $awalBulan->clone()->addDays($i - 1);

                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                 if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }

                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) continue;

                // --- Logika Pencarian Tipe Blok ---
                $kalenderBlokHariIni = $kalenderBlokBulanIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu));

                // --- Logika Filter Jadwal (str_contains) ---
                $jadwalMentahHariIni = $jadwalHariGuru->get($namaHari);
                $jadwalUntukHariIni = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                })->sortBy('jam_ke');
                

                // --- Logika Pengelompokan Blok ---
                $tempBlock = null;
                $jadwalBlok = collect();
                foreach ($jadwalUntukHariIni as $jadwal) {
                    if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                        $tempBlock['jadwal_ids'][] = $jadwal->id;
                        $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                    } else {
                        if ($tempBlock) $jadwalBlok->push($tempBlock);
                        $tempBlock = [
                            'jadwal_ids' => [$jadwal->id],
                            'jam_pertama' => $jadwal->jam_ke,
                            'jam_terakhir' => $jadwal->jam_ke,
                            'kelas' => $jadwal->kelas
                        ];
                    }
                }
                if ($tempBlock) $jadwalBlok->push($tempBlock); 

                // ==========================================================
                // ## PERBAIKAN LOGIKA PERHITUNGAN ##
                // ==========================================================

                // 1. Total Sesi Wajib dihitung untuk SEMUA HARI (termasuk masa depan)
                $totalSesiWajib += $jadwalBlok->count();

                // 2. JANGAN LANJUTKAN jika tanggal ini ada di masa depan
                if ($tanggal->gt($today)) {
                    continue; 
                }

                // 3. Status (Hadir/Alpa/dll) HANYA dihitung untuk hari ini ke belakang
                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];
                    
                    $laporan = $guru->laporanHarian
                        ->where('jadwal_pelajaran_id', $jadwalPertamaId)
                        ->where('tanggal', $tanggal) 
                        ->first();

                    if ($laporan) {
                        switch ($laporan->status) {
                            case 'Hadir':
                                $totalHadir++;
                                if ($laporan->status_keterlambatan == 'Terlambat') {
                                    $totalTerlambat++;
                                } elseif ($laporan->status_keterlambatan == 'Tepat Waktu') {
                                    $totalTepatWaktu++;
                                }
                                break;
                            case 'Sakit': $totalSakit++; break;
                            case 'Izin': $totalIzin++; break;
                            case 'DL': $totalDL++; break;
                            default: $totalAlpa++; break;
                        }
                    } else {
                        $totalAlpa++;
                    }
                }
                // ==========================================================
            } // End looping per hari

            // --- 5. KALKULASI PERSENTASE (UNTUK EXCEL) ---
            $persentaseHadir = ($totalSesiWajib > 0) ? ($totalHadir / $totalSesiWajib) : 0;
            $persentaseTepatWaktu = ($totalHadir > 0) ? ($totalTepatWaktu / $totalHadir) : 0;

            $laporanPerSesi->push([
                $guru->name,
                $totalSesiWajib,
                $totalHadir,
                $totalTerlambat,
                $totalSakit,
                $totalIzin,
                $totalAlpa,
                $totalDL,
                $persentaseHadir,
                $persentaseTepatWaktu
            ]);
        } // End looping per guru

        return $laporanPerSesi;
    }

    public function registerEvents(): array
    {
        // ... (Logika styling 'registerEvents' tidak berubah sama sekali) ...
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- 1. MEMBUAT JUDUL ---
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI SESI - BULAN ' . strtoupper($this->namaBulan) . ' ' . $this->tahun);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // --- 2. MEMBUAT HEADER TABEL ---
                $headings = [
                    'Nama Guru', 'Total Jadwal', 'Hadir', 'Terlambat',
                    'Sakit', 'Izin', 'Alpa', 'Dinas Luar', '% Kehadiran', '% Ketepatan Waktu'
                ];
                $sheet->fromArray($headings, null, 'A3');
                $sheet->getStyle('A3:J3')->getFont()->setBold(true);
                $sheet->getStyle('A3:J3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDEDED');
                $sheet->getStyle('A3:J3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); 

                // --- 3. MENGISI DATA ---
                if ($this->laporanData->isNotEmpty()) {
                    $sheet->fromArray($this->laporanData->toArray(), null, 'A4');
                    $lastRow = count($this->laporanData) + 3;
                } else {
                    $lastRow = 3; 
                }

                // --- 4. STYLING ---
                if ($lastRow > 3) { 
                    $sheet->getStyle("A3:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("B4:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); 
                    $sheet->getStyle("A4:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); 
                    $sheet->getStyle("I4:J{$lastRow}")->getFont()->setBold(true);
                    
                    // Format persentase
                    $sheet->getStyle("I4:J{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                } else {
                     $sheet->getStyle("A3:J3")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
                
                $sheet->getColumnDimension('A')->setAutoSize(true);
                foreach (range('B', 'J') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}