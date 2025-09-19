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
        Schema::create('kalender_blok', function (Blueprint $table) {
        $table->id();
        $table->date('tanggal_mulai');
        $table->date('tanggal_selesai');
        $table->enum('tipe_minggu', ['Minggu 1', 'Minggu 2']);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kalender_bloks');
    }
};
