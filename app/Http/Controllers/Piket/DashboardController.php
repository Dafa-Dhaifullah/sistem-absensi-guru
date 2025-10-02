<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPiket;
use App\Models\LaporanHarian;
use App\Models\User; // Menggunakan model User

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Peta Hari & Waktu (Ini sudah benar)
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $today = now('Asia/Jakarta');
        $hariIni = $hariMap[$today->format('l')];
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';

        // 2. Login Lock (Ini sudah benar)
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

        // 3. Logika Dashboard (Direvisi)
        $tipeMinggu = KalenderBlok::where('tanggal_mulai', '<=', $today)
                                  ->where('tanggal_selesai', '>=', $today)
                                  ->first();
        
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }

        // REVISI: Ambil user_id, bukan data_guru_id
        $jadwalGuruIds = JadwalPelajaran::where('hari', $hariIni)
                            ->whereIn('tipe_blok', $blokValid)
                            ->pluck('user_id')
                            ->unique();

        // REVISI: Ambil data dari User, bukan DataGuru
        $guruWajibHadir = User::whereIn('id', $jadwalGuruIds)
                            ->where('role', 'guru') // Pastikan hanya guru umum
                            ->orderBy('name', 'asc')
                            ->get();

        // REVISI: keyBy 'user_id'
        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('user_id');

        // 4. Tampilkan View
        return view('piket.dashboard', [
            'guruWajibHadir' => $guruWajibHadir,
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
            'laporanHariIni' => $laporanHariIni
        ]);
    }
}
