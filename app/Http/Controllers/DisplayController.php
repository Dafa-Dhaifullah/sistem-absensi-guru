<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use App\Models\LaporanHarian;
use Illuminate\Support\Carbon;

class DisplayController extends Controller
{
    /**
     * Menampilkan jadwal realtime untuk monitor publik.
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

        $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)
                                  ->whereDate('tanggal_selesai', '>=', $today)
                                  ->first();

        $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                            ->where('jam_mulai', '<=', $jamSekarang)
                            ->where('jam_selesai', '>=', $jamSekarang)
                            ->first();

        $jadwalSekarang = collect(); 
        if ($jamKeSekarang) {
            $blokValid = ['Setiap Minggu'];
            if ($tipeMinggu) {
                if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
                if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
            }
    
            $jadwalSekarang = JadwalPelajaran::where('hari', $hariIni)
                                ->where('jam_ke', $jamKeSekarang->jam_ke)
                                ->whereIn('tipe_blok', $blokValid)
                                ->with('user') // REVISI: with 'user'
                                ->orderBy('kelas', 'asc')
                                ->get();
        }

        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('user_id'); // REVISI: keyBy 'user_id'
        
        return view('display.jadwal', [
            'jadwalSekarang' => $jadwalSekarang,
            'hariIni' => $hariIni,
            'jamKeSekarang' => $jamKeSekarang,
            'jamServer' => $today->isoFormat('HH:mm:ss'),
            'laporanHariIni' => $laporanHariIni
        ]);
    }
}