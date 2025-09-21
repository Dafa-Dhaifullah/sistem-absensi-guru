<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // <-- Import Model User
use Illuminate\Support\Facades\Hash; // <-- Import Hash

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat 1 user Admin
        User::create([
            'name' => 'Admin',
            'username' => 'admin', // Ini akan dipakai login
            'email' => 'admin@sekolah.id',
            'password' => Hash::make('admin123'), // Ganti dengan password aman
            'role' => 'admin',
            'email_verified_at' => now(), // (Opsional, agar terverifikasi)
        ]);

        // (Opsional) Buat 1 user Piket untuk tes
        User::create([
            'name' => 'Guru Piket 1',
            'username' => 'piket', // Ini akan dipakai login
            'email' => 'piket@sekolah.id',
            'password' => Hash::make('piket123'),
            'role' => 'piket',
            'email_verified_at' => now(),
        ]);
    }
}