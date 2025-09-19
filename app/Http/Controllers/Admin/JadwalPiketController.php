<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPiket;
use App\Models\User; // Kita butuh data user piket
use Illuminate\Http\Request;

class JadwalPiketController extends Controller
{
    /**
     * Menampilkan halaman grid template jadwal piket mingguan.
     */
    public function index()
    {
        // 1. Ambil semua guru yang role-nya 'piket' untuk opsi dropdown
        $daftarGuruPiket = User::where('role', 'piket')->orderBy('name', 'asc')->get();

        // 2. Ambil data jadwal piket yang sudah tersimpan
        $jadwalTersimpan = JadwalPiket::with('user')
                            ->get()
                            ->keyBy(function($item) {
                                // Buat key unik, misal: 'Senin_Pagi'
                                return $item->hari . '_' . $item->sesi;
                            });

        // 3. Siapkan slot hari dan sesi
        $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $sesi = ['Pagi', 'Siang'];

        // Kirim semua data ke view
        // Anda harus buat view-nya di: resources/views/admin/jadwal_piket/index.blade.php
        return view('admin.jadwal_piket.index', [
            'daftarGuruPiket' => $daftarGuruPiket,
            'jadwalTersimpan' => $jadwalTersimpan,
            'hari' => $hari,
            'sesi' => $sesi
        ]);
    }

    /**
     * Menyimpan (Update) seluruh grid jadwal piket mingguan.
     */
    public function update(Request $request)
    {
        // 1. Validasi input
        // (Ini akan memvalidasi input seperti 'jadwal[Senin][Pagi]' => 'harus diisi dan ada di tabel users')
        $request->validate([
            'jadwal' => 'required|array',
            'jadwal.*.*' => 'required|integer|exists:users,id',
        ], [
            'jadwal.*.*.required' => 'Semua slot piket (Pagi & Siang) harus diisi.',
            'jadwal.*.*.exists' => 'Guru yang dipilih tidak valid.'
        ]);

        // 2. Simpan data ke database
        foreach ($request->jadwal as $hari => $sesiArray) {
            foreach ($sesiArray as $sesi => $userId) {
                
                // updateOrCreate: Canggih, akan meng-update jika ada, atau membuat jika belum ada.
                JadwalPiket::updateOrCreate(
                    [
                        'hari' => $hari,
                        'sesi' => $sesi,
                    ],
                    [
                        'user_id' => $userId
                    ]
                );
            }
        }

        return redirect()->route('admin.jadwal-piket.index')->with('success', 'Jadwal piket mingguan berhasil diperbarui.');
    }
}