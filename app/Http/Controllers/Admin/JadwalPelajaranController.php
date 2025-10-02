<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\User; // <-- Menggunakan model User, bukan DataGuru
use Illuminate\Http\Request;

class JadwalPelajaranController extends Controller
{
    public function index()
    {
        // Ganti relasi dari 'dataGuru' menjadi 'user'
        $jadwal = JadwalPelajaran::with('user')->latest()->paginate(15);
        return view('admin.jadwal_pelajaran.index', ['semuaJadwal' => $jadwal]);
    }

    public function create()
    {
        // Ambil data guru dari tabel 'users' dengan role 'guru' atau 'piket'
        // Sekarang hanya mengambil pengguna dengan role 'guru'
$daftarGuru = User::where('role', 'guru')->orderBy('name', 'asc')->get();
        return view('admin.jadwal_pelajaran.create', ['daftarGuru' => $daftarGuru]);
    }

    public function store(Request $request)
    {
        // Ganti validasi 'data_guru_id' menjadi 'user_id'
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mata_pelajaran' => 'nullable|string|max:255',
            'kelas' => 'required|string|max:255',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_ke' => 'required|array',
            'jam_ke.*' => 'required|integer|min:1|max:10',
            'tipe_blok' => 'required|in:Setiap Minggu,Hanya Minggu 1,Hanya Minggu 2',
        ]);

        $jamKeArray = $validatedData['jam_ke'];
        unset($validatedData['jam_ke']);

        foreach ($jamKeArray as $jam) {
            $dataToCreate = $validatedData;
            $dataToCreate['jam_ke'] = $jam; 

            JadwalPelajaran::create($dataToCreate);
        }

        return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function edit(JadwalPelajaran $jadwalPelajaran)
    {
        // Sekarang hanya mengambil pengguna dengan role 'guru'
$daftarGuru = User::where('role', 'guru')->orderBy('name', 'asc')->get();
        return view('admin.jadwal_pelajaran.edit', [
            'jadwal' => $jadwalPelajaran,
            'daftarGuru' => $daftarGuru
        ]);
    }

    public function update(Request $request, JadwalPelajaran $jadwalPelajaran)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'mata_pelajaran' => 'nullable|string|max:255',
            'kelas' => 'required|string|max:255',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
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