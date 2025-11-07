<?php

namespace App\Exports;

use App\Models\User;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\KalenderBlok;
use App\Models\MasterHariKerja; 
use Carbon\Carbon; // Import Carbon
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Import NumberFormat

class LaporanSesiMingguanExport implements WithEvents
{
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $laporanData;

    public function __construct(string $tanggalMulai, string $tanggalSelesai)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->laporanData = $this->getLaporanData();
    }

    private function getLaporanData()
    {
        // --- 1. PENGATURAN TANGGAL & DATA AWAL ---
        $awalMinggu = Carbon::parse($this->tanggalMulai)->startOfDay();
        $akhirMinggu = Carbon::parse($this->tanggalSelesai)->startOfDay();
        $today = now('Asia/Jakarta')->startOfDay();
        $tanggalRange = $awalMinggu->locale('id_ID')->toPeriod($akhirMinggu);
        
        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($awalMinggu, $akhirMinggu) {
                $query->whereBetween('tanggal', [$awalMinggu, $akhirMinggu]);
            }])
            ->orderBy('name', 'asc')
            ->get();
        
        $hariLibur = HariLibur::whereBetween('tanggal', [$awalMinggu, $akhirMinggu])
            ->pluck('tanggal')
            ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());

        // --- OPTIMASI (N+1): Ambil data KalenderBlok 1x ---
        $kalenderBlokMingguIni = KalenderBlok::where(function ($query) use ($awalMinggu, $akhirMinggu) {
            $query->where('tanggal_mulai', '<=', $akhirMinggu)
                  ->where('tanggal_selesai', '>=', $awalMinggu);
        })->get();
        // --- Akhir Optimasi ---

        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        $laporanPerSesi = collect();

        // --- 2. LOOPING PER GURU ---
        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0; $totalHadir = 0; $totalTepatWaktu = 0; $totalTerlambat = 0;
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');

            // --- 3. LOOPING PER HARI ---
            foreach ($tanggalRange as $tanggal) {
                $tanggal = $tanggal->startOfDay(); // Pastikan start of day

                if ($tanggal->gt($today)) break;
                
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                  if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) continue;

                // --- OPTIMASI: Cari tipe minggu dari koleksi ---
                $kalenderBlokHariIni = $kalenderBlokMingguIni->firstWhere(function ($blok) use ($tanggal) {
                    // Gunakan perbandingan Carbon yang kuat
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                // --- Akhir Optimasi ---

                // ==========================================================
                // ## PERBAIKAN LOGIKA FILTER BLOK (str_contains) ##
                // ==========================================================
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu)); 
                $jadwalMentahHariIni = $jadwalHariGuru->get($namaHari);

                $jadwalUntukHariIni = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                })->sortBy('jam_ke');
                // ==========================================================
                
                // --- Logika Pengelompokan Blok ---
                $tempBlock = null;
                $jadwalBlok = collect();
                foreach ($jadwalUntukHariIni as $jadwal) {
                    if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                        $tempBlock['jadwal_ids'][] = $jadwal->id; $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                    } else {
                        if ($tempBlock) $jadwalBlok->push($tempBlock);
                        $tempBlock = ['jadwal_ids' => [$jadwal->id], 'jam_pertama' => $jadwal->jam_ke, 'jam_terakhir' => $jadwal->jam_ke, 'kelas' => $jadwal->kelas];
                    }
                }
                if ($tempBlock) $jadwalBlok->push($tempBlock);

                $totalSesiWajib += $jadwalBlok->count();

                // --- 4. LOOPING PER BLOK (CEK KEHADIRAN) ---
                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];
                    
                    // ==========================================================
                    // ## PERBAIKAN BUG KRITIS ADA DI SINI ##
                    // Tambahkan ->where('tanggal', $tanggal)
                    // ==========================================================
                    $laporan = $guru->laporanHarian
                        ->where('jadwal_pelajaran_id', $jadwalPertamaId)
                        ->where('tanggal', $tanggal) // Filter berdasarkan hari
                        ->first();
                        
                    if ($laporan) {
                        switch ($laporan->status) {
                            case 'Hadir':
                                $totalHadir++;
                                if ($laporan->status_keterlambatan == 'Tepat Waktu') $totalTepatWaktu++;
                                if ($laporan->status_keterlambatan == 'Terlambat') $totalTerlambat++;
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
                $persentaseHadir, // Simpan sebagai angka
                $persentaseTepatWaktu // Simpan sebagai angka
            ]);
        } // End looping per guru
        return $laporanPerSesi;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI SESI MINGGUAN');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:J2');
                $sheet->setCellValue('A2', 'PERIODE: ' . Carbon::parse($this->tanggalMulai)->isoFormat('D MMM Y') . ' s/d ' . Carbon::parse($this->tanggalSelesai)->isoFormat('D MMM Y'));
                $sheet->getStyle('A2')->getFont()->setItalic(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $headings = [
                    'Nama Guru', 'Total Jadwal', 'Hadir', 'Terlambat',
                    'Sakit', 'Izin', 'Alpa', 'Dinas Luar', '% Kehadiran', '% Ketepatan Waktu'
                ];
                $sheet->fromArray($headings, null, 'A4');
                $sheet->getStyle('A4:J4')->getFont()->setBold(true);
                $sheet->getStyle('A4:J4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDEDED');
                $sheet->getStyle('A4:J4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // --- 3. MENGISI DATA ---
                if ($this->laporanData->isNotEmpty()) {
                    $sheet->fromArray($this->laporanData->toArray(), null, 'A5');
                    $lastRow = count($this->laporanData) + 4;
                } else {
                    $lastRow = 4;
                }

                // --- 4. STYLING ---
                if ($lastRow > 4) {
                    $sheet->getStyle("A4:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("B5:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("I5:J{$lastRow}")->getFont()->setBold(true);

                    // ==========================================================
                    // ## PERBAIKAN FORMAT PERSENTASE ##
                    // ==========================================================
                    $sheet->getStyle("I5:J{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                    // ==========================================================

                } else {
                    $sheet->getStyle("A4:J4")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                }
                
                $sheet->getColumnDimension('A')->setAutoSize(true);
                foreach (range('B', 'J') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}

