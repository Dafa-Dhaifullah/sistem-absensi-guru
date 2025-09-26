<?php

namespace App\Exports;

use App\Models\LaporanHarian;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LaporanIndividuExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    // Siapkan properti untuk menampung filter
    protected $guruId;
    protected $tanggalMulai;
    protected $tanggalSelesai;

    // Buat constructor untuk menerima filter dari controller
    public function __construct(int $guruId, string $tanggalMulai, string $tanggalSelesai)
    {
        $this->guruId = $guruId;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
    }

    /**
    * Mengambil data dari database berdasarkan filter.
    */
    public function query()
    {
        return LaporanHarian::query()
            ->where('data_guru_id', $this->guruId)
            ->whereBetween('tanggal', [$this->tanggalMulai, $this->tanggalSelesai])
            ->orderBy('tanggal', 'asc');
    }

    /**
     * Ini akan menjadi baris header di file Excel.
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Hari',
            'Status Kehadiran',
        ];
    }

    /**
     * Memetakan data dari setiap baris.
     * @param LaporanHarian $laporan
     */
    public function map($laporan): array
    {
        return [
            // Format tanggal agar mudah dibaca
            \Carbon\Carbon::parse($laporan->tanggal)->isoFormat('D MMMM YYYY'),
            \Carbon\Carbon::parse($laporan->tanggal)->isoFormat('dddd'),
            $laporan->status,
        ];
    }
}