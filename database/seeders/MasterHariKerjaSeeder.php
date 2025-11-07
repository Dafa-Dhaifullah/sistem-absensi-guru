<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterHariKerja;

class MasterHariKerjaSeeder extends Seeder
{
    public function run(): void
    {
        $hari = [
            ['nama_hari' => 'Senin', 'is_aktif' => true],
            ['nama_hari' => 'Selasa', 'is_aktif' => true],
            ['nama_hari' => 'Rabu', 'is_aktif' => true],
            ['nama_hari' => 'Kamis', 'is_aktif' => true],
            ['nama_hari' => 'Jumat', 'is_aktif' => true],
            ['nama_hari' => 'Sabtu', 'is_aktif' => false], 
            ['nama_hari' => 'Minggu', 'is_aktif' => false],
        ];

        foreach ($hari as $h) {
            MasterHariKerja::firstOrCreate($h);
        }
    }
}