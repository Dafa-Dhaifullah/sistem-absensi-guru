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
    Schema::create('master_hari_kerja', function (Blueprint $table) {
        $table->id();
        $table->string('nama_hari')->unique(); // Senin, Selasa, ...
        $table->boolean('is_aktif')->default(false); // Aktif (true) atau Libur (false)
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_hari_kerja');
    }
};
