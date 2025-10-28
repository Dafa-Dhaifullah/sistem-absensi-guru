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

        $this->semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) {
                $query->whereYear('tanggal', $this->tahun)->whereMonth('tanggal', $this->bulan);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        $this->hitungHariKerja();
    }

    private function hitungHariKerja()
    {
        $hariLibur = HariLibur::whereMonth('tanggal', $this->bulan)->whereYear('tanggal', $this->tahun)->pluck('tanggal')->map(fn($date) => $date->toDateString());
        
        foreach($this->semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->pluck('hari')->unique();
            $hariKerjaList = collect(); 

            for ($i = 1; $i <= $this->daysInMonth; $i++) {
                $tanggal = \Carbon\Carbon::create($this->tahun, $this->bulan, $i);
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                
                if ($jadwalHariGuru->contains($namaHari) && !$hariLibur->contains($tanggal->toDateString())) {
                    $hariKerjaList->push($tanggal->toDateString());
                }
            }
            $this->hariKerjaEfektif[$guru->id] = $hariKerjaList;
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // --- 1. HEADER KOMPLEKS ---
                $sheet->mergeCells('C1:AF1')->setCellValue('C1', 'KEHADIRAN GURU');
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
                    $sheet->setCellValue('B' . $row, $guru->name);

                    $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

                    for ($i = 1; $i <= $this->daysInMonth; $i++) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                        $tanggal = \Carbon\Carbon::create($this->tahun, $this->bulan, $i);
                        $tanggalCek = $tanggal->toDateString();
                        
                        $cellValue = '-'; // Default untuk non-hari kerja
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
                                // ==========================================================
                                // ## REVISI LOGIKA ALPA: Hanya tandai Alpa jika hari sudah lewat ##
                                // ==========================================================
                                if ($tanggal->isPast()) {
                                    $cellValue = 'A'; 
                                    $totalAlpa++;
                                } else {
                                    $cellValue = '-'; // Jika hari kerja tapi di masa depan
                                }
                                // ==========================================================
                            }
                        }
                        $sheet->setCellValue($col . $row, $cellValue);
                    }
                    
                    // --- 3. MENGISI DATA SUMMARY (PER BARIS) ---
                    $lastDataColIndex = $this->daysInMonth + 2;
                    $sCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 1);
                    $iCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 2);
                    $aCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 3);
                    $dlCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 4);
                    $jumlahCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 5);
                    $persenTidakHadirCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 6);
                    $persenHadirCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 7);

                    $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa;
                    $hariKerjaCount = isset($this->hariKerjaEfektif[$guru->id]) ? $this->hariKerjaEfektif[$guru->id]->count() : 0;
                    $persentaseTidakHadir = ($hariKerjaCount > 0) ? ($totalTidakHadir / $hariKerjaCount) * 100 : 0;
                    $persentaseHadir = ($hariKerjaCount > 0) ? ($totalHadir / $hariKerjaCount) * 100 : 0;
                    
                    $sheet->setCellValue("{$sCol}{$row}", $totalSakit ?: '0');
                    $sheet->setCellValue("{$iCol}{$row}", $totalIzin ?: '0');
                    $sheet->setCellValue("{$aCol}{$row}", $totalAlpa ?: '0');
                    $sheet->setCellValue("{$dlCol}{$row}", $totalDL ?: '0');
                    $sheet->setCellValue("{$jumlahCol}{$row}", $totalTidakHadir ?: '0');
                    $sheet->setCellValue("{$persenTidakHadirCol}{$row}", round($persentaseTidakHadir) . '%');
                    $sheet->setCellValue("{$persenHadirCol}{$row}", round($persentaseHadir) . '%');
                    
                    $row++;
                }

                // --- 4. HEADER SUMMARY ---
                $lastCol = $persenHadirCol;
                $sheet->mergeCells("{$sCol}1:{$dlCol}3")->setCellValue("{$sCol}1", 'KETERANGAN');
                $sheet->mergeCells("{$jumlahCol}1:{$lastCol}3")->setCellValue("{$jumlahCol}1", 'PERSENTASE');
                $sheet->setCellValue("{$sCol}4", 'S');
                $sheet->setCellValue("{$iCol}4", 'I');
                $sheet->setCellValue("{$aCol}4", 'A');
                $sheet->setCellValue("{$dlCol}4", 'DL');
                $sheet->setCellValue("{$jumlahCol}4", 'JML');
                $sheet->setCellValue("{$persenTidakHadirCol}4", '% Tdk Hadir');
                $sheet->setCellValue("{$persenHadirCol}4", '% Hadir');
                $sheet->mergeCells("{$sCol}4:{$sCol}5");
                $sheet->mergeCells("{$iCol}4:{$iCol}5");
                $sheet->mergeCells("{$aCol}4:{$aCol}5");
                $sheet->mergeCells("{$dlCol}4:{$dlCol}5");
                $sheet->mergeCells("{$jumlahCol}4:{$jumlahCol}5");
                $sheet->mergeCells("{$persenTidakHadirCol}4:{$persenTidakHadirCol}5");
                $sheet->mergeCells("{$persenHadirCol}4:{$persenHadirCol}5");
                
                // --- 5. STYLING ---
                $lastRow = count($this->semuaGuru) + 5;
                $fullRange = "A1:{$lastCol}{$lastRow}";
                
                $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($fullRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($fullRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A1:{$lastCol}5")->getFont()->setBold(true);
                $sheet->getStyle("B6:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("{$sCol}1:{$dlCol}3")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("{$jumlahCol}1:{$lastCol}3")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(30);
                for ($i = 1; $i <= $this->daysInMonth; $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                    $sheet->getColumnDimension($col)->setWidth(4);
                }
                foreach ([$sCol, $iCol, $aCol, $dlCol, $jumlahCol, $persenTidakHadirCol, $persenHadirCol] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(10);
                }
                
                // --- 6. PETUNJUK (DIHAPUS) ---
                // Petunjuk untuk warna oranye dihapus.
            },
        ];
    }
}