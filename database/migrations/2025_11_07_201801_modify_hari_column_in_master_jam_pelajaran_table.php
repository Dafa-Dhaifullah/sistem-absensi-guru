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
        Schema::table('master_jam_pelajaran', function (Blueprint $table) {
            // Ubah panjang string dari (misalnya) 5 menjadi 15
            // Angka 15 cukup aman untuk nama hari apapun.
            $table->string('hari', 15)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('master_jam_pelajaran', function (Blueprint $table) {
            // Opsional: kembalikan ke semula jika diperlukan
            // $table->string('hari', 5)->change();
         });
    }
};