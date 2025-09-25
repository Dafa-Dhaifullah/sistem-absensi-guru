<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataGuru;
use App\Models\JadwalPelajaran;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardAdminController extends Controller
{
    public function index()
    {
        // --- DATA STATISTIK (SAMA SEPERTI SEBELUMNYA) ---
        $jumlahGuru = DataGuru::count();
        $jumlahAkunPiket = User::where('role', 'piket')->count();
        $jumlahJadwal = JadwalPelajaran::count();

        // ==========================================================
        // ## LOGIKA BARU UNTUK NOTIFIKASI WARNING (GABUNGAN) ##
        // ==========================================================

        // Tentukan status yang dihitung 'tidak hadir'
        $statusTidakHadir = ['Sakit', 'Izin', 'Alpa']; 
        $batasAbsen = 4;

        // Query untuk mencari guru yang punya total S+I+A >= 4 kali bulan ini
        $guruWarning = DataGuru::withCount(['laporanHarian' => function ($query) use ($statusTidakHadir) {
            $query->whereIn('status', $statusTidakHadir)
                  ->whereMonth('tanggal', now()->month)
                  ->whereYear('tanggal', now()->year);
        }])->having('laporan_harian_count', '>=', $batasAbsen)->get();

        // ==========================================================

        // Kirim semua data ke view
        return view('admin.dashboard', [
            'jumlahGuru' => $jumlahGuru,
            'jumlahAkunPiket' => $jumlahAkunPiket,
            'jumlahJadwal' => $jumlahJadwal,
            'guruWarning' => $guruWarning, // <-- Kirim data (sekarang hanya 1 variabel)
        ]);
    }
}