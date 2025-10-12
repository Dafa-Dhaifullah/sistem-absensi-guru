<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPiket;
use App\Models\User;
use Illuminate\Http\Request;

class JadwalPiketController extends Controller
{
    /**
     * Menampilkan grid jadwal piket (Read-Only)
     */
    public function index()
    {
        // Ambil semua guru dengan role 'guru' untuk pilihan di modal
        $daftarGuruPiket = User::where('role', 'guru')->orderBy('name', 'asc')->get();

        // Ambil semua jadwal, lalu grupkan berdasarkan Hari, lalu Sesi
        $jadwalTersimpan = JadwalPiket::with('user')
                            ->get()
                            ->groupBy('hari')
                            ->map(function ($group) {
                                return $group->groupBy('sesi');
                            });

        $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $sesi = ['Pagi', 'Siang'];

        return view('admin.jadwal_piket.index', compact('daftarGuruPiket', 'jadwalTersimpan', 'hari', 'sesi'));
    }

    /**
     * Halaman edit tidak lagi digunakan dalam alur ini,
     * tapi kita biarkan kosong untuk menghindari error jika ada rute lama.
     */
    public function edit($hari, $sesi)
    {
        // Redirect ke halaman index utama
        return redirect()->route('admin.jadwal-piket.index');
    }

    /**
     * Menyimpan perubahan untuk satu slot dari modal.
     */
    public function update(Request $request, $hari, $sesi)
    {
        $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        // 1. Hapus semua guru dari slot ini
        JadwalPiket::where('hari', $hari)->where('sesi', $sesi)->delete();

        // 2. Masukkan kembali guru yang baru dipilih (dari checkbox)
        if ($request->has('user_ids')) {
            foreach ($request->user_ids as $userId) {
                JadwalPiket::create([
                    'hari' => $hari,
                    'sesi' => $sesi,
                    'user_id' => $userId,
                ]);
            }
        }

        return redirect()->route('admin.jadwal-piket.index')->with('success', "Jadwal piket untuk $hari $sesi berhasil diperbarui.");
    }
}
