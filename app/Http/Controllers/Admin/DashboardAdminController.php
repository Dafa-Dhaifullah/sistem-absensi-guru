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
        // REVISI: Hitung guru dari tabel 'users'
        $jumlahGuru = User::whereIn('role', ['guru', 'piket'])->count(); 
        $jumlahAkunPiket = User::where('role', 'piket')->count();
        $jumlahJadwal = JadwalPelajaran::count();

        // REVISI: Logika notifikasi sekarang menggunakan relasi dari User
        $statusTidakHadir = ['Sakit', 'Izin', 'Alpa']; 
        $batasAbsen = 4;

        $guruWarning = User::whereIn('role', ['guru', 'piket']) // Hanya cek guru
            ->whereHas('laporanHarian', function ($query) use ($statusTidakHadir) {
                $query->whereIn('status', $statusTidakHadir)
                      ->whereMonth('tanggal', now()->month)
                      ->whereYear('tanggal', now()->year);
            }, '>=', $batasAbsen)
            ->withCount(['laporanHarian' => function ($query) use ($statusTidakHadir) {
                $query->whereIn('status', $statusTidakHadir)
                      ->whereMonth('tanggal', now()->month)
                      ->whereYear('tanggal', now()->year);
            }])->get();

        return view('admin.dashboard', compact(
            'jumlahGuru', 'jumlahAkunPiket', 'jumlahJadwal', 'guruWarning'
        ));
    }
}