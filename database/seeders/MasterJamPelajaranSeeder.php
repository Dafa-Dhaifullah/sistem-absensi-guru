<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterJamPelajaran;

class MasterJamPelajaranSeeder extends Seeder
{
    public function run(): void
    {
        // Kosongkan tabel dulu
        MasterJamPelajaran::truncate();

        // 1. Data Jam Hari SENIN (Sesuai info Anda, ini beda)
        $senin = [
            ['hari' => 'Senin', 'jam_ke' => 1, 'jam_mulai' => '07:15', 'jam_selesai' => '08:00'], 
            ['hari' => 'Senin', 'jam_ke' => 2, 'jam_mulai' => '08:00', 'jam_selesai' => '08:45'], 
            ['hari' => 'Senin', 'jam_ke' => 3, 'jam_mulai' => '08:45', 'jam_selesai' => '09:30'], 
            ['hari' => 'Senin', 'jam_ke' => 4, 'jam_mulai' => '09:30', 'jam_selesai' => '10:15'],
            ['hari' => 'Senin', 'jam_ke' => 5, 'jam_mulai' => '10:35', 'jam_selesai' => '11:20'],
            ['hari' => 'Senin', 'jam_ke' => 6, 'jam_mulai' => '11:20', 'jam_selesai' => '12:05'],
            ['hari' => 'Senin', 'jam_ke' => 7, 'jam_mulai' => '12:35', 'jam_selesai' => '13:20'],
            ['hari' => 'Senin', 'jam_ke' => 8, 'jam_mulai' => '13:20', 'jam_selesai' => '14:05'],
            ['hari' => 'Senin', 'jam_ke' => 9, 'jam_mulai' => '14:05', 'jam_selesai' => '14:50'],
            ['hari' => 'Senin', 'jam_ke' => 10, 'jam_mulai' => '14:50', 'jam_selesai' => '15:35'],
        ];

        // Template untuk hari-hari lain
        $template_jam_normal = [
            ['jam_ke' => 1, 'jam_mulai' => '06:30', 'jam_selesai' => '07:15'], 
            ['jam_ke' => 2, 'jam_mulai' => '07:15', 'jam_selesai' => '08:00'], 
            ['jam_ke' => 3, 'jam_mulai' => '08:00', 'jam_selesai' => '08:45'],
            ['jam_ke' => 4, 'jam_mulai' => '08:45', 'jam_selesai' => '09:30'],
            ['jam_ke' => 5, 'jam_mulai' => '09:50', 'jam_selesai' => '10:35'],
            ['jam_ke' => 6, 'jam_mulai' => '10:35', 'jam_selesai' => '11:20'],
            ['jam_ke' => 7, 'jam_mulai' => '11:20', 'jam_selesai' => '12:05'],
            ['jam_ke' => 8, 'jam_mulai' => '12:35', 'jam_selesai' => '13:20'],
            ['jam_ke' => 9, 'jam_mulai' => '13:20', 'jam_selesai' => '14:05'],
            ['jam_ke' => 10, 'jam_mulai' => '14:05', 'jam_selesai' => '14:50'], 
        ];

        // 3. Data Jam Hari JUMAT (6 Jam)
        $jumat = [
            ['hari' => 'Jumat', 'jam_ke' => 1, 'jam_mulai' => '06:30', 'jam_selesai' => '07:15'], 
            ['hari' => 'Jumat', 'jam_ke' => 2, 'jam_mulai' => '07:15', 'jam_selesai' => '08:00'],
            ['hari' => 'Jumat', 'jam_ke' => 3, 'jam_mulai' => '08:00', 'jam_selesai' => '08:45'],
            ['hari' => 'Jumat', 'jam_ke' => 4, 'jam_mulai' => '08:45', 'jam_selesai' => '09:30'],
            ['hari' => 'Jumat', 'jam_ke' => 5, 'jam_mulai' => '09:30', 'jam_selesai' => '10:15'],
            ['hari' => 'Jumat', 'jam_ke' => 6, 'jam_mulai' => '10:15', 'jam_selesai' => '11:00'], 
            // Tambahkan jam "kosong" agar totalnya 10, untuk konsistensi
            ['hari' => 'Jumat', 'jam_ke' => 7, 'jam_mulai' => '11:00', 'jam_selesai' => '11:01'], 
            ['hari' => 'Jumat', 'jam_ke' => 8, 'jam_mulai' => '11:01', 'jam_selesai' => '11:02'], 
            ['hari' => 'Jumat', 'jam_ke' => 9, 'jam_mulai' => '11:02', 'jam_selesai' => '11:03'], 
            ['hari' => 'Jumat', 'jam_ke' => 10, 'jam_mulai' => '11:03', 'jam_selesai' => '11:04'], 
        ];

        // --- Proses Memasukkan ke Database ---

        // Masukkan data Senin
        foreach ($senin as $jam) {
            MasterJamPelajaran::create($jam);
        }

        // ==========================================================
        // ## REVISI DI SINI: Tambahkan Sabtu & Minggu ##
        // Masukkan data Selasa, Rabu, Kamis, SABTU, MINGGU (menggunakan template)
        // ==========================================================
        foreach (['Selasa', 'Rabu', 'Kamis', 'Sabtu', 'Minggu'] as $namaHari) {
            foreach ($template_jam_normal as $jam) {
                MasterJamPelajaran::create([
                    'hari' => $namaHari,
                    'jam_ke' => $jam['jam_ke'],
                    'jam_mulai' => $jam['jam_mulai'],
                    'jam_selesai' => $jam['jam_selesai'],
                ]);
            }
        }

        // Masukkan data Jumat
        foreach ($jumat as $jam) {
            MasterJamPelajaran::create($jam);
        }
    }
}