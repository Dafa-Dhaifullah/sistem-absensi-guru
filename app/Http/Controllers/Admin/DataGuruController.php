<?php

namespace App\Http\Controllers\Admin;

use App\Imports\GuruImport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
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

    // Method untuk menampilkan halaman form
public function showImportForm()
{
    return view('admin.data_guru.import');
}

// Method untuk memproses file Excel
public function importExcel(Request $request)
{
    // Validasi file yang di-upload
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv'
    ]);

    try {
        // Lakukan import
        Excel::import(new GuruImport, $request->file('file'));

        return redirect()->route('admin.data-guru.index')->with('success', 'Data guru berhasil diimpor!');

    } catch (ValidationException $e) {
        // Jika ada error validasi di dalam file Excel (misal NIP duplikat)
        $failures = $e->failures();
        $errorMessages = [];
        foreach ($failures as $failure) {
            $errorMessages[] = "Error di baris " . $failure->row() . ": " . implode(', ', $failure->errors());
        }
        return redirect()->route('admin.data-guru.import.form')->with('error', 'Gagal mengimpor data. Detail: <br>' . implode('<br>', $errorMessages));
    }
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