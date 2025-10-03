<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use Illuminate\Http\Request;

class HariLiburController extends Controller
{
    /**
     * Menampilkan daftar hari libur.
     */
    public function index()
    {
        $semuaHariLibur = HariLibur::orderBy('tanggal', 'desc')->paginate(15);
        return view('admin.hari_libur.index', compact('semuaHariLibur'));
    }

    /**
     * Menampilkan form tambah hari libur.
     */
    public function create()
    {
        return view('admin.hari_libur.create');
    }

    /**
     * Menyimpan hari libur baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:hari_libur',
            'keterangan' => 'required|string|max:255',
        ]);

        HariLibur::create($request->all());

        return redirect()->route('admin.hari-libur.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    /**
     * Menghapus hari libur.
     */
    public function destroy(HariLibur $hariLibur)
    {
        $hariLibur->delete();
        return redirect()->route('admin.hari-libur.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}
