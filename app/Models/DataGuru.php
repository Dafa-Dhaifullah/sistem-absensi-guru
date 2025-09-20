<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataGuru extends Model
{
    use HasFactory;
    protected $table = 'data_guru'; // <-- Tambahkan ini
    protected $guarded = []; // <-- Tambahkan ini
}