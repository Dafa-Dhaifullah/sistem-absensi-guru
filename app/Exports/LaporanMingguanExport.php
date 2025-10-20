<?php

namespace App\Exports;

use App\Models\User;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanMingguanExport implements WithEvents
{
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $semuaGuru;
    protected $tanggalRange;
    protected $hariKerjaEfektif = [];

    public function __construct(string $tanggalMulai, string $tanggalSelesai)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->tanggalRange = \Carbon\Carbon::parse($this->tanggalMulai)->locale('id_ID')->toPeriod(\Carbon\Carbon::parse($this->tanggalSelesai));

        $this->semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) {
                $query->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai]);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        $this->hitungHariKerja();
    }

    // Fungsi untuk menghitung hari kerja efektif per guru
    private function hitungHariKerja()
    {
        $hariLibur = HariLibur::whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai])
            ->pluck('tanggal')->map(fn($date) => $date->toDateString());
        
        foreach($this->semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->pluck('hari')->unique();
            $hariKerjaList = collect(); 

            foreach ($this->tanggalRange as $tanggal) {
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                if ($jadwalHariGuru->contains($namaHari) && !$hariLibur->contains($tanggal->toDateString())) {
                    $hariKerjaList->push($tanggal->toDateString());
                }
            }
            $this->hariKerjaEfektIF[$guru->id] = $hariKerjaList;
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $today = \Carbon\Carbon::now('Asia/Jakarta')->startOfDay();
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

                // --- 1. JUDUL LAPORAN (BARU) ---
                $sheet->mergeCells('A1:M1');
                $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI MINGGUAN');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:M2');
                $sheet->setCellValue('A2', 'PERIODE: ' . \Carbon\Carbon::parse($this->tanggalMulai)->isoFormat('D MMM Y') . ' s/d ' . \Carbon\Carbon::parse($this->tanggalSelesai)->isoFormat('D MMM Y'));
                $sheet->getStyle('A2')->getFont()->setItalic(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


                // --- 2. PETUNJUK ---
                $sheet->setCellValue('A4', 'PETUNJUK:');
                $sheet->setCellValue('A5', 'H = Hadir, S = Sakit, I = Izin, A = Alpa, DL = Dinas Luar.');
                $sheet->mergeCells('A5:H5');

                // --- 3. HEADER TABEL ---
                $sheet->setCellValue('A7', 'Nama Guru');
                $colIndex = 2; // Mulai dari B
                foreach ($this->tanggalRange as $tanggal) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($col . '7', $tanggal->isoFormat('ddd, D'));
                    $colIndex++;
                }
                
                // --- 4. HEADER SUMMARY ---
                $hCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $iCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 2);
                $aCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 3);
                $dlCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 4);
                $sheet->setCellValue("{$hCol}7", 'H');
                $sheet->setCellValue("{$sCol}7", 'S');
                $sheet->setCellValue("{$iCol}7", 'I');
                $sheet->setCellValue("{$aCol}7", 'A');
                $sheet->setCellValue("{$dlCol}7", 'DL');

                // --- 5. ISI DATA ---
                $rowIndex = 8; // Mulai dari baris 8
                foreach ($this->semuaGuru as $guru) {
                    $sheet->setCellValue('A' . $rowIndex, $guru->name);
                    
                    $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;
                    $colIndex = 2;

                    foreach ($this->tanggalRange as $tanggal) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $tanggalCek = $tanggal->toDateString();
                        $cellValue = '-';
                        $isHariKerja = isset($this->hariKerjaEfektif[$guru->id]) && $this->hariKerjaEfektif[$guru->id]->contains($tanggalCek);

                        if ($isHariKerja) {
                            $laporanPerHari = $guru->laporanHarian->where('tanggal', $tanggalCek);
                            if ($laporanPerHari->isNotEmpty()) {
                                if ($laporanPerHari->contains('status', 'Hadir')) {
                                    $cellValue = 'H'; $totalHadir++;
                                } elseif ($laporanPerHari->contains('status', 'DL')) {
                                    $cellValue = 'DL'; $totalDL++;
                                } elseif ($laporanPerHari->contains('status', 'Sakit')) {
                                    $cellValue = 'S'; $totalSakit++;
                                } elseif ($laporanPerHari->contains('status', 'Izin')) {
                                    $cellValue = 'I'; $totalIzin++;
                                } else {
                                    $cellValue = 'A'; $totalAlpa++;
                                }
                            } else {
                                if ($tanggal->isBefore($today)) {
                                    $cellValue = 'A'; $totalAlpa++;
                                } else {
                                    $cellValue = '-';
                                }
                            }
                        }
                        $sheet->setCellValue($col . $rowIndex, $cellValue);
                        $colIndex++;
                    }
                    
                    // Isi Summary
                    $sheet->setCellValue("{$hCol}{$rowIndex}", $totalHadir ?: '0');
                    $sheet->setCellValue("{$sCol}{$rowIndex}", $totalSakit ?: '0');
                    $sheet->setCellValue("{$iCol}{$rowIndex}", $totalIzin ?: '0');
                    $sheet->setCellValue("{$aCol}{$rowIndex}", $totalAlpa ?: '0');
                    $sheet->setCellValue("{$dlCol}{$rowIndex}", $totalDL ?: '0');
                    
                    $rowIndex++;
                }

                // --- 6. STYLING ---
                $lastCol = $dlCol;
                $lastRow = count($this->semuaGuru) + 7; // Mulai dari 7 + jumlah guru
                $sheet->getStyle("A7:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A7:{$lastCol}7")->getFont()->setBold(true);
                $sheet->getStyle("A7:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A7:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A8:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getColumnDimension('A')->setAutoSize(true);
            },
        ];
    }
}