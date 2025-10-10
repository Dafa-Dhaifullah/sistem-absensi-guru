<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
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
            'foto_selfie' => 'required|image|max:2048',
        ]);

        $user = Auth::user();
        $today = now('Asia/Jakarta');

        // ==========================================================
        // ## REVISI LOGIKA PENGAMANAN ##
        // ==========================================================
        // Cari laporan yang sudah ada untuk hari ini
        $laporanHariIni = LaporanHarian::where('user_id', $user->id)
                                ->whereDate('tanggal', $today->toDateString())
                                ->first();
        
        // Blokir HANYA JIKA guru ini sudah pernah absen mandiri (status Hadir)
        if ($laporanHariIni && $laporanHariIni->status == 'Hadir' && $laporanHariIni->diabsen_oleh == $user->id) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Anda sudah melakukan absensi mandiri hari ini.']);
        }
        // ==========================================================


        // 2. Validasi Token QR Code (Tidak berubah)
        try {
            $decryptedToken = Crypt::decryptString($request->qr_token);
            $tokenData = json_decode($decryptedToken, true);
            
            $waktuKadaluarsaDenganToleransi = Carbon::createFromTimestamp($tokenData['valid_until'])->addSeconds(15);
            if ($today->isAfter($waktuKadaluarsaDenganToleransi) || $tokenData['secret'] !== config('app.key')) {
                throw new \Exception('Token tidak valid atau kedaluwarsa.');
            }
            // (Logika update QrCodeLog sudah dihapus, jadi aman)

        } catch (DecryptException | \Exception $e) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Gagal validasi QR Code: ' . $e->getMessage()]);
        }

        // 3. Tentukan Status Keterlambatan (Tidak berubah)
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

        // 4. Simpan Foto Selfie (Tidak berubah)
        $pathFoto = $request->file('foto_selfie')->store('public/selfies/' . $today->format('Y-m'));

        // ==========================================================
        // ## REVISI LOGIKA PENYIMPANAN ##
        // ==========================================================
        // 5. Gunakan updateOrCreate untuk menimpa data lama (jika ada)
        LaporanHarian::updateOrCreate(
            // Kunci untuk mencari:
            [
                'tanggal' => $today->toDateString(),
                'user_id' => $user->id,
            ],
            // Data yang di-update atau di-insert:
            [
                'status' => 'Hadir',
                'jam_absen' => $today->toTimeString(),
                'foto_selfie_path' => $pathFoto,
                'status_keterlambatan' => $statusKeterlambatan,
                'diabsen_oleh' => $user->id,
                'keterangan_piket' => null // Hapus catatan piket lama
            ]
        );

        return redirect()->route('guru.dashboard')->with('success', 'Absensi berhasil! Status Anda telah diperbarui menjadi Hadir.');
    }
}