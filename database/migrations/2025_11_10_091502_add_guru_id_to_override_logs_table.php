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
    Schema::table('override_logs', function (Blueprint $table) {
        $table->foreignId('guru_id')
              ->nullable()
              ->constrained('users') // Merujuk ke tabel 'users'
              ->onDelete('set null') // Jika guru dihapus, log tetap ada
              ->after('jadwal_pelajaran_id'); // Posisi di database
    });
}

public function down(): void
{
    Schema::table('override_logs', function (Blueprint $table) {
        $table->dropForeign(['guru_id']);
        $table->dropColumn('guru_id');
    });
}
};
