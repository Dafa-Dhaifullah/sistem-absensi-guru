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
       Schema::create('laporan_harian', function (Blueprint $table) {
        $table->id();
        $table->date('tanggal');
        $table->foreignId('data_guru_id')->constrained('data_guru')->onDelete('cascade');
        $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alpa', 'DL']);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_harians');
    }
};
