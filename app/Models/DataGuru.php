<?php

namespace App\Models;

// Pastikan 3 'use' statement ini ada
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // (Opsional, tapi rapi)

class DataGuru extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang HARUS digunakan oleh model ini.
     */
    protected $table = 'data_guru';

    /**
     * Mass assignment (agar create() berfungsi)
     */
    protected $guarded = [];


    /**
     * ==========================================================
     * ## TAMBAHKAN FUNGSI RELASI DI BAWAH INI ##
     * ==========================================================
     */

    /**
     * Mendefinisikan bahwa satu DataGuru memiliki BANYAK LaporanHarian.
     */
    public function laporanHarian(): HasMany
    {
        // Argumen kedua adalah 'foreign_key' di tabel laporan_harian
        return $this->hasMany(LaporanHarian::class, 'data_guru_id');
    }

    /**
     * Mendefinisikan bahwa satu DataGuru memiliki BANYAK JadwalPelajaran.
     * (Ini akan kita butuhkan nanti)
     */
    public function jadwalPelajaran(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class, 'data_guru_id');
    }
}