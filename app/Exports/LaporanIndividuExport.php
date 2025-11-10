<?php

namespace App\Exports;

use App\Models\LaporanHarian;
use App\Models\User;
use App\Models\JadwalPelajaran;
use App\Models\HariLibur;
use App\Models\KalenderBlok;
use App\Models\MasterHariKerja;
use Maatwebsite\Excel\Concerns\FromCollection; 
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Carbon; // Import Carbon

class LaporanIndividuExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $guruId;
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $namaGuru;

    public function __construct(int $guruId, string $tanggalMulai, string $tanggalSelesai)
    {
        $this->guruId = $guruId;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->namaGuru = User::findOrFail($guruId)->name;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // REVISI: Logika ini disamakan dengan LaporanController@individu
        $guruTerpilih = User::findOrFail($this->guruId);
        $tanggalMulai = Carbon::parse($this->tanggalMulai);
        $tanggalSelesai = Carbon::parse($this->tanggalSelesai);
        $today = now('Asia/Jakarta')->startOfDay();

        $laporanTersimpan = LaporanHarian::where('user_id', $this->guruId)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->with('piket')
            ->get()
            ->keyBy(function ($item) {
                return $item->tanggal->toDateString() . '_' . $item->jadwal_pelajaran_id;
            });

        $jadwalGuru = $guruTerpilih->jadwalPelajaran->groupBy('hari');
        $hariLibur = HariLibur::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->pluck('tanggal')->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());
        
        // ==========================================================
        // ## PERBAIKAN N+1 QUERY ##
        // ==========================================================
        $kalenderBlokPeriodeIni = KalenderBlok::where(function ($query) use ($tanggalMulai, $tanggalSelesai) {
            $query->where('tanggal_mulai', '<=', $tanggalSelesai)
                  ->where('tanggal_selesai', '>=', $tanggalMulai);
        })->get();
        // ==========================================================

        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
            
        $laporanFinal = collect();

        foreach (Carbon::parse($tanggalMulai)->toPeriod($tanggalSelesai) as $tanggal) {
            if ($tanggal->isFuture()) break;
            $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
              if (!$hariKerjaAktif->contains($namaHari)) {
                continue;
            }
            if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalGuru->has($namaHari)) {
                continue;
            }

            // ==========================================================
            // ## PERBAIKAN LOGIKA PENCARIAN BLOK (dari N+1) ##
            // ==========================================================
            $kalenderBlokHariIni = $kalenderBlokPeriodeIni->firstWhere(function ($blok) use ($tanggal) {
                $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                return $tanggal->gte($mulai) && $tanggal->lte($selesai);
            });
            $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
            // ==========================================================
            
            // ==========================================================
            // ## PERBAIKAN LOGIKA FILTER BLOK (str_contains) ##
            // ==========================================================
            $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu)); 
            $jadwalMentahHariIni = $jadwalGuru->get($namaHari);

            $jadwalHariIni = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                $tipeBlokJadwal = $jadwal->tipe_blok;
                if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                if ($tipeBlokJadwal == $tipeMinggu) return true;
                return false;
            })->sortBy('jam_ke');
            // ==========================================================


            // --- LOGIKA PENGELOMPOKKAN BLOK ---
            $tempBlock = null;
            $jadwalBlok = collect();
            foreach ($jadwalHariIni as $jadwal) {
                if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                    $tempBlock['jadwal_ids'][] = $jadwal->id; $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                } else {
                    if ($tempBlock) $jadwalBlok->push($tempBlock);
                    $tempBlock = ['jadwal_ids' => [$jadwal->id], 'jam_pertama' => $jadwal->jam_ke, 'jam_terakhir' => $jadwal->jam_ke, 'kelas' => $jadwal->kelas];
                }
            }
            if ($tempBlock) $jadwalBlok->push($tempBlock);
            // --- AKHIR LOGIKA BLOK ---

            foreach ($jadwalBlok as $blok) {
                $jadwalPertamaId = $blok['jadwal_ids'][0];
                $key = $tanggal->toDateString() . '_' . $jadwalPertamaId;
                $laporan = $laporanTersimpan->get($key);

                $logSesi = new \stdClass();
                $logSesi->tanggal = $tanggal->toDateString();
                $logSesi->jam_pertama = $blok['jam_pertama'];
                $logSesi->jam_terakhir = $blok['jam_terakhir'];
                $logSesi->kelas = $blok['kelas'];
                if ($laporan) {
                    $logSesi->status = $laporan->status; $logSesi->status_keterlambatan = $laporan->status_keterlambatan;
                    $logSesi->jam_absen = $laporan->jam_absen; $logSesi->diabsen_oleh = $laporan->diabsen_oleh;
                    $logSesi->keterangan_piket = $laporan->keterangan_piket; $logSesi->piket = $laporan->piket;
                } else {
                    $logSesi->status = 'Alpa'; $logSesi->status_keterlambatan = null;
                    $logSesi->jam_absen = null; $logSesi->diabsen_oleh = null;
                    $logSesi->keterangan_piket = null; $logSesi->piket = null;
                }
                $laporanFinal->push($logSesi);
            }
        }
        return $laporanFinal;
    }

    public function headings(): array
    {
        return [
            ['Laporan Kehadiran Individu (per Jadwal Mengajar)'],
            ['Guru: ' . $this->namaGuru],
            ['Periode: ' . Carbon::parse($this->tanggalMulai)->isoFormat('D MMM Y') . ' s/d ' . Carbon::parse($this->tanggalSelesai)->isoFormat('D MMM Y')],
            [], 
            [
                'Tanggal',
                'Hari',
                'Jadwal (Jam Ke)',
                'Kelas',
                'Status Kehadiran',
                'Keterangan',
                'Jam Absen',
                'Diabsen Oleh',
                'Keterangan Piket',
            ]
        ];
    }

    /**
     * @param \stdClass $laporan
     */
    public function map($laporan): array
    {
        $status = $laporan->status;
        $keterangan = $laporan->status_keterlambatan;
        if ($status == 'Hadir') {
            if ($laporan->status_keterlambatan == 'Terlambat') {
                $status = 'Terlambat'; $keterangan = 'Hadir Terlambat';
            } else {
                $keterangan = 'Tepat Waktu';
            }
        }
        
        $diabsenOleh = '-';
        if ($laporan->diabsen_oleh) {
            $diabsenOleh = ($laporan->diabsen_oleh == $this->guruId) ? 'Mandiri (Selfie)' : ($laporan->piket->name ?? 'Piket');
        } elseif ($status == 'Alpa') {
            $diabsenOleh = 'Sistem (Alpa)';
        }

        return [
            Carbon::parse($laporan->tanggal)->isoFormat('D MMMM YYYY'),
            Carbon::parse($laporan->tanggal)->locale('id_ID')->isoFormat('dddd'),
            'Jam ' . $laporan->jam_pertama . ($laporan->jam_pertama != $laporan->jam_terakhir ? '-' . $laporan->jam_terakhir : ''),
            $laporan->kelas,
            $status,
            $keterangan,
            $laporan->jam_absen ? Carbon::parse($laporan->jam_absen)->format('H:i:s') : '-',
            $diabsenOleh,
            $laporan->keterangan_piket,
        ];
    }

   public function styles(Worksheet $sheet)
{
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->mergeCells('A2:I2');
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->mergeCells('A3:I3');
    $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->getStyle('A5:I5')->getFont()->setBold(true);
    $sheet->getStyle('A5:I5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDEDED');
    $sheet->getStyle('A5:I5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $lastRow = $sheet->getHighestRow();
    $sheet->getStyle("A6:I{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $sheet->getStyle("C6:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("G6:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // ===== A4 LANDSCAPE & FIT TO WIDTH =====
    $pageSetup = $sheet->getPageSetup();
    $pageSetup->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $pageSetup->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    $pageSetup->setFitToWidth(1)->setFitToHeight(0); // 1 halaman lebar, tinggi otomatis
    $pageSetup->setHorizontalCentered(true);

    // Margin agar muat rapi
    $sheet->getPageMargins()
        ->setTop(0.4)->setBottom(0.4)
        ->setLeft(0.4)->setRight(0.4);

    // Batasi area cetak agar pas A4
    $sheet->getPageSetup()->setPrintArea("A1:I{$lastRow}");
}

}
