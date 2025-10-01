<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            // Tambahkan kolom-kolom baru setelah 'status'
            $table->time('jam_absen')->nullable()->after('status');
            $table->string('foto_selfie_path')->nullable()->after('jam_absen');
            $table->string('latitude_absen')->nullable()->after('foto_selfie_path');
            $table->string('longitude_absen')->nullable()->after('latitude_absen');
            $table->enum('status_keterlambatan', ['Tepat Waktu', 'Terlambat'])->nullable()->after('longitude_absen');
            $table->unsignedBigInteger('diabsen_oleh')->nullable()->after('status_keterlambatan'); // ID user yang menginput
        });
    }

    public function down(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            // Perintah untuk rollback jika terjadi kesalahan
            $table->dropColumn([
                'jam_absen',
                'foto_selfie_path',
                'latitude_absen',
                'longitude_absen',
                'status_keterlambatan',
                'diabsen_oleh'
            ]);
        });
    }
};