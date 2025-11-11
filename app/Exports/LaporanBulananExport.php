<?php

namespace App\Exports;

use App\Models\User;
use App\Models\HariLibur;
use App\Models\KalenderBlok; 
use App\Models\MasterHariKerja;
use App\Models\MasterJamPelajaran;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LaporanBulananExport implements WithEvents
{
    protected $bulan;
    protected $tahun;
    protected $semuaGuru;
    protected $daysInMonth;
    protected $namaBulan;
    protected $hariKerjaEfektif = [];
    protected $jamTerakhirPerHari;

    public function __construct(int $bulan, int $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->namaBulan = \Carbon\Carbon::create()->month($bulan)->locale('id_ID')->isoFormat('MMMM');
        $this->daysInMonth = \Carbon\Carbon::createFromDate($tahun, $bulan)->daysInMonth;

        // ==========================================================
        // == PERBAIKAN 1: Error Scope di 'with()' ==
        // ==========================================================
        $this->semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) use ($bulan, $tahun) { // <-- Gunakan 'use()'
                // Gunakan variabel lokal $bulan dan $tahun
                $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();

         $this->jamTerakhirPerHari = MasterJamPelajaran::select('hari', \DB::raw('MAX(jam_selesai) as jam_terakhir'))
                            ->groupBy('hari')
                            ->pluck('jam_terakhir', 'hari');
        
        $this->hitungHariKerja();
    }

    // ==========================================================
    // == PERBAIKAN 2: Logika 'hitungHariKerja' (Penyebab Bug 33%) ==
    // ==========================================================
    private function hitungHariKerja()
    {
        $hariLibur = HariLibur::whereMonth('tanggal', $this->bulan)->whereYear('tanggal', $this->tahun)->pluck('tanggal')->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());
        
        $awalBulan = \Carbon\Carbon::create($this->tahun, $this->bulan, 1)->startOfMonth();
        $akhirBulan = $awalBulan->clone()->endOfMonth();

        // 1. Ambil data KalenderBlok satu kali
        $kalenderBlokBulanIni = KalenderBlok::where(function ($query) use ($awalBulan, $akhirBulan) {
            $query->where('tanggal_mulai', '<=', $akhirBulan)
                  ->where('tanggal_selesai', '>=', $awalBulan);
        })->get();

        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        foreach($this->semuaGuru as $guru) {
            $jadwalPerHari = $guru->jadwalPelajaran->groupBy('hari'); // Grup jadwal per hari
            $hariKerjaList = collect(); 

            for ($i = 1; $i <= $this->daysInMonth; $i++) {
                $tanggal = \Carbon\Carbon::create($this->tahun, $this->bulan, $i)->startOfDay();
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                  if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }

                // 2. Cek Libur atau tidak ada jadwal sama sekali hari itu
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalPerHari->has($namaHari)) {
                    continue;
                }
                
                // 3. Cari Tipe Minggu (Minggu 1 / Minggu 2)
                $kalenderBlokHariIni = $kalenderBlokBulanIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = \Carbon\Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = \Carbon\Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu));

                // 4. Filter jadwal berdasarkan Tipe Minggu (logika str_contains)
                $jadwalMentahHariIni = $jadwalPerHari->get($namaHari);
                
                $jadwalValid = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                });

                // 5. Jika ada jadwal valid, baru hitung sebagai hari kerja
                if ($jadwalValid->isNotEmpty()) {
                    $hariKerjaList->push($tanggal->toDateString());
                }
            }
            $this->hariKerjaEfektif[$guru->id] = $hariKerjaList;
        }
    }

    public function registerEvents(): array
    {
        // Kode 'registerEvents' Anda (dengan menyalin properti ke var lokal)
        // sudah benar dan tidak perlu diubah.
        
        $namaBulan = $this->namaBulan;
        $tahun = $this->tahun;
        $bulan = $this->bulan; 
        $daysInMonth = $this->daysInMonth;
        $semuaGuru = $this->semuaGuru;
        $hariKerjaEfektif = $this->hariKerjaEfektif;
        $jamTerakhirPerHari = $this->jamTerakhirPerHari;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($namaBulan, $tahun, $bulan, $daysInMonth, $semuaGuru, $hariKerjaEfektif, $jamTerakhirPerHari) {
                $sheet = $event->sheet->getDelegate();
                // === A4 LANDSCAPE & FIT ===
$pageSetup = $sheet->getPageSetup();
$pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
// Fit 1 halaman lebar, tinggi otomatis
$pageSetup->setFitToWidth(1)->setFitToHeight(0);
// (opsional) center secara horizontal saat print
$pageSetup->setHorizontalCentered(true);

// Margin rapat agar muat
$sheet->getPageMargins()
    ->setTop(0.5)->setBottom(0.5)
    ->setLeft(0.4)->setRight(0.4);

                $today = \Carbon\Carbon::now('Asia/Jakarta')->startOfDay();
                 $now = \Carbon\Carbon::now('Asia/Jakarta');
                
                // --- 1. HEADER KOMPLEKS ---
                $sheet->mergeCells('C1:AF1')->setCellValue('C1', 'KEHADIRAN GURU');
                $sheet->mergeCells('C2:AF2')->setCellValue('C2', 'BULAN ' . strtoupper($namaBulan) . ' ' . $tahun);
                $sheet->mergeCells('C3:AF3')->setCellValue('C3', 'TANGGAL');
                $sheet->mergeCells('A1:A5')->setCellValue('A1', 'NO');
                $sheet->mergeCells('B1:B5')->setCellValue('B1', 'NAMA GURU');

                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $tanggal = \Carbon\Carbon::create($tahun, $bulan, $i)->locale('id_ID');
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                    
                    $sheet->setCellValue($col . '4', $i); 
                    $sheet->setCellValue($col . '5', $tanggal->isoFormat('dd')); 
                }
                
                $lastDataColIndex = $daysInMonth + 2;
                $sCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 1);
                $iCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 2);
                $aCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 3);
                $dlCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 4);
                $jumlahCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 5);
                $persenTidakHadirCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 6);
                $persenHadirCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastDataColIndex + 7);

                // --- 2. MENGISI DATA ABSENSI ---
                $row = 6;
                foreach ($semuaGuru as $index => $guru) {
                    $sheet->setCellValue('A' . $row, $index + 1);
                    $sheet->setCellValue('B' . $row, $guru->name);
                    $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

                    for ($i = 1; $i <= $daysInMonth; $i++) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                        $tanggal = \Carbon\Carbon::create($tahun, $bulan, $i)->startOfDay();
                        $tanggalCek = $tanggal->toDateString();
                        $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                        
                        $cellValue = '-'; 
                        // Logika $isHariKerja sekarang akurat
                        $isHariKerja = isset($hariKerjaEfektif[$guru->id]) && $hariKerjaEfektif[$guru->id]->contains($tanggalCek);

                        if ($isHariKerja) {
                            $laporanPerHari = $guru->laporanHarian->where('tanggal', $tanggal);
                            
                            if ($laporanPerHari->isNotEmpty()) {
                                if ($laporanPerHari->contains('status', 'Hadir')) { $cellValue = 'H'; $totalHadir++; }
                                elseif ($laporanPerHari->contains('status', 'DL')) { $cellValue = 'DL'; $totalDL++; }
                                elseif ($laporanPerHari->contains('status', 'Sakit')) { $cellValue = 'S'; $totalSakit++; }
                                elseif ($laporanPerHari->contains('status', 'Izin')) { $cellValue = 'I'; $totalIzin++; }
                                else { $cellValue = 'A'; $totalAlpa++; }
                            } else {
                               if ($tanggal->isBefore($today)) {
                                    $cellValue = 'A'; 
                                    $totalAlpa++;
                                } elseif ($tanggal->is($today)) {
                                    $jamTerakhirString = $jamTerakhirPerHari->get($namaHari);
                                    if ($jamTerakhirString && $now->toTimeString() > $jamTerakhirString) {
                                        $cellValue = 'A';
                                        $totalAlpa++;
                                    } else {
                                        $cellValue = '-'; // Masih "Belum Absen"
                                    }
                                } else {
                                    $cellValue = '-'; // Masa depan
                                }
                            }
                        }
                        $sheet->setCellValue($col . $row, $cellValue);
                    }
                    
                    // --- 3. MENGISI DATA SUMMARY (PER BARIS) ---
                    $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa;
                    $hariKerjaCount = isset($hariKerjaEfektif[$guru->id]) ? $hariKerjaEfektif[$guru->id]->count() : 0;
                    
                    $persentaseTidakHadir = ($hariKerjaCount > 0) ? ($totalTidakHadir / $hariKerjaCount) : 0;
                    $persentaseHadir = ($hariKerjaCount > 0) ? ($totalHadir / $hariKerjaCount) : 0;
                    
                    $sheet->setCellValue("{$sCol}{$row}", $totalSakit ?: '0');
                    $sheet->setCellValue("{$iCol}{$row}", $totalIzin ?: '0');
                    $sheet->setCellValue("{$aCol}{$row}", $totalAlpa ?: '0');
                    $sheet->setCellValue("{$dlCol}{$row}", $totalDL ?: '0');
                    $sheet->setCellValue("{$jumlahCol}{$row}", $totalTidakHadir ?: '0');
                    $sheet->setCellValue("{$persenTidakHadirCol}{$row}", $persentaseTidakHadir);
                    $sheet->setCellValue("{$persenHadirCol}{$row}", $persentaseHadir);
                    
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
                $sheet->setCellValue("{$jumlahCol}4", 'Tdk Hadir');
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
                $lastRow = count($semuaGuru) + 5;
                $fullRange = "A1:{$lastCol}{$lastRow}";
                // === Batas area cetak (biar pas ke A4) ===
$sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$lastRow}");

                
                $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle($fullRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($fullRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A1:{$lastCol}5")->getFont()->setBold(true);
                $sheet->getStyle("B6:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("{$sCol}1:{$dlCol}3")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("{$jumlahCol}1:{$lastCol}3")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Terapkan format persentase ke kolom
                if ($lastRow > 5) {
                    $sheet->getStyle("{$persenTidakHadirCol}6:{$persenTidakHadirCol}{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                    $sheet->getStyle("{$persenHadirCol}6:{$persenHadirCol}{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                }

                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(30);
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
                    $sheet->getColumnDimension($col)->setWidth(4);
                }
                foreach ([$sCol, $iCol, $aCol, $dlCol, $jumlahCol, $persenTidakHadirCol, $persenHadirCol] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(10);
                }
                
            
            },
        ];
    }
}