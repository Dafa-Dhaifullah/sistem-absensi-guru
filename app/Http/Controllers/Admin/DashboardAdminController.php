<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataGuru;
use App\Models\JadwalPelajaran;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardAdminController extends Controller
{
    /**
     * Menampilkan halaman dashboard admin.
     */
    public function index()
    {
        // Ambil beberapa data statistik sederhana untuk "shortcut"
        $jumlahGuru = DataGuru::count();
        $jumlahAkunPiket = User::where('role', 'piket')->count();
        $jumlahJadwal = JadwalPelajaran::count();

        // Anda harus buat view-nya di: resources/views/admin/dashboard.blade.php
        return view('admin.dashboard', [
            'jumlahGuru' => $jumlahGuru,
            'jumlahAkunPiket' => $jumlahAkunPiket,
            'jumlahJadwal' => $jumlahJadwal,
        ]);
    }
}