<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\User;
use App\Models\HariLibur;
use App\Models\KalenderBlok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardAdminController extends Controller
{
    public function index()
{
    // --- DATA STATISTIK (Tidak berubah) ---
    $jumlahGuru = \App\Models\User::whereIn('role', ['guru', 'piket'])->count(); 
    $jumlahAkunPiket = \App\Models\User::where('role', 'piket')->count();
    $jumlahJadwal = \App\Models\JadwalPelajaran::count();

    $batasAbsen = 4;
        $bulanIni = now()->month;
        $tahunIni = now()->year;
        $today = now('Asia/Jakarta')->startOfDay();

        // 1. Ambil semua data yang relevan
        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($bulanIni, $tahunIni) {
                $query->whereMonth('tanggal', $bulanIni)->whereYear('tanggal', $tahunIni);
            }])
            ->get();
        
        $hariLibur = HariLibur::whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->pluck('tanggal')
            ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());
            
        $guruWarning = collect();

        // 2. Loop setiap guru untuk menghitung status harian
        foreach ($semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
            $laporanGuru = $guru->laporanHarian;
            
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0;

            // Loop setiap hari di bulan ini (hanya sampai hari ini)
            for ($i = 1; $i <= $today->day; $i++) {
                $tanggal = Carbon::create($tahunIni, $bulanIni, $i)->startOfDay();
                $tanggalCek = $tanggal->toDateString();
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                // Cek apakah hari ini hari kerja untuk guru ini
                $adaJadwal = $jadwalHariGuru->has($namaHari);
                $isLibur = $hariLibur->contains($tanggalCek);
                
                if ($adaJadwal && !$isLibur) {
                    $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $tanggal)->whereDate('tanggal_selesai', '>=', $tanggal)->first()->tipe_minggu ?? 'Reguler';
                    $adaJadwalBlok = $jadwalHariGuru->get($namaHari)->whereIn('tipe_blok', ['Setiap Minggu', $tipeMinggu])->isNotEmpty();
                    
                    if ($adaJadwalBlok) {
                        $laporanPerHari = $laporanGuru->where('tanggal', $tanggalCek);
                        
                        if ($laporanPerHari->isNotEmpty()) {
                            if ($laporanPerHari->contains('status', 'Sakit')) {
                                $totalSakit++;
                            } elseif ($laporanPerHari->contains('status', 'Izin')) {
                                $totalIzin++;
                            }
                            // Status Hadir & DL diabaikan
                        } else {
                            // Tidak ada data laporan sama sekali di hari kerja = Alpa
                            $totalAlpa++;
                        }
                    }
                }
            }
            
            $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa;
            
            if ($totalTidakHadir >= $batasAbsen) {
                $guru->total_tidak_hadir = $totalTidakHadir; // Tambahkan properti baru
                $guruWarning->push($guru);
            }
        }
        // Kirim semua data ke view
        return view('admin.dashboard', [
            'jumlahGuru' => $jumlahGuru,
            'jumlahAkunPiket' => $jumlahAkunPiket,
            'jumlahJadwal' => $jumlahJadwal,
            'guruWarning' => $guruWarning,
            'batasAbsen' => $batasAbsen, // Kirim juga batas absen ke view
        ]);
    }
}