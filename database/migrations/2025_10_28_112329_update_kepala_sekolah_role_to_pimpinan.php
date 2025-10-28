<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah semua role 'kepala_sekolah' menjadi 'pimpinan'
        DB::table('users')
            ->where('role', 'kepala_sekolah')
            ->update(['role' => 'pimpinan']);
    }

    public function down(): void
    {
        // Logika untuk rollback jika diperlukan
        DB::table('users')
            ->where('role', 'pimpinan')
            ->update(['role' => 'kepala_sekolah']);
    }
};