<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class QrCodeController extends Controller
{
    /**
     * Menampilkan halaman Kios QR Code.
     */
    public function showKios()
    {
        // Kita akan buat view ini di langkah berikutnya
        return view('display.qr_kios');
    }

    /**
     * Menghasilkan token terenkripsi yang unik dan memiliki masa kedaluwarsa.
     */
    public function generateToken()
    {
        // Data yang akan kita sembunyikan di dalam QR code
        $data = [
            'timestamp' => now()->timestamp, // Waktu saat token dibuat
            'valid_until' => now()->addMinutes(1)->timestamp, // Token valid 1 menit
            'secret' => config('app.key'), // Kunci rahasia aplikasi
        ];

        // Enkripsi data menjadi string panjang
        $encryptedToken = Crypt::encryptString(json_encode($data));

        // Kirim token sebagai respons JSON
        return response()->json([
            'token' => $encryptedToken,
        ]);
    }
}