<?php

namespace App\Models;
// ...
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanHarian extends Model
{
    use HasFactory;
    protected $table = 'laporan_harian'; // <-- Tambahkan ini
    protected $guarded = []; // <-- Tambahkan ini

    public function user()
    {
        // Hubungkan ke model User melalui foreign key 'user_id'
        return $this->belongsTo(User::class, 'user_id');
    }
}