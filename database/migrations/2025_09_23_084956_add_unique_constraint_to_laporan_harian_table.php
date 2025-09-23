<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('laporan_harian', function (Blueprint $table) {
        // Tambahkan aturan: Kombinasi tanggal dan data_guru_id HARUS unik.
        $table->unique(['tanggal', 'data_guru_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            //
        });
    }
};
