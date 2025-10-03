<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\User;
use Illuminate\Http\Request;
use App\Imports\JadwalPelajaranImport; // <-- Pastikan ini ada
use Maatwebsite\Excel\Facades\Excel; // <-- Pastikan ini ada
use Maatwebsite\Excel\Validators\ValidationException; // <-- Pastikan ini ada

class JadwalPelajaranController extends Controller
{
    public function index()
    {
        $jadwal = JadwalPelajaran::with('user')->latest()->paginate(15);
        return view('admin.jadwal_pelajaran.index', ['semuaJadwal' => $jadwal]);
    }

    public function create()
    {
        $daftarGuru = User::where('role', 'guru')->orderBy('name', 'asc')->get();
        return view('admin.jadwal_pelajaran.create', ['daftarGuru' => $daftarGuru]);
    }

    public function store(Request $request)
    {
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

    public function show(JadwalPelajaran $jadwalPelajaran)
    {
        return redirect()->route('admin.jadwal-pelajaran.index');
    }

    public function edit(JadwalPelajaran $jadwalPelajaran)
    {
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
    
    // --- METHOD UNTUK IMPORT ---

    public function showImportForm()
    {
        return view('admin.jadwal_pelajaran.import');
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new JadwalPelajaranImport, $request->file('file'));
            
            return redirect()->route('admin.jadwal-pelajaran.index')->with('success', 'Jadwal pelajaran berhasil diimpor!');

        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Error di baris " . $failure->row() . ": " . implode(', ', $failure->errors());
            }
            return redirect()->route('admin.jadwal-pelajaran.import.form')->with('error', 'Gagal mengimpor data. Detail: <br>' . implode('<br>', $errorMessages));
        }
    }
}
