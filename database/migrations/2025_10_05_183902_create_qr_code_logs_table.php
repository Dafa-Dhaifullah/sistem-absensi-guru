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
    Schema::create('qr_code_logs', function (Blueprint $table) {
        $table->id();
        $table->text('token');
        $table->foreignId('dibuat_oleh')->nullable()->constrained('users');
        $table->string('status')->default('Menunggu'); // Menunggu, Di-scan, Kedaluwarsa
        $table->integer('jumlah_scan')->default(0);
        $table->timestamp('waktu_kadaluarsa');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_code_logs');
    }
};
