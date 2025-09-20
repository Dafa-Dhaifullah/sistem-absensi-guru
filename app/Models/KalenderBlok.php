<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KalenderBlok extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang HARUS digunakan oleh model ini.
     */
    protected $table = 'kalender_blok';
    
    // (Pastikan $fillable atau $guarded juga diisi jika Anda menggunakan create())
    protected $guarded = []; 
}