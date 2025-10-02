<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPelajaran extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan.
     */
    protected $table = 'jadwal_pelajaran';

    /**
     * Mass assignment.
     */
    protected $guarded = [];

    /**
     * ==========================================================
     * ## INI RELASI YANG HILANG / PERLU DIPERBAIKI ##
     * ==========================================================
     * Mendefinisikan bahwa satu JadwalPelajaran dimiliki oleh satu User.
     */
    public function user()
    {
        // Sambungkan ke model User, foreign key-nya adalah 'user_id'
        return $this->belongsTo(User::class, 'user_id');
    }
}