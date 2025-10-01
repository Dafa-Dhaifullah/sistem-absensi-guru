<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt; // Untuk dekripsi token
use Illuminate\Contracts\Encryption\DecryptException; // Untuk menangani error dekripsi
use App\Models\LaporanHarian;
use App\Models\JadwalPelajaran;
use Carbon\Carbon;

class AbsenController extends Controller
{
    /**
     * Menyimpan data absensi mandiri dari guru.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input Dasar
        $request->validate([
            'qr_token' => 'required|string',
            'foto_selfie' => 'required|image|max:2048', // Maksimal 2MB
        ]);

        $user = Auth::user();
        $today = now('Asia/Jakarta');

        // 2. Validasi Token QR Code
        try {
            $decryptedToken = Crypt::decryptString($request->qr_token);
            $tokenData = json_decode($decryptedToken, true);

            // Cek apakah token valid dan belum kedaluwarsa (misal, valid 1 menit)
            if (time() > $tokenData['valid_until'] || $tokenData['secret'] !== config('app.key')) {
                throw new \Exception('Token tidak valid atau kedaluwarsa.');
            }
        } catch (DecryptException | \Exception $e) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Gagal memvalidasi QR Code. Silakan scan ulang.']);
        }

        // 3. Tentukan Status Keterlambatan
        $jadwalPertama = $user->dataGuru->jadwalPelajaran()
            ->where('hari', ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'][$today->format('l')])
            ->orderBy('jam_ke', 'asc')
            ->first();

        if (!$jadwalPertama) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Anda tidak memiliki jadwal mengajar hari ini.']);
        }

        $masterJamPertama = \App\Models\MasterJamPelajaran::where('hari', $jadwalPertama->hari)
            ->where('jam_ke', $jadwalPertama->jam_ke)
            ->first();
        
        // Beri toleransi 15 menit dari jam mulai pelajaran pertama
        $batasWaktuMasuk = Carbon::parse($masterJamPertama->jam_mulai)->addMinutes(15);
        
        $statusKeterlambatan = ($today->isAfter($batasWaktuMasuk)) ? 'Terlambat' : 'Tepat Waktu';

        // 4. Simpan Foto Selfie
        $pathFoto = $request->file('foto_selfie')->store('public/selfies/' . $today->format('Y-m'));

        // 5. Simpan Laporan ke Database
        LaporanHarian::create([
            'tanggal' => $today->toDateString(),
            'data_guru_id' => $user->dataGuru->id,
            'status' => 'Hadir',
            'jam_absen' => $today->toTimeString(),
            'foto_selfie_path' => $pathFoto,
            'status_keterlambatan' => $statusKeterlambatan,
            'diabsen_oleh' => $user->id,
        ]);

        return redirect()->route('guru.dashboard')->with('success', 'Absensi berhasil! Status Anda: ' . $statusKeterlambatan);
    }
}