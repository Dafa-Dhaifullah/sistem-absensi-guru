<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverrideLog extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Relasi untuk mengambil nama Guru Piket
    public function piket() {
        return $this->belongsTo(User::class, 'piket_user_id');
    }

    // Relasi untuk mengambil nama Guru yang diabsenkan
    public function guru() {
        return $this->belongsTo(User::class, 'guru_user_id');
    }
}