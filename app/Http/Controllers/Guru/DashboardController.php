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
        $jadwalHariIni = collect();
        if ($user->dataGuru) {
            $jadwalHariIni = $user->dataGuru->jadwalPelajaran()
                ->where('hari', $hariIni)
                ->orderBy('jam_ke', 'asc')
                ->get();
        }

        // Cek laporan absensi hari ini
        $laporanHariIni = null;
        if ($user->dataGuru) {
            $laporanHariIni = LaporanHarian::where('data_guru_id', $user->dataGuru->id)
                ->whereDate('tanggal', $today->toDateString())
                ->first();
        }

        // Ambil data guru piket hari ini
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';
        $piketIds = JadwalPiket::where('hari', $hariIni)
                        ->where('sesi', $sesiSekarang)
                        ->pluck('user_id');
        $guruPiketHariIni = User::whereIn('id', $piketIds)
                            ->with('dataGuru')
                            ->get();

        return view('guru.dashboard', [
            'jadwalHariIni' => $jadwalHariIni,
            'laporanHariIni' => $laporanHariIni,
            'guruPiketHariIni' => $guruPiketHariIni,
            'sesiSekarang' => $sesiSekarang,
        ]);
    }
}