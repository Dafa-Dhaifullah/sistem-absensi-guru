<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterJamPelajaran extends Model
{
    use HasFactory;
    
    protected $table = 'master_jam_pelajaran';
    
    // Izinkan semua kolom diisi
    protected $guarded = [];

    public $timestamps = false;
}