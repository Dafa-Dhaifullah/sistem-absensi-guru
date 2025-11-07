<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterHariKerja extends Model
{
    use HasFactory;

    protected $table = 'master_hari_kerja';
    protected $guarded = [];
    public $timestamps = false;
}