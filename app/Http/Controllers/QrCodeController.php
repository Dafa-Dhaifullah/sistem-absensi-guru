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
        $waktuDibuat = now();
        $waktuKadaluarsa = $waktuDibuat->copy()->addMinutes(2); // REVISI: Jadi 2 menit

        $data = [
            'timestamp' => $waktuDibuat->timestamp,
            'valid_until' => $waktuKadaluarsa->timestamp,
            'secret' => config('app.key'),
        ];
        $encryptedToken = Crypt::encryptString(json_encode($data));

        // Simpan log ke database
        \App\Models\QrCodeLog::create([
            'token' => $encryptedToken,
            'dibuat_oleh' => auth()->check() ? auth()->id() : null, // Catat siapa yg generate (jika login)
            'waktu_kadaluarsa' => $waktuKadaluarsa,
        ]);

        return response()->json(['token' => $encryptedToken]);
    }
}