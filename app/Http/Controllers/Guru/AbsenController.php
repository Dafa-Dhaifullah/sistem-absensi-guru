<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Crypt; // Tidak diperlukan lagi
// use Illuminate\Contracts\Encryption\DecryptException; // Tidak diperlukan lagi
use App\Models\LaporanHarian;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AbsenController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'qr_token' => 'required|string',
            'foto_selfie' => 'required|image|max:15360', 
            'jadwal_ids' => 'required|array',
            'jadwal_ids.*' => 'exists:jadwal_pelajaran,id',
        ]);

        $user = Auth::user();
        $today = now('Asia/Jakarta');
        
        $jadwals = JadwalPelajaran::find($validated['jadwal_ids']);
        if($jadwals->isEmpty()) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Jadwal tidak valid.']);
        }
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
        
        // ==========================================================
        // ## REVISI LOGIKA VALIDASI QR CODE ##
        // ==========================================================
        // Hapus try-catch dan Crypt::decryptString
        // Ganti dengan perbandingan teks biasa
        $qrKelas = $validated['qr_token'];
        $jadwalKelas = $jadwalPertama->kelas;

        if ($qrKelas !== $jadwalKelas) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Gagal validasi QR Code: QR Code tidak sesuai dengan kelas yang dijadwalkan.']);
        }
        // ==========================================================

        // --- Tentukan Status Keterlambatan (berdasarkan jam pertama) ---
        $masterJamPertama = MasterJamPelajaran::where('hari', $jadwalPertama->hari)->where('jam_ke', $jadwalPertama->jam_ke)->first();
        if (!$masterJamPertama) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Master jam pelajaran tidak ditemukan.']);
        }
        $batasToleransi = Carbon::parse($today->toDateString() . ' ' . $masterJamPertama->jam_mulai)->addMinutes(15);
        $statusKeterlambatan = ($today->isAfter($batasToleransi)) ? 'Terlambat' : 'Tepat Waktu';

        // --- Logika Kompresi Gambar (Tidak berubah) ---
        try {
            $image = $request->file('foto_selfie');
            $manager = new ImageManager(new Driver());
            $processedImage = $manager->read($image);
            $processedImage->scaleDown(width: 800);
            $encodedImage = $processedImage->toJpeg(75);
            $fileName = Str::uuid() . '.jpg';
            $pathFoto = 'public/selfies/' . $today->format('Y-m') . '/' . $fileName;
            Storage::put($pathFoto, (string) $encodedImage);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Gagal memproses gambar: ' . $e->getMessage()]);
        }
        
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