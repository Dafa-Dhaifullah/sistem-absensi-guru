<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanMingguanExport implements WithEvents
{
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $semuaGuru;
    protected $tanggalRange;

    public function __construct(string $tanggalMulai, string $tanggalSelesai)
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;

        $this->semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) {
                $query->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai]);
            }])->orderBy('name', 'asc')->get();

        $this->tanggalRange = \Carbon\Carbon::parse($this->tanggalMulai)->locale('id_ID')->toPeriod(\Carbon\Carbon::parse($this->tanggalSelesai));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

                // --- 1. PETUNJUK ---
                $sheet->setCellValue('A1', 'PETUNJUK:');
                $sheet->setCellValue('A2', 'H = Hadir, S = Sakit, I = Izin, A = Alpa, DL = Dinas Luar. Sel berwarna oranye menandakan hadir terlambat.');
                $sheet->mergeCells('A2:G2');

                // --- 2. HEADER TABEL ---
                $sheet->setCellValue('A4', 'Nama Guru');
                $colIndex = 2;
                foreach ($this->tanggalRange as $tanggal) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($col . '4', $tanggal->isoFormat('ddd, D'));
                    $colIndex++;
                }

                // --- 3. ISI DATA ---
                $rowIndex = 5;
                foreach ($this->semuaGuru as $guru) {
                    $sheet->setCellValue('A' . $rowIndex, $guru->name);
                    $colIndex = 2;
                    foreach ($this->tanggalRange as $tanggal) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $laporan = $guru->laporanHarian->firstWhere('tanggal', $tanggal->toDateString());
                        
                        $statusTampilan = '-';
                        if ($laporan) {
                            // ==========================================================
                            // ## REVISI DI SINI ##
                            // ==========================================================
                            if ($laporan->status == 'Hadir') {
                                $statusTampilan = 'H'; // Selalu tampilkan 'H'
                                // Beri warna oranye jika terlambat
                                if ($laporan->status_keterlambatan == 'Terlambat') {
                                    $sheet->getStyle($col . $rowIndex)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFC7CE'); // Warna Oranye/Merah muda
                                    $sheet->getStyle($col . $rowIndex)->getFont()->getColor()->setARGB('FF9C0006'); // Teks merah tua
                                }
                            } elseif ($laporan->status == 'DL') {
                                $statusTampilan = 'DL';
                            } else {
                                $statusTampilan = substr($laporan->status, 0, 1);
                            }
                            // ==========================================================
                        }
                        $sheet->setCellValue($col . $rowIndex, $statusTampilan);
                        $colIndex++;
                    }
                    $rowIndex++;
                }

                // --- 4. STYLING ---
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($this->tanggalRange->count() + 1);
                $lastRow = count($this->semuaGuru) + 4;
                $sheet->getStyle("A4:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A4:{$lastCol}4")->getFont()->setBold(true);
                $sheet->getStyle("A4:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A4:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getColumnDimension('A')->setAutoSize(true);
            },
        ];
    }
}