<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // ==========================================================
        // == REVISI BARU: Nonaktifkan Cek Foreign Key ==
        // ==========================================================
        Schema::disableForeignKeyConstraints();

        Schema::table('laporan_harian', function (Blueprint $table) {
            
            // 1. Hapus unique key lama yang salah
            // (Nama key 'laporan_harian_user_id_jadwal_pelajaran_id_unique' diambil dari error Anda)
            try {
                $table->dropUnique('laporan_harian_user_id_jadwal_pelajaran_id_unique');
            } catch (\Exception $e) {
                // Lanjutkan jika index tidak ada (misalnya sudah terhapus)
            }
            
            // 2. Tambahkan unique key baru yang benar
            // Ini akan memastikan absensi unik berdasarkan TANGGAL dan JADWAL
            $table->unique(['tanggal', 'jadwal_pelajaran_id'], 'laporan_harian_tanggal_jadwal_unique');
        });

        // 3. Aktifkan kembali Cek Foreign Key
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        
        Schema::table('laporan_harian', function (Blueprint $table) {
            // Logika untuk mengembalikan jika terjadi rollback
            
            // 1. Hapus Unique Key baru
            $table->dropUnique('laporan_harian_tanggal_jadwal_unique');
            
            // 2. Tambahkan Unique Key lama
            $table->unique(['user_id', 'jadwal_pelajaran_id'], 'laporan_harian_user_id_jadwal_pelajaran_id_unique');
            
        });

        Schema::enableForeignKeyConstraints();
    }
};

