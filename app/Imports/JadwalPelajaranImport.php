<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JadwalPelajaranImport implements ToCollection, WithHeadingRow
{
    /**
    * Method ini hanya akan mengumpulkan semua baris dari file Excel.
    * Semua logika validasi dan penyimpanan sekarang ada di controller.
    */
    public function collection(Collection $rows)
    {
        // Sengaja dikosongkan.
    }
}