<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterHariKerja;
use Illuminate\Http\Request;

class HariKerjaController extends Controller
{
    // Menampilkan halaman (7 checkbox)
    public function index()
    {
        $hariKerja = MasterHariKerja::all();
        return view('admin.hari_kerja.index', compact('hariKerja'));
    }

    // Menyimpan perubahan
    public function update(Request $request)
    {
        $request->validate([
            'hari_aktif' => 'nullable|array' // 'hari_aktif' adalah nama checkbox
        ]);

        $hariAktifDariForm = $request->input('hari_aktif', []); // Ambil array hari yg dicentang

        $semuaHari = MasterHariKerja::all();

        foreach ($semuaHari as $hari) {
            // Jika nama hari ada di dalam array yang dicentang
            if (in_array($hari->nama_hari, $hariAktifDariForm)) {
                $hari->is_aktif = true;
            } else {
                $hari->is_aktif = false; // Jika tidak dicentang, set jadi libur
            }
            $hari->save();
        }

        return redirect()->route('admin.hari-kerja.index')->with('success', 'Pengaturan hari kerja berhasil diperbarui.');
    }
}