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
        Schema::create('logbook_piket', function (Blueprint $table) {
        $table->id();
        $table->date('tanggal')->unique(); // Hanya ada 1 logbook per tanggal
        $table->text('kejadian_penting')->nullable();
        $table->text('tindak_lanjut')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbook_pikets');
    }
};
