<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            // Hapus aturan unik yang lama (tanggal + user_id)
            $table->dropUnique(['tanggal', 'user_id']);

            // Tambahkan foreign key baru yang menunjuk ke jadwal pelajaran
            $table->foreignId('jadwal_pelajaran_id')
                  ->after('user_id')
                  ->constrained('jadwal_pelajaran')
                  ->onDelete('cascade');

            // Buat aturan unik yang baru: satu user hanya bisa absen sekali untuk satu jadwal
            $table->unique(['user_id', 'jadwal_pelajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->dropForeign(['jadwal_pelajaran_id']);
            $table->dropUnique(['user_id', 'jadwal_pelajaran_id']);
            $table->dropColumn('jadwal_pelajaran_id');
            $table->unique(['tanggal', 'user_id']);
        });
    }
};