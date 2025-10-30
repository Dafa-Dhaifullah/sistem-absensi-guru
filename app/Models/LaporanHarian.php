<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanHarian extends Model
{
    use HasFactory;

    protected $table = 'laporan_harian';
    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Mendefinisikan bahwa LaporanHarian dimiliki oleh satu User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * 
     * Mendefinisikan bahwa LaporanHarian dimiliki oleh satu JadwalPelajaran.
     */
    public function jadwalPelajaran()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_pelajaran_id');
    }

    /**
     * (Opsional) Relasi untuk mengambil data piket yang melakukan override
     */
    public function piket()
    {
        return $this->belongsTo(User::class, 'diabsen_oleh');
    }
}