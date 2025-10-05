<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiwayatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Ambil semua riwayat absensi guru yang login, urutkan dari terbaru
        $riwayat = $user->laporanHarian()->orderBy('tanggal', 'desc')->paginate(15);

        return view('guru.riwayat', compact('riwayat'));
    }
}