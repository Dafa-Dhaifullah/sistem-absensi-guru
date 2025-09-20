<?php

namespace App\Models;
// ...
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPiket extends Model
{
    use HasFactory;
    protected $table = 'jadwal_piket'; // <-- Tambahkan ini
    protected $guarded = []; // <-- Tambahkan ini

    // (Opsional, tapi bagus untuk relasi)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}