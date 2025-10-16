<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JadwalPelajaran;

class QrCodeGeneratorController extends Controller
{
    /**
     * Menampilkan halaman utama generator QR Code dengan daftar kelas.
     */
    public function index()
    {
        // Ambil semua nama kelas yang unik dari tabel jadwal pelajaran
        $daftarKelas = JadwalPelajaran::select('kelas')->distinct()->orderBy('kelas', 'asc')->get();

        return view('admin.qrcode.index', compact('daftarKelas'));
    }

    /**
     * Menampilkan halaman cetak untuk satu QR Code.
     */
    public function print(Request $request)
    {
        // Validasi input kelas
        $request->validate(['kelas' => 'required|string']);

        $kelas = $request->kelas;

        // Kirim nama kelas ke view, QR code akan di-generate di sana
        return view('admin.qrcode.print', compact('kelas'));
    }
}