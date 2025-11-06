<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use App\Models\LaporanHarian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache; // Import Cache

class DisplayController extends Controller
{
    /**
     * Menampilkan jadwal realtime untuk monitor publik.
     * REVISI:
     * 1. Perbaiki logika filter 'tipe_blok' (str_contains)
     * 2. Perbaiki logika 'keyBy' dari user_id ke jadwal_pelajaran_id
     */
    public function jadwalRealtime()
    {
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $today = now('Asia/Jakarta');
        $hariIni = $hariMap[$today->format('l')]; 
        $jamSekarang = $today->toTimeString();

        // Gunakan cache agar tidak query database setiap 60 detik
        $tipeMingguObj = Cache::remember('kalender_blok_today', 60, function () use ($today) {
            return KalenderBlok::whereDate('tanggal_mulai', '<=', $today)
                                     ->whereDate('tanggal_selesai', '>=', $today)
                                     ->first();
        });

        $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                            ->where('jam_mulai', '<=', $jamSekarang)
                            ->where('jam_selesai', '>=', $jamSekarang)
                            ->first();

        // ==========================================================
        // ## PERBAIKAN LOGIKA FILTER BLOK ##
        // ==========================================================
        $tipeMingguString = $tipeMingguObj->tipe_minggu ?? 'Reguler';
        $nomorMinggu = trim(str_replace('Minggu', '', $tipeMingguString)); // Cth: "1"

        $jadwalSekarang = collect(); 
        if ($jamKeSekarang) {
            
            $jadwalSekarang = JadwalPelajaran::where('hari', $hariIni)
                ->where('jam_ke', $jamKeSekarang->jam_ke)
                ->where(function ($query) use ($tipeMingguString, $nomorMinggu) {
                    // 1. Selalu sertakan 'Setiap Minggu'
                    $query->where('tipe_blok', 'Setiap Minggu');
                    // 2. Sertakan jika nama blok sama persis (cth: "Reguler" == "Reguler")
                    $query->orWhere('tipe_blok', $tipeMingguString);
                    // 3. Sertakan "Hanya Minggu 1,2" jika $nomorMinggu adalah "1" atau "2"
                    if (is_numeric($nomorMinggu)) {
                        $query->orWhere('tipe_blok', 'like', '%' . $nomorMinggu . '%'); 
                    }
                    if ($tipeMingguString === 'Reguler') {
                         $query->orWhere('tipe_blok', 'Reguler');
                    }
                })
                ->with('user')
                ->orderBy('kelas', 'asc')
                ->get();
        }

        // ==========================================================
        // ## PERBAIKAN LOGIKA 'keyBy' (BUG FATAL) ##
        // =_========================================================
        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->whereIn('jadwal_pelajaran_id', $jadwalSekarang->pluck('id')) // Hanya ambil laporan yg relevan
                            ->get()
                            ->keyBy('jadwal_pelajaran_id'); // <-- Kunci harus ID JADWAL, bukan ID GURU
        
        return view('display.jadwal', [
            'jadwalSekarang' => $jadwalSekarang,
            'hariIni' => $hariIni,
            'jamKeSekarang' => $jamKeSekarang,
            'jamServer' => $today->isoFormat('HH:mm:ss'), // Anda punya ini di kode lama, saya tambahkan lagi
            'tipeMinggu' => $tipeMingguString, // Kirim string, bukan objek
            'laporanHariIni' => $laporanHariIni
        ]);
    }
}