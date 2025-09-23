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
        // 1. Peta Hari & Waktu
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $today = now();
        $hariIni = $hariMap[$today->format('l')]; // "Senin"
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang'; 

        // 2. Login Lock (Ini sudah benar)
        $userBolehPiket = JadwalPiket::where('hari', $hariIni)
                                    ->where('sesi', $sesiSekarang)
                                    ->where('user_id', Auth::id())
                                    ->exists();
        
        // (Admin boleh tembus untuk testing)
        if (Auth::user()->role != 'admin' && !$userBolehPiket) { 
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect('/login')->withErrors([
                'username' => 'Akses ditolak. Anda tidak terjadwal piket untuk hari/sesi ini.'
            ]);
        }

        // ========================================================
        // ## 3. LOGIKA DASHBOARD (YANG SUDAH DIPERBAIKI) ##
        // ========================================================

        // 3a. Cari Tipe Minggu (Blok)
        $tipeMinggu = KalenderBlok::where('tanggal_mulai', '<=', $today)
                                  ->where('tanggal_selesai', '>=', $today)
                                  ->first();

        // 3b. Tentukan Blok Valid (Perbaikan Bug Mismatch)
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') {
                $blokValid[] = 'Hanya Minggu 1';
            } elseif ($tipeMinggu->tipe_minggu == 'Minggu 2') {
                $blokValid[] = 'Hanya Minggu 2';
            }
        }

        // 3c. Query "Daftar Guru Wajib Hadir" (HANYA BERDASARKAN HARI)
        // KITA HAPUS FILTER 'jam_ke' DARI SINI
        $jadwalWajib = JadwalPelajaran::where('hari', $hariIni)
                            ->whereIn('tipe_blok', $blokValid)
                            ->with('dataGuru')
                            ->get();
        
        $guruWajibHadir = $jadwalWajib->pluck('dataGuru')->unique('id')->sortBy('nama_guru');

         $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('data_guru_id');

        // 4. Tampilkan View
        return view('piket.dashboard', [
            'guruWajibHadir' => $guruWajibHadir,
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
             'laporanHariIni' => $laporanHariIni
            // 'jamKeSekarang' sudah kita hapus karena tidak relevan di sini
        ]);
    }
}