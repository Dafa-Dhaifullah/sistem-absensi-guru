<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LaporanHarian;
use App\Models\JadwalPiket;
use App\Models\User;
use App\Models\KalenderBlok;
use App\Models\MasterJamPelajaran;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = now('Asia/Jakarta');
        $hariMap = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
        $hariIni = $hariMap[$today->format('l')];
        
        // --- LOGIKA BARU: MENGELOMPOKKAN JADWAL MENJADI BLOK MENGAJAR ---
        $jadwalHariIni = collect();
        $jadwalBlok = collect(); // Ini yang akan dikirim ke view

        if ($user->jadwalPelajaran) {
            $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)->whereDate('tanggal_selesai', '>=', $today)->first();
            $blokValid = ['Setiap Minggu'];
            if ($tipeMinggu) {
                if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
                if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
            }

            $jadwalHariIni = $user->jadwalPelajaran()
                ->where('hari', $hariIni)
                ->whereIn('tipe_blok', $blokValid)
                ->orderBy('jam_ke', 'asc')
                ->get();
            
            // Proses pengelompokan
            $tempBlock = null;
            foreach ($jadwalHariIni as $jadwal) {
                if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                    // Lanjutkan blok yang ada
                    $tempBlock['jadwal_ids'][] = $jadwal->id;
                    $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                } else {
                    // Simpan blok lama jika ada, lalu buat blok baru
                    if ($tempBlock) $jadwalBlok->push($tempBlock);
                    $tempBlock = [
                        'jadwal_ids' => [$jadwal->id],
                        'jam_pertama' => $jadwal->jam_ke,
                        'jam_terakhir' => $jadwal->jam_ke,
                        'kelas' => $jadwal->kelas,
                        'mata_pelajaran' => $jadwal->mata_pelajaran,
                    ];
                }
            }
            if ($tempBlock) $jadwalBlok->push($tempBlock); // Simpan blok terakhir
        }

        // Cek laporan absensi hari ini, diindeks berdasarkan jadwal_pelajaran_id
        $laporanHariIni = LaporanHarian::where('user_id', $user->id)
            ->where('tanggal', $today->toDateString())
            ->get()
            ->keyBy('jadwal_pelajaran_id');
        
        $masterJamHariIni = MasterJamPelajaran::where('hari', $hariIni)->get()->keyBy('jam_ke');
        
        // --- Ambil data guru piket & Cek status piket (tidak berubah) ---
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';
        $piketIds = JadwalPiket::where('hari', $hariIni)->where('sesi', $sesiSekarang)->pluck('user_id');
        $guruPiketHariIni = User::whereIn('id', $piketIds)->get();
        $isPiket = $piketIds->contains($user->id);

        return view('guru.dashboard', [
            'jadwalBlok' => $jadwalBlok, // <-- Kirim data blok
            'laporanHariIni' => $laporanHariIni,
            'guruPiketHariIni' => $guruPiketHariIni,
            'sesiSekarang' => $sesiSekarang,
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMinggu->tipe_minggu ?? 'Reguler',
            'masterJamHariIni' => $masterJamHariIni,
            'sedangPiket' => $isPiket,
        ]);
    }
}