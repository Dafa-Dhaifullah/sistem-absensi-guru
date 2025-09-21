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
    Schema::table('jadwal_pelajaran', function (Blueprint $table) {
        // Ubah tipe kolom 'jam_ke' dari string menjadi integer
        $table->integer('jam_ke')->change();
    });
}

public function down(): void
{
    Schema::table('jadwal_pelajaran', function (Blueprint $table) {
        // Perintah rollback (jika gagal)
        $table->string('jam_ke')->change();
    });
}
};
