<?php

namespace App\Exports;

use App\Models\LogbookPiket;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize; // Untuk lebar kolom otomatis

class ArsipLogbookExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
    * Mengambil data dari database.
    */
    public function query()
    {
        // Ambil semua data logbook, diurutkan dari yang terbaru
        return LogbookPiket::query()->latest('tanggal');
    }

    /**
     * Ini akan menjadi baris header di file Excel.
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Kejadian Penting',
            'Tindak Lanjut',
        ];
    }

    /**
     * Memetakan data dari setiap baris.
     * @param LogbookPiket $logbook
     */
    public function map($logbook): array
    {
        return [
            // Format tanggal agar mudah dibaca
            \Carbon\Carbon::parse($logbook->tanggal)->isoFormat('dddd, D MMMM YYYY'),
            $logbook->kejadian_penting,
            $logbook->tindak_lanjut,
        ];
    }
}