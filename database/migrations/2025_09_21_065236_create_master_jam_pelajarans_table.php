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
    // Skema baru yang lebih akurat
    Schema::create('master_jam_pelajaran', function (Blueprint $table) {
        $table->id();
        // Kita buat spesifik per hari
        $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
        $table->integer('jam_ke'); // Jam ke-1, 2, 3, ... 10
        $table->time('jam_mulai'); // 06:30:00
        $table->time('jam_selesai'); // 07:15:00
        $table->timestamps();
    });
}

// (Method down() bisa Anda isi dengan: Schema::dropIfExists('master_jam_pelajaran');)

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_jam_pelajarans');
    }
};
