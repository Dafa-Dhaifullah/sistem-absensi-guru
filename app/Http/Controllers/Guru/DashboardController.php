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
        
        // Ambil jadwal guru yang login HARI INI
        $jadwalHariIni = $user->jadwalPelajaran()
            ->where('hari', $hariIni)
            ->orderBy('jam_ke', 'asc')
            ->get();

        // Cek laporan absensi hari ini
        $laporanHariIni = LaporanHarian::where('user_id', $user->id)
            ->whereDate('tanggal', $today->toDateString())
            ->first();
        
        // ==========================================================
        // ## TAMBAHAN BARU: Hitung Total Ketidakhadiran Bulan Ini ##
        // ==========================================================
        $statusTidakHadir = ['Sakit', 'Izin', 'Alpa'];
        $totalTidakHadirBulanIni = LaporanHarian::where('user_id', $user->id)
            ->whereIn('status', $statusTidakHadir)
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->count();
        $batasAbsen = 4;
        // ==========================================================
            
        // Ambil data guru piket hari ini
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';
        $piketIds = JadwalPiket::where('hari', $hariIni)
                        ->where('sesi', $sesiSekarang)
                        ->pluck('user_id');
        $guruPiketHariIni = User::whereIn('id', $piketIds)
                            ->get();

        $isPiket = \App\Models\JadwalPiket::where('user_id', $user->id)
            ->where('hari', $hariIni)
            ->where('sesi', (now('Asia/Jakarta')->hour < 12 ? 'Pagi' : 'Siang'))
            ->exists();

        return view('guru.dashboard', [
            'sedangPiket' => $isPiket,
            'jadwalHariIni' => $jadwalHariIni,
            'laporanHariIni' => $laporanHariIni,
            'guruPiketHariIni' => $guruPiketHariIni,
            'sesiSekarang' => $sesiSekarang,
            'totalTidakHadir' => $totalTidakHadirBulanIni, // <-- Kirim data baru
            'batasAbsen' => $batasAbsen, // <-- Kirim data baru
        ]);
    }
}
