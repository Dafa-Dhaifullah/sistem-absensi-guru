<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class GantiPasswordController extends Controller
{
    /**
     * Menampilkan form ganti password.
     */
    public function edit()
    {
        // Anda harus buat view-nya di: resources/views/piket/ganti_password.blade.php
        return view('piket.ganti_password');
    }

    /**
     * Memperbarui password pengguna yang sedang login.
     */
    public function update(Request $request)
    {
        // 1. Validasi
        $validated = $request->validate([
            // 'current_password' otomatis mengecek password yang sedang login
            'current_password' => ['required', 'current_password'],
            // 'confirmed' otomatis mencocokkan dengan 'password_confirmation'
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // 2. Update password
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // 3. Kembalikan dengan pesan sukses
        return redirect()->route('piket.ganti-password.edit')->with('success', 'Password berhasil diperbarui.');
    }
}