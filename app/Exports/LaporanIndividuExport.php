<?php

namespace App\Exports;

use App\Models\LaporanHarian;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LaporanIndividuExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $guruId;
    protected $tanggalMulai;
    protected $tanggalSelesai;

    public function __construct(int $guruId, string $tanggalMulai, string $tanggalSelesai)
    {
        $this->guruId = $guruId;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
    }

    public function query()
    {
        // Query tidak berubah
        return LaporanHarian::query()
            ->where('user_id', $this->guruId)
            ->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai])
            ->orderBy('tanggal', 'asc');
    }

    public function headings(): array
    {
        // Header tidak berubah
        return [
            'Tanggal',
            'Hari',
            'Status Kehadiran',
        ];
    }

    /**
     * @param LaporanHarian $laporan
     */
    public function map($laporan): array
    {
        // REVISI: Tambahkan locale('id_ID') untuk format hari
        return [
            \Carbon\Carbon::parse($laporan->tanggal)->locale('id_ID')->isoFormat('D MMMM YYYY'),
            \Carbon\Carbon::parse($laporan->tanggal)->locale('id_ID')->isoFormat('dddd'),
            $laporan->status,
        ];
    }
}
