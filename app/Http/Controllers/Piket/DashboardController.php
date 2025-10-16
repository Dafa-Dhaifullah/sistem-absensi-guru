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
        
        if ($libur = \App\Models\HariLibur::where('tanggal', $today->toDateString())->first()) {
            return view('piket.libur', ['keterangan' => $libur->keterangan]);
        }
        
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $hariIni = $hariMap[$today->format('l')];
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';

        if (Auth::user()->role != 'admin' && !JadwalPiket::where('hari', $hariIni)->where('sesi', $sesiSekarang)->where('user_id', Auth::id())->exists()) {
            return redirect()->route('guru.dashboard')->withErrors('Anda tidak memiliki jadwal piket untuk sesi ini.');
        }

        $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)->whereDate('tanggal_selesai', '>=', $today)->first();
        
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }

        // --- REVISI LOGIKA PENGAMBILAN DATA ---
        
        // 1. Ambil SEMUA jadwal pelajaran untuk hari ini
        $semuaJadwalHariIni = JadwalPelajaran::where('hari', $hariIni)
                            ->whereIn('tipe_blok', $blokValid)
                            ->with('user')
                            ->orderBy('user_id')
                            ->orderBy('jam_ke', 'asc')
                            ->get();

        // 2. Ambil daftar guru unik dari jadwal tersebut, diurutkan berdasarkan nama
        $guruWajibHadir = $semuaJadwalHariIni->pluck('user')
                            ->where('role', 'guru')
                            ->unique('id')
                            ->sortBy('name');

        // 3. Ambil Master Jam Pelajaran untuk hari ini
        $masterJamHariIni = MasterJamPelajaran::where('hari', $hariIni)->get()->keyBy('jam_ke');
        
        // 4. Ambil semua Laporan Harian untuk hari ini, diindeks berdasarkan jadwal_pelajaran_id
        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('jadwal_pelajaran_id');

        return view('piket.dashboard', [
            'guruWajibHadir' => $guruWajibHadir,
            'semuaJadwalHariIni' => $semuaJadwalHariIni,
            'masterJamHariIni' => $masterJamHariIni,
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
            'laporanHariIni' => $laporanHariIni
        ]);
    }
}

