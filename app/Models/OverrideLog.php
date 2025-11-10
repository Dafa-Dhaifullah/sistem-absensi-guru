<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverrideLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Relasi untuk mengambil data GURU PIKET yang melakukan aksi.
     */
    public function piket()
    {
        return $this->belongsTo(User::class, 'piket_user_id');
    }

    /**
     * Relasi untuk mengambil data JADWAL PELAJARAN yang di-override.
     */
    public function jadwalPelajaran()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id');
    }
    public function guru()
{
    // Relasi langsung ke tabel 'users' melalui 'guru_id'
    return $this->belongsTo(User::class, 'guru_id');
}
}