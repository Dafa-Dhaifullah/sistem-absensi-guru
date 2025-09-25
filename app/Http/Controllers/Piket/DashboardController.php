<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\KalenderBlok;
use App\Models\LaporanHarian;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPiket;
use App\Models\MasterJamPelajaran; // Biarkan ini, tidak apa-apa
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $userBolehPiket = \App\Models\JadwalPiket::where('hari', $hariIni)
                                    ->where('sesi', $sesiSekarang)
                                    ->where('user_id', \Illuminate\Support\Facades\Auth::id())
                                    ->exists();
        
        if (auth()->user()->role != 'admin' && !$userBolehPiket) {
            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->withErrors(['username' => 'Akses ditolak. Anda tidak terjadwal piket untuk hari/sesi ini.']);
        }

        // ========================================================
        // ## 3. LOGIKA DASHBOARD (VERSI PALING BARU & AMAN) ##
        // ========================================================

        // 3a. Ambil SEMUA guru yang wajib hadir hari ini (tanpa kecuali)
        $tipeMinggu = \App\Models\KalenderBlok::where('tanggal_mulai', '<=', $today)
                                  ->where('tanggal_selesai', '>=', $today)
                                  ->first();
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }

        // Ambil ID guru yang wajib hadir
        $guruWajibHadirIds = \App\Models\JadwalPelajaran::where('hari', $hariIni)
                            ->whereIn('tipe_blok', $blokValid)
                            ->pluck('data_guru_id')
                            ->unique();

        // Ambil data lengkap guru berdasarkan ID di atas
        $guruWajibHadir = \App\Models\DataGuru::whereIn('id', $guruWajibHadirIds)
                            ->orderBy('nama_guru', 'asc')
                            ->get();

        // 3b. Ambil SEMUA laporan yang sudah tersimpan hari ini
        $laporanHariIni = \App\Models\LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('data_guru_id');

        // ========================================================

        // 4. Tampilkan View
        return view('piket.dashboard', [
            'guruWajibHadir' => $guruWajibHadir,
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
            'laporanHariIni' => $laporanHariIni
        ]);
    }
}