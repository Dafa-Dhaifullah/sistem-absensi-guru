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
        // Ambil semua jadwal, lalu grupkan berdasarkan Hari, lalu Sesi
        $jadwalTersimpan = JadwalPiket::with('user')
                            ->get()
                            ->groupBy('hari')
                            ->map(function ($group) {
                                return $group->groupBy('sesi');
                            });

        $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $sesi = ['Pagi', 'Siang'];

        return view('admin.jadwal_piket.index', compact('jadwalTersimpan', 'hari', 'sesi'));
    }

    /**
     * Menampilkan form (checkbox) untuk mengedit satu slot.
     */
    public function edit($hari, $sesi)
    {
        // Ambil semua guru piket untuk pilihan checkbox
        $daftarGuruPiket = User::where('role', 'piket')->orderBy('name', 'asc')->get();
        
        // Ambil data guru yang saat ini terpilih untuk slot ini
        $jadwalSlot = JadwalPiket::where('hari', $hari)->where('sesi', $sesi)->get();
        
        // Ambil ID-nya saja untuk memudahkan pengecekan 'checked'
        $selected_ids = $jadwalSlot->pluck('user_id');

        return view('admin.jadwal_piket.edit', compact('hari', 'sesi', 'daftarGuruPiket', 'selected_ids'));
    }

    /**
     * Menyimpan perubahan untuk satu slot.
     */
    public function update(Request $request, $hari, $sesi)
    {
        // Validasi bahwa 'user_ids' adalah array, dan semua isinya ada di tabel 'users'
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

        return redirect()->route('admin.jadwal-piket.index')->with('success', "Jadwal piket $hari $sesi berhasil diperbarui.");
    }
}