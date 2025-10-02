<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\LaporanHarian;
use Carbon\Carbon;

class AbsenController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input Dasar
        $request->validate([
            'qr_token' => 'required|string',
            'foto_selfie' => 'required|image|max:2048',
        ]);

        $user = Auth::user();
        $today = now('Asia/Jakarta');

        // 2. Validasi Token QR Code (Ini sudah benar)
        try {
            $decryptedToken = Crypt::decryptString($request->qr_token);
            $tokenData = json_decode($decryptedToken, true);
            if (time() > $tokenData['valid_until'] || $tokenData['secret'] !== config('app.key')) {
                throw new \Exception('Token tidak valid atau kedaluwarsa.');
            }
        } catch (DecryptException | \Exception $e) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Gagal memvalidasi QR Code. Silakan scan ulang.']);
        }

        // ==========================================================
        // ## TAMBAHAN: Validasi Foto (EXIF Data) ##
        // ==========================================================
        $imagePath = $request->file('foto_selfie')->getRealPath();
        // Cek apakah fungsi exif_read_data ada di server Anda
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($imagePath);
            // Jika tidak ada data EXIF 'Make' (merek kamera) atau 'Model', tolak foto
            if (empty($exif['Make']) && empty($exif['Model'])) {
                 return redirect()->back()->withErrors(['foto_selfie' => 'Foto yang diunggah tidak valid. Harap gunakan foto langsung dari kamera.']);
            }
        }
        // ==========================================================


        // 3. Tentukan Status Keterlambatan (Ini sudah benar)
        $jadwalPertama = $user->jadwalPelajaran()
            ->where('hari', ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'][$today->format('l')])
            ->orderBy('jam_ke', 'asc')
            ->first();

        if (!$jadwalPertama) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Anda tidak memiliki jadwal mengajar hari ini.']);
        }

        $masterJamPertama = \App\Models\MasterJamPelajaran::where('hari', $jadwalPertama->hari)
            ->where('jam_ke', $jadwalPertama->jam_ke)
            ->first();
        
        $batasWaktuMasuk = Carbon::parse($masterJamPertama->jam_mulai)->addMinutes(15);
        $statusKeterlambatan = ($today->isAfter($batasWaktuMasuk)) ? 'Terlambat' : 'Tepat Waktu';

        // 4. Simpan Foto Selfie (Ini sudah benar)
        $pathFoto = $request->file('foto_selfie')->store('public/selfies/' . $today->format('Y-m'));

        // 5. Simpan Laporan ke Database (Ini sudah benar)
        LaporanHarian::create([
            'tanggal' => $today->toDateString(),
            'user_id' => $user->id,
            'status' => 'Hadir',
            'jam_absen' => $today->toTimeString(),
            'foto_selfie_path' => $pathFoto,
            'status_keterlambatan' => $statusKeterlambatan,
            'diabsen_oleh' => $user->id,
        ]);

        return redirect()->route('guru.dashboard')->with('success', 'Absensi berhasil! Status Anda: ' . $statusKeterlambatan);
    }
}

