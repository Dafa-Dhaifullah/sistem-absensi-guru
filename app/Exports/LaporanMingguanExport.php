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
    protected $semuaGuru; 
    protected $hariKerjaEfektif;

    // Konstruktor sekarang menerima data yang sudah diproses dari controller
    public function __construct(Collection $laporanHarianTeringkas, array $summaryTotal, Collection $semuaGuru, $tanggalRange, $tanggalMulai, $tanggalSelesai, $hariKerjaEfektif)
    {
        $this->laporanHarianTeringkas = $laporanHarianTeringkas;
        $this->summaryTotal = $summaryTotal;
        $this->semuaGuru = $semuaGuru; // Ambil collection User
        $this->tanggalRange = $tanggalRange;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->hariKerjaEfektif = $hariKerjaEfektif;
    }

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $today = Carbon::now('Asia/Jakarta')->startOfDay();

            // ====== SET HALAMAN: A4 LANDSCAPE + FIT TO WIDTH ======
            $pageSetup = $sheet->getPageSetup();
            $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
            // Fit 1 halaman lebar, tinggi bebas (0 = auto)
            $pageSetup->setFitToWidth(1)->setFitToHeight(0);

            // (Opsional) margin lebih rapat agar muat banyak
            $sheet->getPageMargins()
                ->setTop(0.5)->setBottom(0.5)
                ->setLeft(0.4)->setRight(0.4);

            // ====== POSISI BARIS ======
            $headerRow   = 4;  // header kolom di baris 4 (A4)
            $dataStart   = $headerRow + 1; // data mulai baris 5
            $titleRow1   = 1;
            $titleRow2   = 2;

            // ====== Hitung kolom terakhir secara DINAMIS ======
            $jumlahTanggal    = count($this->tanggalRange); // B..(tanggal)
            $jumlahRingkasan  = 5;                          // H, S, I, A, DL
            // A (nama guru = 1) + tanggal + ringkasan
            $lastColIndex     = 1 + $jumlahTanggal + $jumlahRingkasan;
            $lastCol          = Coordinate::stringFromColumnIndex($lastColIndex);

            // ====== 1. JUDUL LAPORAN (merge mengikuti kolom terakhir) ======
            $sheet->mergeCells("A{$titleRow1}:{$lastCol}{$titleRow1}");
            $sheet->setCellValue("A{$titleRow1}", 'LAPORAN REKAPITULASI MINGGUAN');
            $sheet->getStyle("A{$titleRow1}")->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$titleRow1}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $periode = Carbon::parse($this->tanggalMulai)->isoFormat('D MMM Y') . ' s/d ' .
                       Carbon::parse($this->tanggalSelesai)->isoFormat('D MMM Y');

            $sheet->mergeCells("A{$titleRow2}:{$lastCol}{$titleRow2}");
            $sheet->setCellValue("A{$titleRow2}", "PERIODE: {$periode}");
            $sheet->getStyle("A{$titleRow2}")->getFont()->setItalic(true);
            $sheet->getStyle("A{$titleRow2}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ====== 3. HEADER TABEL (baris 4) ======
            $sheet->setCellValue("A{$headerRow}", 'Nama Guru');

            $colIndex = 2; // B
            foreach ($this->tanggalRange as $tanggal) {
                $col = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue("{$col}{$headerRow}", $tanggal->isoFormat('ddd, D'));
                $colIndex++;
            }

            // ====== 4. KOLOM SUMMARY (lanjutan dari tanggal) ======
            $hCol  = Coordinate::stringFromColumnIndex($colIndex);
            $sCol  = Coordinate::stringFromColumnIndex($colIndex + 1);
            $iCol  = Coordinate::stringFromColumnIndex($colIndex + 2);
            $aCol  = Coordinate::stringFromColumnIndex($colIndex + 3);
            $dlCol = Coordinate::stringFromColumnIndex($colIndex + 4);

            $sheet->setCellValue("{$hCol}{$headerRow}", 'H');
            $sheet->setCellValue("{$sCol}{$headerRow}", 'S');
            $sheet->setCellValue("{$iCol}{$headerRow}", 'I');
            $sheet->setCellValue("{$aCol}{$headerRow}", 'A');
            $sheet->setCellValue("{$dlCol}{$headerRow}", 'DL');

            // ====== 5. ISI DATA (mulai baris 5) ======
            $rowIndex = $dataStart;

            foreach ($this->laporanHarianTeringkas as $index => $laporan) {
                $guru = $this->semuaGuru->get($index);
                if (!$guru) { $rowIndex++; continue; }

                $sheet->setCellValue("A{$rowIndex}", $laporan['name']);

                $colIndex = 2; // B
                foreach ($this->tanggalRange as $tanggal) {
                    $col    = Coordinate::stringFromColumnIndex($colIndex);
                    $status = $laporan['dataHarian'][$tanggal->toDateString()];
                    $sheet->setCellValue("{$col}{$rowIndex}", $status);
                    $colIndex++;
                }

                // Summary per guru (gunakan id sebagai key)
                $currentKey = $guru->id;

                if (isset($this->summaryTotal[$currentKey])) {
                    $summary = $this->summaryTotal[$currentKey];

                    $sheet->setCellValue("{$hCol}{$rowIndex}", $summary['totalHadir'] ?: '0');
                    $sheet->setCellValue("{$sCol}{$rowIndex}", $summary['totalSakit'] ?: '0');
                    $sheet->setCellValue("{$iCol}{$rowIndex}", $summary['totalIzin'] ?: '0');
                    $sheet->setCellValue("{$aCol}{$rowIndex}", $summary['totalAlpa'] ?: '0');
                    $sheet->setCellValue("{$dlCol}{$rowIndex}", $summary['totalDL'] ?: '0');
                } else {
                    $sheet->setCellValue("{$hCol}{$rowIndex}", '0');
                    $sheet->setCellValue("{$sCol}{$rowIndex}", '0');
                    $sheet->setCellValue("{$iCol}{$rowIndex}", '0');
                    $sheet->setCellValue("{$aCol}{$rowIndex}", '0');
                    $sheet->setCellValue("{$dlCol}{$rowIndex}", '0');
                }

                $rowIndex++;
            }

            // ====== 6. STYLING ======
            $lastCol = $dlCol;
            $lastRow = max($rowIndex - 1, $headerRow); // pengaman

            // Border & header fill
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")
                  ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")
                  ->getFill()->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFEDEDED');

            // Alignment
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")
                  ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                  ->setVertical(Alignment::VERTICAL_CENTER);

            if ($lastRow > $headerRow) {
                $sheet->getStyle("A{$dataStart}:A{$lastRow}")
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }

            // Autosize kolom
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension($hCol)->setAutoSize(true);
            $sheet->getColumnDimension($sCol)->setAutoSize(true);
            $sheet->getColumnDimension($iCol)->setAutoSize(true);
            $sheet->getColumnDimension($aCol)->setAutoSize(true);
            $sheet->getColumnDimension($dlCol)->setAutoSize(true);

            // Lebar kolom tanggal
            $colIndex = 2; // B
            foreach ($this->tanggalRange as $tanggal) {
                $col = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->getColumnDimension($col)->setWidth(8);
                $colIndex++;
            }

        },
    ];
}

}
