<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\User; // <-- Menggunakan model User
use Illuminate\Http\Request;

class DashboardAdminController extends Controller
{
    public function index()
{
    // --- DATA STATISTIK (Tidak berubah) ---
    $jumlahGuru = \App\Models\User::whereIn('role', ['guru', 'piket'])->count(); 
    $jumlahAkunPiket = \App\Models\User::where('role', 'piket')->count();
    $jumlahJadwal = \App\Models\JadwalPelajaran::count();

    // ==========================================================
    // ## LOGIKA NOTIFIKASI YANG SUDAH DIPERBAIKI ##
    // ==========================================================

    $statusTidakHadir = ['Sakit', 'Izin', 'Alpa','DL']; 
    $batasAbsen = 4;

    // Query yang lebih andal:
    // 1. Hitung dulu jumlah ketidakhadiran bulan ini untuk semua guru.
    // 2. Kemudian, filter (having) hanya yang jumlahnya >= batas.
    $guruWarning = \App\Models\User::where('role', 'guru')
        ->withCount(['laporanHarian as total_tidak_hadir' => function ($query) use ($statusTidakHadir) {
            $query->whereIn('status', $statusTidakHadir)
                  ->whereMonth('tanggal', now()->month)
                  ->whereYear('tanggal', now()->year);
        }])
        ->having('total_tidak_hadir', '>=', $batasAbsen)
        ->get();

    // ==========================================================

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