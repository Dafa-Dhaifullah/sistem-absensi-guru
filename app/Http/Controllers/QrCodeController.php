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
        return view('display.qr_kios');
    }

    /**
     * Menghasilkan token terenkripsi yang unik (tanpa menyimpan log).
     */
    public function generateToken()
    {
        $waktuDibuat = now();
        $waktuKadaluarsa = $waktuDibuat->copy()->addMinutes(2);

        $data = [
            'timestamp' => $waktuDibuat->timestamp,
            'valid_until' => $waktuKadaluarsa->timestamp,
            'secret' => config('app.key'),
        ];

        $encryptedToken = Crypt::encryptString(json_encode($data));

        // Logika penyimpanan ke database DIHAPUS

        return response()->json([
            'token' => $encryptedToken,
        ]);
    }
}
