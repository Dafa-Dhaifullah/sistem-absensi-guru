<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;
use Carbon\Carbon; // Impor Carbon
use PhpOffice\PhpSpreadsheet\Cell\Coordinate; // Impor Coordinate

class LaporanMingguanExport implements WithEvents
{
    protected $laporanHarianTeringkas;
    protected $summaryTotal;
    protected $tanggalRange;
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $semuaGuru; // Dibutuhkan untuk mapping key

    // Konstruktor sekarang menerima data yang sudah diproses dari controller
    public function __construct(Collection $laporanHarianTeringkas, array $summaryTotal, Collection $semuaGuru, $tanggalRange, $tanggalMulai, $tanggalSelesai)
    {
        $this->laporanHarianTeringkas = $laporanHarianTeringkas;
        $this->summaryTotal = $summaryTotal;
        $this->semuaGuru = $semuaGuru; // Ambil collection User
        $this->tanggalRange = $tanggalRange;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $today = Carbon::now('Asia/Jakarta')->startOfDay();
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

                // --- 1. JUDUL LAPORAN ---
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI MINGGUAN');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells('A2:H2');
                $sheet->setCellValue('A2', 'PERIODE: ' . Carbon::parse($this->tanggalMulai)->isoFormat('D MMM Y') . ' s/d ' . Carbon::parse($this->tanggalSelesai)->isoFormat('D MMM Y'));
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
                    $col = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($col . '7', $tanggal->isoFormat('ddd, D'));
                    $colIndex++;
                }
                
                // --- 4. HEADER SUMMARY ---
                $hCol = Coordinate::stringFromColumnIndex($colIndex);
                $sCol = Coordinate::stringFromColumnIndex($colIndex + 1);
                $iCol = Coordinate::stringFromColumnIndex($colIndex + 2);
                $aCol = Coordinate::stringFromColumnIndex($colIndex + 3);
                $dlCol = Coordinate::stringFromColumnIndex($colIndex + 4);
                $sheet->setCellValue("{$hCol}7", 'H');
                $sheet->setCellValue("{$sCol}7", 'S');
                $sheet->setCellValue("{$iCol}7", 'I');
                $sheet->setCellValue("{$aCol}7", 'A');
                $sheet->setCellValue("{$dlCol}7", 'DL');

                // --- 5. ISI DATA (BAGIAN YANG DIPERBAIKI) ---
                $rowIndex = 8;
                // $summaryKeys = array_keys($this->summaryTotal); // <-- DIHAPUS: Ini sumber masalahnya

                // Kita loop $laporanHarianTeringkas, yang kita tahu urutannya
                // SAMA DENGAN $this->semuaGuru (keduanya di-order by name)
                foreach ($this->laporanHarianTeringkas as $index => $laporan) {
                    
                    // AMBIL ID GURU YANG BENAR:
                    // Gunakan $index untuk mengambil guru dari $this->semuaGuru
                    $guru = $this->semuaGuru->get($index);

                    // Safety check jika terjadi ketidakcocokan (seharusnya tidak)
                    if (!$guru) {
                        $rowIndex++;
                        continue;
                    }

                    // $laporan['name'] juga bisa diganti $guru->name
                    $sheet->setCellValue('A' . $rowIndex, $laporan['name']);
                    
                    $colIndex = 2;
                    foreach ($this->tanggalRange as $tanggal) {
                        $col = Coordinate::stringFromColumnIndex($colIndex);
                        $status = $laporan['dataHarian'][$tanggal->toDateString()];
                        $sheet->setCellValue($col . $rowIndex, $status);
                        $colIndex++;
                    }
                    
                    // --- PERBAIKAN LOGIKA SUMMARY ---
                    // Gunakan $guru->id sebagai kunci yang BENAR
                    $currentKey = $guru->id; 
                    
                    // Pastikan data summary ada untuk guru ini
                    if (isset($this->summaryTotal[$currentKey])) {
                        $summary = $this->summaryTotal[$currentKey];
                        
                        $sheet->setCellValue("{$hCol}{$rowIndex}", $summary['totalHadir'] ?: '0');
                        $sheet->setCellValue("{$sCol}{$rowIndex}", $summary['totalSakit'] ?: '0');
                        $sheet->setCellValue("{$iCol}{$rowIndex}", $summary['totalIzin'] ?: '0');
                        $sheet->setCellValue("{$aCol}{$rowIndex}", $summary['totalAlpa'] ?: '0');
                        $sheet->setCellValue("{$dlCol}{$rowIndex}", $summary['totalDL'] ?: '0');
                    } else {
                        // Fallback jika tidak ada data summary
                        $sheet->setCellValue("{$hCol}{$rowIndex}", '0');
                        $sheet->setCellValue("{$sCol}{$rowIndex}", '0');
                        $sheet->setCellValue("{$iCol}{$rowIndex}", '0');
                        $sheet->setCellValue("{$aCol}{$rowIndex}", '0');
                        $sheet->setCellValue("{$dlCol}{$rowIndex}", '0');
                    }
                    
                    $rowIndex++;
                }

                // --- 6. STYLING ---
                $lastCol = $dlCol;
                // Gunakan $rowIndex (yang sudah di-increment) dikurangi 1 sebagai baris terakhir
                $lastRow = $rowIndex - 1; 
                
                if ($lastRow < 8) { // Pengaman jika data kosong
                   $lastRow = 7; 
                }
                
                $sheet->getStyle("A7:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A7:{$lastCol}7")->getFont()->setBold(true);
                $sheet->getStyle("A7:{$lastCol}7")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDEDED'); // Latar header
                $sheet->getStyle("A7:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A7:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                
                // Pastikan $lastRow > 7 sebelum menerapkan style ke data (baris 8 dst.)
                if ($lastRow > 7) {
                    $sheet->getStyle("A8:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
                
                $sheet->getColumnDimension('A')->setAutoSize(true);
                
                // Set AutoSize untuk kolom summary (H, S, I, A, DL)
                $sheet->getColumnDimension($hCol)->setAutoSize(true);
                $sheet->getColumnDimension($sCol)->setAutoSize(true);
                $sheet->getColumnDimension($iCol)->setAutoSize(true);
                $sheet->getColumnDimension($aCol)->setAutoSize(true);
                $sheet->getColumnDimension($dlCol)->setAutoSize(true);
                
                // Set lebar spesifik untuk kolom tanggal (agar tidak terlalu lebar)
                $colIndex = 2; // Mulai dari B
                foreach ($this->tanggalRange as $tanggal) {
                    $col = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($col)->setWidth(8); // Lebar 8
                    $colIndex++;
                }

            },
        ];
    }
}
