<?php

namespace App\Exports;

use App\Models\LaporanHarian;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanIndividuExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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

    public function query()
    {
        // Query dari LaporanHarian, gabung (join) dengan jadwal
        return LaporanHarian::query()
            ->where('user_id', $this->guruId)
            ->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai])
            ->with(['jadwalPelajaran', 'piket']) // Ambil relasi jadwal dan user piket (jika ada)
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam_absen', 'asc');
    }

    public function headings(): array
    {
        // Tambah judul di atas header
        return [
            ['Laporan Kehadiran Individu (per Sesi Mengajar)'],
            ['Guru: ' . $this->namaGuru],
            ['Periode: ' . \Carbon\Carbon::parse($this->tanggalMulai)->isoFormat('D MMM Y') . ' s/d ' . \Carbon\Carbon::parse($this->tanggalSelesai)->isoFormat('D MMM Y')],
            [], // Baris kosong
            [
                'Tanggal',
                'Hari',
                'Sesi (Jam Ke)',
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
     * @param LaporanHarian $laporan
     */
    public function map($laporan): array
    {
        $status = $laporan->status;
        $keterangan = $laporan->status_keterlambatan;
        if ($status == 'Hadir' && $laporan->status_keterlambatan == 'Terlambat') {
            $status = 'Terlambat';
            $keterangan = 'Hadir Terlambat';
        }
        
        $diabsenOleh = '-';
        if ($laporan->diabsen_oleh) {
            // Cek jika diabsen oleh diri sendiri (mandiri)
            if ($laporan->diabsen_oleh == $laporan->user_id) {
                $diabsenOleh = 'Mandiri (Selfie)';
            } else {
                // Jika diabsen oleh orang lain (piket)
                $diabsenOleh = $laporan->piket->name ?? 'Piket';
            }
        }

        return [
            \Carbon\Carbon::parse($laporan->tanggal)->isoFormat('D MMMM YYYY'),
            \Carbon\Carbon::parse($laporan->tanggal)->locale('id_ID')->isoFormat('dddd'),
            $laporan->jadwalPelajaran->jam_ke ?? 'N/A',
            $laporan->jadwalPelajaran->kelas ?? 'N/A',
            $status,
            $keterangan,
            $laporan->jam_absen ? \Carbon\Carbon::parse($laporan->jam_absen)->format('H:i:s') : '-',
            $diabsenOleh,
            $laporan->keterangan_piket,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style untuk Judul (Baris 1-3)
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A3:I3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Style untuk Header Tabel (Baris 5)
        $sheet->getStyle('A5:I5')->getFont()->setBold(true);
        $sheet->getStyle('A5:I5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDEDED');
        $sheet->getStyle('A5:I5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Style untuk Data
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A6:I{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Rata tengah untuk beberapa kolom
        $sheet->getStyle("C6:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G6:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}