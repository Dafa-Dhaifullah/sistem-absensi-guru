<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use App\Models\LaporanHarian; // <-- TAMBAHKAN IMPORT INI
use Illuminate\Support\Carbon;

class DisplayController extends Controller
{
    /**
     * Menampilkan jadwal realtime untuk monitor publik.
     */
    public function jadwalRealtime()
    {
        // 1-4. (Peta Hari, Waktu, Blok, JamKe) - INI SEMUA SAMA
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $today = now('Asia/Jakarta');
        $hariIni = $hariMap[$today->format('l')]; 
        $jamSekarang = $today->toTimeString();

        $tipeMinggu = KalenderBlok::where('tanggal_mulai', '<=', $today)
                                ->where('tanggal_selesai', '>=', $today)
                                ->first();

        $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                            ->where('jam_mulai', '<=', $jamSekarang)
                            ->where('jam_selesai', '>=', $jamSekarang)
                            ->first();

        // 5. Query Jadwal (Sama seperti sebelumnya)
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
                                ->with('dataGuru') 
                                ->orderBy('kelas', 'asc')
                                ->get();
        }

        // ============================================
        // ## PERUBAHAN DI SINI ##
        // 6. Ambil data absensi yang sudah di-submit HARI INI
        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            // Buat peta (map) agar mudah dicari di view
                            // Key-nya adalah ID guru, Valuenya adalah data laporan
                            ->keyBy('data_guru_id'); 
        // ============================================

        // 7. Tampilkan View (Kirim data baru $laporanHariIni)
        return view('display.jadwal', [
            'jadwalSekarang' => $jadwalSekarang,
            'hariIni' => $hariIni,
            'jamKeSekarang' => $jamKeSekarang,
            'jamServer' => $today->isoFormat('HH:mm:ss'),
            'laporanHariIni' => $laporanHariIni // <-- KIRIM DATA BARU INI
        ]);
    }
}