<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KalenderBlok;
use Illuminate\Http\Request;

class KalenderBlokController extends Controller
{
    public function index()
    {
        $kalender = KalenderBlok::latest()->paginate(10);
        return view('admin.kalender_blok.index', ['kalender' => $kalender]);
    }

    public function create()
    {
        return view('admin.kalender_blok.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'tipe_minggu' => 'required|in:Minggu 1,Minggu 2',
        ]);
        
        // TODO: Tambahkan validasi agar tanggal tidak tumpang tindih (overlapping)

        KalenderBlok::create($validatedData);

        return redirect()->route('admin.kalender-blok.index')->with('success', 'Kalender blok berhasil ditambahkan.');
    }

    public function edit(KalenderBlok $kalenderBlok)
    {
        // (Gunakan Route Model Binding)
        return view('admin.kalender_blok.edit', ['kalender' => $kalenderBlok]);
    }

    public function update(Request $request, KalenderBlok $kalenderBlok)
    {
        $validatedData = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'tipe_minggu' => 'required|in:Minggu 1,Minggu 2',
        ]);
        
        // TODO: Validasi tumpang tindih

        $kalenderBlok->update($validatedData);

        return redirect()->route('admin.kalender-blok.index')->with('success', 'Kalender blok berhasil diperbarui.');
    }

    public function destroy(KalenderBlok $kalenderBlok)
    {
        $kalenderBlok->delete();
        return redirect()->route('admin.kalender-blok.index')->with('success', 'Kalender blok berhasil dihapus.');
    }
}