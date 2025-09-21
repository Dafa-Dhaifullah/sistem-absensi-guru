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
    // 1. Validasi input
    $validatedData = $request->validate([
        'data_guru_id' => 'required|exists:data_guru,id',
        'mata_pelajaran' => 'nullable|string|max:255',
        'kelas' => 'required|string|max:255',
        'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
        'tipe_blok' => 'required|in:Setiap Minggu,Hanya Minggu 1,Hanya Minggu 2',
        'jam_ke' => 'required|array', // Pastikan 'jam_ke' adalah array
        'jam_ke.*' => 'required|integer|min:1|max:10' // Validasi setiap item di array
    ]);

    // 2. Ambil array jam_ke
    $jamKeArray = $validatedData['jam_ke'];

    // 3. Hapus jam_ke dari data utama (agar bisa di-loop)
    unset($validatedData['jam_ke']);

    // 4. Looping dan simpan data satu per satu
    foreach ($jamKeArray as $jam) {
        // Gabungkan data utama dengan jam ke-
        $dataToCreate = $validatedData;
        $dataToCreate['jam_ke'] = $jam; 

        JadwalPelajaran::create($dataToCreate);
    }

    return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran ('. count($jamKeArray) .' jam) berhasil ditambahkan.');
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
        // Revisi validasi 'jam_ke'
        'jam_ke' => 'required|integer|min:1|max:10',
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