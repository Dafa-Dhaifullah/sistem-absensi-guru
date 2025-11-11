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
       User::firstOrCreate(
        ['email' => 'admin@sekolah.id'], // Cek berdasarkan email
        [
            'name' => 'Admin',
            'username' => 'admin', // Ini akan dipakai login
            'password' => Hash::make('smkn6garut'), // Ganti dengan password aman
            'role' => 'admin',
            'email_verified_at' => now(), 
        ]);

    }
}