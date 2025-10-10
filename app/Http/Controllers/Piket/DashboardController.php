<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPiket;
use App\Models\LaporanHarian;
use App\Models\MasterJamPelajaran; 
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now('Asia/Jakarta');
        
        $hariLibur = \App\Models\HariLibur::where('tanggal', $today->toDateString())->first();
        if ($hariLibur) {
            return view('piket.libur', ['keterangan' => $hariLibur->keterangan]);
        }
        
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $hariIni = $hariMap[$today->format('l')];
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';

        $userBolehPiket = JadwalPiket::where('hari', $hariIni)
                                    ->where('sesi', $sesiSekarang)
                                    ->where('user_id', Auth::id())
                                    ->exists();
        
        if (Auth::user()->role != 'admin' && !$userBolehPiket) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->withErrors(['username' => 'Akses ditolak. Anda tidak terjadwal piket untuk hari/sesi ini.']);
        }

        $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)
                                  ->whereDate('tanggal_selesai', '>=', $today)
                                  ->first();
        
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }

        // ==========================================================
        // ## REVISI LOGIKA PENGAMBILAN DATA ##
        // ==========================================================
        
        // 1. Ambil SEMUA jadwal pelajaran untuk hari ini
        $semuaJadwalHariIni = JadwalPelajaran::where('hari', $hariIni)
                            ->whereIn('tipe_blok', $blokValid)
                            ->with('user') // Eager load relasi user
                            ->get();

        // 2. Ambil daftar guru unik dari jadwal tersebut
        $guruWajibHadir = $semuaJadwalHariIni->pluck('user')
                            ->where('role', 'guru')
                            ->unique('id')
                            ->sortBy('name');

        // 3. Ambil Master Jam Pelajaran untuk hari ini agar mudah dicari
        $masterJamHariIni = MasterJamPelajaran::where('hari', $hariIni)->get()->keyBy('jam_ke');
        
        // ==========================================================


        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('user_id');

        return view('piket.dashboard', [
            'guruWajibHadir' => $guruWajibHadir,
            'semuaJadwalHariIni' => $semuaJadwalHariIni, // <-- Kirim data baru
            'masterJamHariIni' => $masterJamHariIni, // <-- Kirim data baru
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
            'laporanHariIni' => $laporanHariIni
        ]);
    }
}

