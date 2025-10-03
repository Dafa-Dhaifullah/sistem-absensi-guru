<?php

namespace App\Exports;

use App\Models\User;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanBulananExport implements WithEvents
{
    protected $bulan;
    protected $tahun;
    protected $semuaGuru;
    protected $daysInMonth;
    protected $namaBulan;
    protected $hariKerjaEfektif = [];

    public function __construct(int $bulan, int $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->namaBulan = \Carbon\Carbon::create()->month($bulan)->locale('id_ID')->isoFormat('MMMM');
        $this->daysInMonth = \Carbon\Carbon::createFromDate($tahun, $bulan)->daysInMonth;

        // REVISI: Menggunakan model User dan relasi yang benar
        $this->semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) {
                $query->whereYear('tanggal', $this->tahun)->whereMonth('tanggal', $this->bulan);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        // TAMBAHAN: Logika untuk menghitung hari kerja efektif
        $this->hitungHariKerja();
    }

    // Fungsi untuk menghitung hari kerja per guru
    private function hitungHariKerja()
    {
        $hariLibur = HariLibur::whereMonth('tanggal', $this->bulan)->whereYear('tanggal', $this->tahun)->pluck('tanggal')->map(fn($date) => $date->toDateString());
        
        foreach($this->semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->pluck('hari')->unique();
            $jumlahHariKerja = 0;

            for ($i = 1; $i <= $this->daysInMonth; $i++) {
                $tanggal = \Carbon\Carbon::create($this->tahun, $this->bulan, $i);
                $namaHari = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'][$tanggal->format('l')];

                if ($jadwalHariGuru->contains($namaHari) && !$hariLibur->contains($tanggal->toDateString())) {
                    $jumlahHariKerja++;
                }
            }
            $this->hariKerjaEfektif[$guru->id] = $jumlahHariKerja;
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- 1. HEADER KOMPLEKS (Struktur Anda dipertahankan) ---
                $sheet->mergeCells('C1:AF1')->setCellValue('C1', 'KETIDAKHADIRAN GURU');
                $sheet->mergeCells('C2:AF2')->setCellValue('C2', 'BULAN ' . strtoupper($this->namaBulan) . ' ' . $this->tahun);
                $sheet->mergeCells('C3:AF3')->setCellValue('C3', 'TANGGAL');
                $sheet->mergeCells('A1:A5')->setCellValue('A1', 'NO');
                $sheet->mergeCells('B1:B5')->setCellValue('B1', 'NAMA GURU');

                for ($i = 1; $i <= $this->daysInMonth; $i++) {
                    $tanggal = \Carbon\Carbon::create($this->tahun, $this->bulan, $i)->locale('id_ID');
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                    
                    $sheet->setCellValue($col . '4', $i); 
                    $sheet->setCellValue($col . '5', $tanggal->isoFormat('dd')); 
                }
                
                // --- 2. MENGISI DATA ABSENSI ---
                $row = 6;
                foreach ($this->semuaGuru as $index => $guru) {
                    $sheet->setCellValue('A' . $row, $index + 1);
                    $sheet->setCellValue('B' . $row, $guru->name); // REVISI: $guru->name

                    for ($i = 1; $i <= $this->daysInMonth; $i++) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                        $tanggalCek = sprintf('%s-%s-%s', $this->tahun, str_pad($this->bulan, 2, '0', STR_PAD_LEFT), str_pad($i, 2, '0', STR_PAD_LEFT));
                        $laporan = $guru->laporanHarian->firstWhere('tanggal', $tanggalCek);
                        $status = $laporan ? $laporan->status : '';
                        
                        $cellValue = '';
                        if ($status == 'Sakit') $cellValue = 'S';
                        elseif ($status == 'Izin') $cellValue = 'I';
                        elseif ($status == 'Alpa') $cellValue = 'A';
                        elseif ($status == 'DL') $cellValue = 'DL';
                        
                        $sheet->setCellValue($col . $row, $cellValue);
                    }
                    $row++;
                }

                // --- 3. HEADER & KOLOM SUMMARY ---
                $lastDataColIndex = $this->daysInMonth + 2;
                
                $sCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 1);
                $iCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 2);
                $aCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 3);
                $dlCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 4);
                $jumlahCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 5);
                $persenTidakHadirCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 6);
                $persenHadirCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 7); // Kolom baru
                
                // REVISI: Merge Header disesuaikan dengan kolom baru
                $sheet->mergeCells("{$sCol}1:{$dlCol}3")->setCellValue("{$sCol}1", 'KETERANGAN');
                $sheet->mergeCells("{$jumlahCol}1:{$persenHadirCol}3")->setCellValue("{$jumlahCol}1", 'PERSENTASE');

                // Tulis ke baris 4 dulu
                $sheet->setCellValue("{$sCol}4", 'S');
                $sheet->setCellValue("{$iCol}4", 'I');
                $sheet->setCellValue("{$aCol}4", 'A');
                $sheet->setCellValue("{$dlCol}4", 'DL');
                $sheet->setCellValue("{$jumlahCol}4", 'JML');
                $sheet->setCellValue("{$persenTidakHadirCol}4", '% Tdk Hadir');
                $sheet->setCellValue("{$persenHadirCol}4", '% Hadir'); // Kolom baru

                // Baru merge 4:5
                $sheet->mergeCells("{$sCol}4:{$sCol}5");
                $sheet->mergeCells("{$iCol}4:{$iCol}5");
                $sheet->mergeCells("{$aCol}4:{$aCol}5");
                $sheet->mergeCells("{$dlCol}4:{$dlCol}5");
                $sheet->mergeCells("{$jumlahCol}4:{$jumlahCol}5");
                $sheet->mergeCells("{$persenTidakHadirCol}4:{$persenTidakHadirCol}5");
                $sheet->mergeCells("{$persenHadirCol}4:{$persenHadirCol}5"); // Kolom baru

                // --- 4. MENGISI DATA SUMMARY (PER BARIS) ---
                $row = 6;
                foreach ($this->semuaGuru as $guru) {
                    $totalHadir = $guru->laporanHarian->where('status', 'Hadir')->count();
                    $totalSakit = $guru->laporanHarian->where('status', 'Sakit')->count();
                    $totalIzin = $guru->laporanHarian->where('status', 'Izin')->count();
                    $totalAlpa = $guru->laporanHarian->where('status', 'Alpa')->count();
                    $totalDL = $guru->laporanHarian->where('status', 'DL')->count();
                    $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa;
                    
                    // REVISI: Perhitungan Persentase yang akurat
                    $hariKerja = $this->hariKerjaEfektif[$guru->id] ?? 0;
                    $persentaseTidakHadir = ($hariKerja > 0) ? ($totalTidakHadir / $hariKerja) * 100 : 0;
                    $persentaseHadir = ($hariKerja > 0) ? ($totalHadir / $hariKerja) * 100 : 0;
                    
                    $sheet->setCellValue("{$sCol}{$row}", $totalSakit ?: '0');
                    $sheet->setCellValue("{$iCol}{$row}", $totalIzin ?: '0');
                    $sheet->setCellValue("{$aCol}{$row}", $totalAlpa ?: '0');
                    $sheet->setCellValue("{$dlCol}{$row}", $totalDL ?: '0');
                    $sheet->setCellValue("{$jumlahCol}{$row}", $totalTidakHadir ?: '0');
                    $sheet->setCellValue("{$persenTidakHadirCol}{$row}", round($persentaseTidakHadir) . '%');
                    $sheet->setCellValue("{$persenHadirCol}{$row}", round($persentaseHadir) . '%'); // Kolom baru
                    $row++;
                }
                
                // --- 5. STYLING ---
                $lastRow = count($this->semuaGuru) + 5;
                $lastCol = $persenHadirCol; // REVISI: Kolom terakhir
                $fullRange = "A1:{$lastCol}{$lastRow}";
                
                $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($fullRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($fullRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A1:{$lastCol}5")->getFont()->setBold(true);
                $sheet->getStyle("B6:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("{$sCol}1:{$dlCol}3")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("{$jumlahCol}1:{$persenHadirCol}3")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(30);
                for ($i = 1; $i <= $this->daysInMonth; $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                    $sheet->getColumnDimension($col)->setWidth(4);
                }
                foreach ([$sCol, $iCol, $aCol, $dlCol, $jumlahCol, $persenTidakHadirCol, $persenHadirCol] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(10); // Lebar kolom summary disesuaikan
                }
            },
        ];
    }
}

