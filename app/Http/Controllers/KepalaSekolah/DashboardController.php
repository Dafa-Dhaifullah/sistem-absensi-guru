<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now('Asia/Jakarta')->toDateString();

        // --- 1. Ambil data ringkasan kehadiran HARI INI ---
        $laporanHariIni = LaporanHarian::where('tanggal', $today)->get();
        
        // REVISI: Tambahkan 'dl' (Dinas Luar) ke dalam summary
        $summaryHariIni = [
            'hadir' => $laporanHariIni->where('status', 'Hadir')->count(),
            'terlambat' => $laporanHariIni->where('status', 'Hadir')->where('status_keterlambatan', 'Terlambat')->count(),
            'sakit' => $laporanHariIni->where('status', 'Sakit')->count(),
            'izin' => $laporanHariIni->where('status', 'Izin')->count(),
            'alpa' => $laporanHariIni->where('status', 'Alpa')->count(),
            'dl' => $laporanHariIni->where('status', 'DL')->count(), // <-- INI TAMBAHANNYA
        ];

        // --- 2. Ambil data notifikasi warning ---
        $statusTidakHadir = ['Sakit', 'Izin', 'Alpa'];
        $batasAbsen = 4;
        $guruWarning = User::whereIn('role', ['guru', 'piket'])
            ->whereHas('laporanHarian', function ($query) use ($statusTidakHadir) {
                $query->whereIn('status', $statusTidakHadir)
                      ->whereMonth('tanggal', now()->month)
                      ->whereYear('tanggal', now()->year);
            }, '>=', $batasAbsen)
            ->withCount(['laporanHarian as total_tidak_hadir' => function ($query) use ($statusTidakHadir) {
                $query->whereIn('status', $statusTidakHadir)
                      ->whereMonth('tanggal', now()->month)
                      ->whereYear('tanggal', now()->year);
            }])->get();

        return view('kepala_sekolah.dashboard', compact('summaryHariIni', 'guruWarning'));
    }
}