<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPelajaran extends Model
{
    use HasFactory;
    protected $table = 'jadwal_pelajaran'; // <-- Tambahkan ini
    protected $guarded = []; // <-- Tambahkan ini
    
    // (Ini penting untuk query di controller)
    public function dataGuru()
    
    {
        return $this->belongsTo(DataGuru::class, 'data_guru_id');
    }
}