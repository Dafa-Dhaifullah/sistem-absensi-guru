<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\LaporanHarian;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use Carbon\Carbon;

class AbsenController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input sekarang menerima array
        $validated = $request->validate([
            'qr_token' => 'required|string',
            'foto_selfie' => 'required|image|max:2048',
            'jadwal_ids' => 'required|array',
            'jadwal_ids.*' => 'exists:jadwal_pelajaran,id',
        ]);

        $user = Auth::user();
        $today = now('Asia/Jakarta');
        
        // Ambil semua objek jadwal dari array ID yang diterima
        $jadwals = JadwalPelajaran::find($validated['jadwal_ids']);
        $jadwalPertama = $jadwals->first();

        // --- Validasi Keamanan & Logika ---
        foreach ($jadwals as $jadwal) {
            if ($jadwal->user_id != $user->id) {
                return redirect()->back()->withErrors(['foto_selfie' => 'Jadwal tidak sesuai dengan akun Anda.']);
            }
            if (LaporanHarian::where('jadwal_pelajaran_id', $jadwal->id)->exists()) {
                return redirect()->back()->withErrors(['foto_selfie' => 'Anda sudah absen untuk salah satu jam di blok ini.']);
            }
        }
        
        // Validasi QR Code (cocokkan dengan kelas dari jadwal pertama)
        if (Crypt::decryptString($validated['qr_token']) !== $jadwalPertama->kelas) {
            return redirect()->back()->withErrors(['foto_selfie' => 'QR Code tidak sesuai dengan kelas yang dijadwalkan.']);
        }

        // --- Tentukan Status Keterlambatan (berdasarkan jam pertama) ---
        $masterJamPertama = MasterJamPelajaran::where('hari', $jadwalPertama->hari)->where('jam_ke', $jadwalPertama->jam_ke)->first();
        $batasToleransi = Carbon::parse($today->toDateString() . ' ' . $masterJamPertama->jam_mulai)->addMinutes(15);
        $statusKeterlambatan = ($today->isAfter($batasToleransi)) ? 'Terlambat' : 'Tepat Waktu';

        // --- Simpan Foto dan Laporan ---
        $pathFoto = $request->file('foto_selfie')->store('public/selfies/' . $today->format('Y-m'));
        
        // LOOPING untuk menyimpan laporan untuk SETIAP jam di blok
        foreach ($jadwals as $jadwal) {
            LaporanHarian::create([
                'jadwal_pelajaran_id' => $jadwal->id,
                'user_id' => $user->id,
                'tanggal' => $today->toDateString(),
                'status' => 'Hadir',
                'jam_absen' => $today->toTimeString(),
                'foto_selfie_path' => $pathFoto,
                'status_keterlambatan' => $statusKeterlambatan,
                'diabsen_oleh' => $user->id,
            ]);
        }

        return redirect()->route('guru.dashboard')->with('success', 'Absensi untuk kelas ' . $jadwalPertama->kelas . ' berhasil!');
    }
}