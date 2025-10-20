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
        Schema::create('override_logs', function (Blueprint $table) {
            $table->id();
            // ID Guru Piket yang melakukan override
            $table->foreignId('piket_user_id')->constrained('users')->onDelete('cascade');

            // ID Jadwal Pelajaran spesifik yang di-override
            $table->foreignId('jadwal_pelajaran_id')->constrained('jadwal_pelajaran')->onDelete('cascade');

            $table->string('status_lama')->nullable();
            $table->string('status_baru');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('override_logs');
    }
};