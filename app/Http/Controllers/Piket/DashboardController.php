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

        // ==========================================================
        // ## REVISI UTAMA: Logika Keamanan (Login Lock) ##
        // ==========================================================
        // Cek apakah pengguna yang sedang login ada di jadwal piket untuk hari dan sesi ini
        $isPiket = JadwalPiket::where('hari', $hariIni)
                                ->where('sesi', $sesiSekarang)
                                ->where('user_id', Auth::id())
                                ->exists();
        
        // JIKA PENGGUNA BUKAN PIKET AKTIF (dan bukan admin yang boleh tembus),
        // alihkan ke dasbor guru biasa dengan pesan error.
        if (Auth::user()->role != 'admin' && !$isPiket) {
            return redirect()->route('guru.dashboard')->withErrors('Anda tidak memiliki jadwal piket untuk sesi ini.');
        }
        // ==========================================================


        $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)
                                  ->whereDate('tanggal_selesai', '>=', $today)
                                  ->first();
        
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }

        // Ambil ID guru yang memiliki jadwal hari ini
        $jadwalGuruIds = JadwalPelajaran::where('hari', $hariIni)
                            ->whereIn('tipe_blok', $blokValid)
                            ->pluck('user_id')
                            ->unique();

        // Ambil data lengkap guru berdasarkan ID di atas, pastikan rolenya 'guru'
        $guruWajibHadir = User::whereIn('id', $jadwalGuruIds)
                            ->where('role', 'guru')
                            ->orderBy('name', 'asc')
                            ->get();

        // Ambil SEMUA jadwal pelajaran untuk hari ini (untuk mencari jam pertama di view)
        $semuaJadwalHariIni = JadwalPelajaran::where('hari', $hariIni)
                            ->whereIn('tipe_blok', $blokValid)
                            ->get();
        
        // Ambil Master Jam Pelajaran untuk hari ini
        $masterJamHariIni = MasterJamPelajaran::where('hari', $hariIni)->get()->keyBy('jam_ke');
        
        // Ambil Laporan Harian yang sudah ada
        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('user_id');

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

