<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPiket;
use App\Models\LaporanHarian;
use App\Models\User;
use App\Models\HariLibur; // <-- IMPORT BARU

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now('Asia/Jakarta');

        // ==========================================================
        // ## LOGIKA BARU: CEK HARI LIBUR ##
        // ==========================================================
        $hariLibur = HariLibur::where('tanggal', $today->toDateString())->first();
        if ($hariLibur) {
            // Jika hari ini libur, langsung tampilkan view libur
            return view('piket.libur', ['keterangan' => $hariLibur->keterangan]);
        }
        // ==========================================================
        
        // --- Sisa logika berjalan jika hari ini BUKAN hari libur ---
        
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

        $tipeMinggu = KalenderBlok::where('tanggal_mulai', '<=', $today)
                                  ->where('tanggal_selesai', '>=', $today)
                                  ->first();
        
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }

        $jadwalGuruIds = JadwalPelajaran::where('hari', $hariIni)
                            ->whereIn('tipe_blok', $blokValid)
                            ->pluck('user_id')
                            ->unique();

        $guruWajibHadir = User::whereIn('id', $jadwalGuruIds)
                            ->where('role', 'guru')
                            ->orderBy('name', 'asc')
                            ->get();

        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('user_id');

        return view('piket.dashboard', [
            'guruWajibHadir' => $guruWajibHadir,
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
            'laporanHariIni' => $laporanHariIni
        ]);
    }
}

