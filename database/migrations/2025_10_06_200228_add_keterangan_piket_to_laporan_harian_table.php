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
    Schema::table('laporan_harian', function (Blueprint $table) {
        // Kolom untuk catatan dari guru piket saat override
        $table->string('keterangan_piket')->nullable()->after('diabsen_oleh');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            //
        });
    }
};
