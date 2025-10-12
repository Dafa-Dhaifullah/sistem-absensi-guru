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
            // HANYA JADWAL PIKET YANG AMAN UNTUK DI-CASCADE
            Schema::table('jadwal_piket', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')
                      ->references('id')->on('users')
                      ->onDelete('cascade'); // Jika user dihapus, jadwal piketnya ikut hilang
            });

        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('related_user_tables', function (Blueprint $table) {
            //
        });
    }
};
