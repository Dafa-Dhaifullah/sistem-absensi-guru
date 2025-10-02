<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LaporanHarian;
use App\Models\JadwalPiket;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = now('Asia/Jakarta');

        $hariMap = [
            'Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 
            'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 
            'Saturday'=>'Sabtu'
        ];
        $hariIni = $hariMap[$today->format('l')];

        // ==========================================================
        // ## REVISI DI SINI: Panggil relasi langsung dari $user ##
        // ==========================================================
        $jadwalHariIni = $user->jadwalPelajaran() // <-- TIDAK LAGI PAKAI ->dataGuru
            ->where('hari', $hariIni)
            ->orderBy('jam_ke', 'asc')
            ->get();

        // ==========================================================
        // ## REVISI DI SINI: Gunakan $user->id langsung ##
        // ==========================================================
        $laporanHariIni = LaporanHarian::where('user_id', $user->id) // <-- Ganti dari data_guru_id
            ->whereDate('tanggal', $today->toDateString())
            ->first();

        // Ambil data guru piket hari ini (logika ini sudah benar)
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';
        $piketIds = JadwalPiket::where('hari', $hariIni)
                        ->where('sesi', $sesiSekarang)
                        ->pluck('user_id');
        $guruPiketHariIni = User::whereIn('id', $piketIds)
                            ->get(); // Dihapus ->with('dataGuru') karena sudah di User

        return view('guru.dashboard', [
            'jadwalHariIni' => $jadwalHariIni,
            'laporanHariIni' => $laporanHariIni,
            'guruPiketHariIni' => $guruPiketHariIni,
            'sesiSekarang' => $sesiSekarang,
        ]);
    }
}