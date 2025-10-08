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
        $table->foreignId('piket_user_id')->constrained('users')->comment('ID Guru Piket yang melakukan aksi');
        $table->foreignId('guru_user_id')->constrained('users')->comment('ID Guru yang statusnya diubah');
        $table->date('tanggal');
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
