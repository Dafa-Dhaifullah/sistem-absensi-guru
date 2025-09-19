<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataGuru; // Pastikan Model DataGuru sudah di-import
use Illuminate\Http\Request;

class DataGuruController extends Controller
{
    /**
     * Menampilkan daftar semua guru.
     */
    public function index()
    {
        // Ambil data guru terbaru, 10 data per halaman
        $semuaGuru = DataGuru::latest()->paginate(10);
        
        // Tampilkan view dan kirim data
        return view('admin.data_guru.index', ['semuaGuru' => $semuaGuru]);
    }

    /**
     * Menampilkan form untuk menambah guru baru.
     */
    public function create()
    {
        return view('admin.data_guru.create');
    }

    /**
     * Menyimpan data guru baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi input
        $validatedData = $request->validate([
            'nama_guru' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50|unique:data_guru', // NIP boleh kosong, tapi jika diisi harus unik
        ]);

        // 2. Simpan data ke database
        DataGuru::create($validatedData);

        // 3. Kembali ke halaman index dengan pesan sukses
        return redirect()->route('admin.data-guru.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    /**
     * Menampilkan data satu guru (opsional, jika Anda butuh halaman detail).
     */
    public function show($id)
    {
        $guru = DataGuru::findOrFail($id);
        return view('admin.data_guru.show', ['guru' => $guru]);
    }

    /**
     * Menampilkan form untuk mengedit data guru.
     */
    public function edit($id)
    {
        $guru = DataGuru::findOrFail($id);
        return view('admin.data_guru.edit', ['guru' => $guru]);
    }

    /**
     * Meng-update data guru di database.
     */
    public function update(Request $request, $id)
    {
        $guru = DataGuru::findOrFail($id);

        // 1. Validasi input
        $validatedData = $request->validate([
            'nama_guru' => 'required|string|max:255',
            // Pastikan NIP unik, tapi abaikan NIP milik guru ini sendiri
            'nip' => 'nullable|string|max:50|unique:data_guru,nip,' . $guru->id,
        ]);

        // 2. Update data
        $guru->update($validatedData);

        // 3. Kembali ke halaman index dengan pesan sukses
        return redirect()->route('admin.data-guru.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    /**
     * Menghapus data guru dari database.
     */
    public function destroy($id)
    {
        $guru = DataGuru::findOrFail($id);
        
        // Hati-hati: Jika guru ini sudah terkait dengan jadwal pelajaran,
        // Anda mungkin perlu logika tambahan di sini (misal: 'onDelete('cascade')' di migrasi).
        
        $guru->delete();

        return redirect()->route('admin.data-guru.index')->with('success', 'Data guru berhasil dihapus.');
    }
}