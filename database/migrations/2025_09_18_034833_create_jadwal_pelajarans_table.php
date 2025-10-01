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
        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
        $table->id();
        // data_guru_id adalah ID dari tabel 'data_guru'
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('mata_pelajaran')->nullable();
        $table->string('kelas');
        $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
        $table->string('jam_ke'); // Misal: "1-2" atau "3-4"
        $table->enum('tipe_blok', ['Setiap Minggu', 'Hanya Minggu 1', 'Hanya Minggu 2']);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_pelajarans');
    }
};
