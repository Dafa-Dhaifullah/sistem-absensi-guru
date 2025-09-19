<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\DataGuru; // Kita butuh ini untuk form
use Illuminate\Http\Request;

class JadwalPelajaranController extends Controller
{
    public function index()
    {
        // Tampilkan semua jadwal dengan info guru (relasi)
        $jadwal = JadwalPelajaran::with('dataGuru')->latest()->paginate(15);
        return view('admin.jadwal_pelajaran.index', ['semuaJadwal' => $jadwal]);
    }

    public function create()
    {
        // Kirim data semua guru ke form agar bisa dipilih
        $dataGuru = DataGuru::orderBy('nama_guru', 'asc')->get();
        return view('admin.jadwal_pelajaran.create', ['dataGuru' => $dataGuru]);
    }

    public function store(Request $request)
    {
        // Validasi
        $validatedData = $request->validate([
            'data_guru_id' => 'required|exists:data_guru,id',
            'mata_pelajaran' => 'nullable|string|max:255',
            'kelas' => 'required|string|max:255',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke' => 'required|string|max:50',
            'tipe_blok' => 'required|in:Setiap Minggu,Hanya Minggu 1,Hanya Minggu 2',
        ]);

        JadwalPelajaran::create($validatedData);

        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function edit(JadwalPelajaran $jadwalPelajaran)
    {
        // (Route model binding akan otomatis mencari $jadwalPelajaran)
        $dataGuru = DataGuru::orderBy('nama_guru', 'asc')->get();
        return view('admin.jadwal_pelajaran.edit', [
            'jadwal' => $jadwalPelajaran,
            'dataGuru' => $dataGuru
        ]);
    }

    public function update(Request $request, JadwalPelajaran $jadwalPelajaran)
    {
        // Validasi
        $validatedData = $request->validate([
            'data_guru_id' => 'required|exists:data_guru,id',
            'mata_pelajaran' => 'nullable|string|max:255',
            'kelas' => 'required|string|max:255',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke' => 'required|string|max:50',
            'tipe_blok' => 'required|in:Setiap Minggu,Hanya Minggu 1,Hanya Minggu 2',
        ]);

        $jadwalPelajaran->update($validatedData);

        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil diperbarui.');
    }

    public function destroy(JadwalPelajaran $jadwalPelajaran)
    {
        $jadwalPelajaran->delete();
        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }
}