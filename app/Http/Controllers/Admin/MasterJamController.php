<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterJamPelajaran;
use Illuminate\Http\Request;

class MasterJamController extends Controller
{
    /**
     * Menampilkan halaman index untuk memilih hari.
     */
    public function index()
    {
        $daftarHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        
        return view('admin.master_jam.index', compact('daftarHari'));
    }

    /**
     * Menampilkan form untuk mengedit jam pelajaran pada hari yang dipilih.
     */
    public function edit($hari)
    {
        // Ambil 10 jam pelajaran untuk hari yang dipilih
        $jamPelajaran = MasterJamPelajaran::where('hari', $hari)
                                ->orderBy('jam_ke', 'asc')
                                ->get();
        
        // Jika data master jam belum ada (misal setelah migrate),
        // buat 10 jam default agar form tidak kosong.
        if ($jamPelajaran->isEmpty()) {
            for ($i = 1; $i <= 10; $i++) {
                MasterJamPelajaran::create([
                    'hari' => $hari,
                    'jam_ke' => $i,
                    'jam_mulai' => '07:00',
                    'jam_selesai' => '07:45'
                ]);
            }
            // Ambil lagi datanya setelah dibuat
            $jamPelajaran = MasterJamPelajaran::where('hari', $hari)->orderBy('jam_ke', 'asc')->get();
        }

        return view('admin.master_jam.edit', compact('jamPelajaran', 'hari'));
    }

    /**
     * Update data jam pelajaran untuk satu hari.
     */
    public function update(Request $request, $hari)
    {
        $request->validate([
            'jam_mulai' => 'required|array',
            // Terima format HH:MM atau HH:MM:SS
            'jam_mulai.*' => 'required|date_format:H:i,H:i:s', 
            'jam_selesai' => 'required|array',
            // Terima format HH:MM atau HH:MM:SS
            'jam_selesai.*' => 'required|date_format:H:i,H:i:s|after:jam_mulai.*', 
        ],[
            'jam_selesai.*.after' => 'Jam Selesai harus setelah Jam Mulai.'
        ]);

        // Loop dan update setiap jam pelajaran (jam ke 1 s/d 10)
        foreach ($request->jam_mulai as $jam_ke => $jam_mulai) {
            
            // Pastikan jam_selesai ada untuk jam_ke ini
            if (isset($request->jam_selesai[$jam_ke])) {
                $jam_selesai = $request->jam_selesai[$jam_ke];
                
                MasterJamPelajaran::where('hari', $hari)
                    ->where('jam_ke', $jam_ke)
                    ->update([
                        'jam_mulai' => $jam_mulai,
                        'jam_selesai' => $jam_selesai,
                    ]);
            }
        }

        return redirect()->route('admin.master-jam.index')->with('success', 'Rentang jam pelajaran untuk Hari ' . $hari . ' berhasil diperbarui.');
    }
}