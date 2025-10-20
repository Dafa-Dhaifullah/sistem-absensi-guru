<?php

namespace App\Exports;

use App\Models\User;
use App\Models\HariLibur;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        $this->namaBulan = \Carbon\Carbon::create()->month($bulan)->locale('id_ID')->isoFormat('MMMM');
        $this->laporanData = $this->getLaporanData();
    }

    /**
     * Logika utama untuk mengambil dan memproses data laporan.
     */
    private function getLaporanData()
    {
        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) {
                $query->whereMonth('tanggal', $this->bulan)->whereYear('tanggal', $this->tahun);
            }])
            ->orderBy('name', 'asc')
            ->get();
        
        $hariLibur = HariLibur::whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->pluck('tanggal')
            ->map(fn($date) => $date->toDateString());

        $laporanPerSesi = collect();

        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0;
            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');

            for ($i = 1; $i <= \Carbon\Carbon::create($this->tahun, $this->bulan)->daysInMonth; $i++) {
                $tanggal = \Carbon\Carbon::create($this->tahun, $this->bulan, $i);
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) {
                    continue;
                }

                $jadwalUntukHariIni = $jadwalHariGuru->get($namaHari);
                $tipeMinggu = \App\Models\KalenderBlok::whereDate('tanggal_mulai', '<=', $tanggal)
                                ->whereDate('tanggal_selesai', '>=', $tanggal)
                                ->first()->tipe_minggu ?? 'Reguler';
                
                foreach ($jadwalUntukHariIni as $jadwal) {
                    if ($jadwal->tipe_blok == 'Setiap Minggu' || 
                       ($jadwal->tipe_blok == 'Hanya Minggu 1' && $tipeMinggu == 'Minggu 1') ||
                       ($jadwal->tipe_blok == 'Hanya Minggu 2' && $tipeMinggu == 'Minggu 2')) {
                        $totalSesiWajib++;
                    }
                }
            }

            $laporanGuru = $guru->laporanHarian;
            $totalHadir = $laporanGuru->where('status', 'Hadir')->count();
            $totalTepatWaktu = $laporanGuru->where('status', 'Hadir')->where('status_keterlambatan', 'Tepat Waktu')->count();
            $totalTerlambat = $laporanGuru->where('status', 'Hadir')->where('status_keterlambatan', 'Terlambat')->count();
            $totalSakit = $laporanGuru->where('status', 'Sakit')->count();
            $totalIzin = $laporanGuru->where('status', 'Izin')->count();
            $totalAlpa = $laporanGuru->where('status', 'Alpa')->count();
            $totalDL = $laporanGuru->where('status', 'DL')->count();
            
            $persentaseHadir = ($totalSesiWajib > 0) ? ($totalHadir / $totalSesiWajib) * 100 : 0;
            $persentaseTepatWaktu = ($totalHadir > 0) ? ($totalTepatWaktu / $totalHadir) * 100 : 0;

            $laporanPerSesi->push([
                'name' => $guru->name,
                'totalSesiWajib' => $totalSesiWajib,
                'totalHadir' => $totalHadir,
                'totalTerlambat' => $totalTerlambat,
                'totalSakit' => $totalSakit,
                'totalIzin' => $totalIzin,
                'totalAlpa' => $totalAlpa,
                'totalDL' => $totalDL,
                'persentaseHadir' => round($persentaseHadir, 2) . '%',
                'persentaseTepatWaktu' => round($persentaseTepatWaktu, 2) . '%',
            ]);
        }
        return $laporanPerSesi;
    }

    /**
     * Daftarkan event untuk memanipulasi sheet.
     */
    public function registerEvents(): array
    {
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
                    'Nama Guru', 'Total Sesi Wajib', 'Sesi Hadir', 'Sesi Terlambat',
                    'Sakit', 'Izin', 'Alpa', 'Dinas Luar', '% Kehadiran', '% Ketepatan Waktu'
                ];
                $sheet->fromArray($headings, null, 'A3'); // Mulai dari baris 3
                $sheet->getStyle('A3:J3')->getFont()->setBold(true);
                $sheet->getStyle('A3:J3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDEDED');

                // --- 3. MENGISI DATA ---
                $sheet->fromArray($this->laporanData->toArray(), null, 'A4'); // Mulai dari baris 4

                // --- 4. STYLING ---
                $lastRow = count($this->laporanData) + 3;
                $sheet->getStyle("A3:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("B3:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A4:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("I4:J{$lastRow}")->getFont()->setBold(true);
                
                // Atur lebar kolom
                $sheet->getColumnDimension('A')->setAutoSize(true);
                foreach (range('B', 'J') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}

