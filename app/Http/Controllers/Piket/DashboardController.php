<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Set locale ke Indonesia untuk nama hari
        Carbon::setLocale('id_ID');

        // 1. Tentukan hari ini & tipe minggunya
        $today = now();
        $hariIni = $today->translatedFormat('l'); // Misal: "Jumat"
        
        $tipeMinggu = KalenderBlok::where('tanggal_mulai', '<=', $today)
                                  ->where('tanggal_selesai', '>=', $today)
                                  ->first();
        
        // 2. Tentukan tipe blok yang valid untuk query
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            $blokValid[] = $tipeMinggu->tipe_minggu; // Tambahkan 'Minggu 1' atau 'Minggu 2'
        }

        // 3. Query "Daftar Guru Wajib Hadir"
        $jadwalWajib = JadwalPelajaran::where('hari', $hariIni)
                                        ->whereIn('tipe_blok', $blokValid)
                                        ->with('dataGuru') // Ambil relasi ke DataGuru
                                        ->get();

        // 4. Ambil daftar guru unik (jika 1 guru ada >1 jadwal hari ini)
        // Kita gunakan relasi 'dataGuru' yang sudah di-load
        $guruWajibHadir = $jadwalWajib->pluck('dataGuru')->unique('id')->sortBy('nama_guru');

        // TODO: Cek apakah laporan hari ini sudah di-submit?
        
        // TODO: Ambil data logbook hari ini

        // Tampilkan view Piket Dashboard
        // Anda harus buat view-nya di: resources/views/piket/dashboard.blade.php
        return view('piket.dashboard', [
            'guruWajibHadir' => $guruWajibHadir,
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
            // 'logbook' => $logbook
        ]);
    }
}