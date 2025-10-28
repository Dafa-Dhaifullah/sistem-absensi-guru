<?php

namespace App\Exports;

use App\Models\User;
use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\KalenderBlok;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
        $today = now('Asia/Jakarta')->startOfDay();
        $tanggalRange = \Carbon\Carbon::parse($this->tanggalMulai)->locale('id_ID')->toPeriod(\Carbon\Carbon::parse($this->tanggalSelesai));
        
        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) {
                $query->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai]);
            }])
            ->orderBy('name', 'asc')
            ->get();
        
        $hariLibur = HariLibur::whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai])
            ->pluck('tanggal')
            ->map(fn($date) => $date->toDateString());

        $laporanPerSesi = collect();

        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0; $totalHadir = 0; $totalTepatWaktu = 0; $totalTerlambat = 0;
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');

            foreach ($tanggalRange as $tanggal) {
                if ($tanggal->isFuture()) break;
                
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) continue;

                $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $tanggal)->whereDate('tanggal_selesai', '>=', $tanggal)->first()->tipe_minggu ?? 'Reguler';
                
                $jadwalUntukHariIni = $jadwalHariGuru->get($namaHari)
                    ->whereIn('tipe_blok', ['Setiap Minggu', $tipeMinggu])
                    ->sortBy('jam_ke');
                
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

                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];
                    $laporan = $guru->laporanHarian->where('jadwal_pelajaran_id', $jadwalPertamaId)->first();
                    if ($laporan) {
                        if ($laporan->status == 'Hadir') {
                            $totalHadir++;
                            if ($laporan->status_keterlambatan == 'Tepat Waktu') $totalTepatWaktu++;
                            if ($laporan->status_keterlambatan == 'Terlambat') $totalTerlambat++;
                        } 
                        elseif ($laporan->status == 'Sakit') $totalSakit++;
                        elseif ($laporan->status == 'Izin') $totalIzin++;
                        elseif ($laporan->status == 'DL') $totalDL++;
                        else $totalAlpa++;
                    } else {
                        $totalAlpa++;
                    }
                }
            }
            
            $persentaseHadir = ($totalSesiWajib > 0) ? ($totalHadir / $totalSesiWajib) * 100 : 0;
            $persentaseTepatWaktu = ($totalHadir > 0) ? ($totalTepatWaktu / $totalHadir) * 100 : 0;

            $laporanPerSesi->push([
                'name' => $guru->name, 'totalSesiWajib' => $totalSesiWajib,
                'totalHadir' => $totalHadir, 'totalTerlambat' => $totalTerlambat,
                'totalSakit' => $totalSakit, 'totalIzin' => $totalIzin,
                'totalAlpa' => $totalAlpa, 'totalDL' => $totalDL,
                'persentaseHadir' => round($persentaseHadir, 2) . '%',
                'persentaseTepatWaktu' => round($persentaseTepatWaktu, 2) . '%',
            ]);
        }
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
                $sheet->setCellValue('A2', 'PERIODE: ' . \Carbon\Carbon::parse($this->tanggalMulai)->isoFormat('D MMM Y') . ' s/d ' . \Carbon\Carbon::parse($this->tanggalSelesai)->isoFormat('D MMM Y'));
                $sheet->getStyle('A2')->getFont()->setItalic(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $headings = [
                    'Nama Guru', 'Total Sesi Wajib', 'Sesi Hadir', 'Sesi Terlambat',
                    'Sakit', 'Izin', 'Alpa', 'Dinas Luar', '% Kehadiran', '% Ketepatan Waktu'
                ];
                $sheet->fromArray($headings, null, 'A4');
                $sheet->getStyle('A4:J4')->getFont()->setBold(true);
                $sheet->getStyle('A4:J4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDEDED');

                $sheet->fromArray($this->laporanData->toArray(), null, 'A5');

                $lastRow = count($this->laporanData) + 4;
                $sheet->getStyle("A4:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("B4:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("I5:J{$lastRow}")->getFont()->setBold(true);
                
                $sheet->getColumnDimension('A')->setAutoSize(true);
                foreach (range('B', 'J') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}