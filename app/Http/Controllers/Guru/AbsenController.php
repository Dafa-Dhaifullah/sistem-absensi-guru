<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\LaporanHarian;
use App\Models\JadwalPelajaran;
use App\Models\QrCodeLog;
use Carbon\Carbon;

class AbsenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'qr_token' => 'required|string',
            'foto_selfie' => 'required|image|max:2048',
        ]);

        $user = Auth::user();
        $today = now('Asia/Jakarta');

        // REVISI: Validasi Token QR Code
        try {
            // Update status log menjadi 'Di-scan'
            $qrLog = QrCodeLog::where('token', $request->qr_token)->firstOrFail();
            
            // Cek apakah token sudah pernah di-scan
            if ($qrLog->status == 'Di-scan') {
                throw new \Exception('QR Code ini sudah pernah digunakan.');
            }
            
            // REVISI: Waktu kedaluwarsa jadi 2 menit + toleransi 15 detik
            $waktuKadaluarsaDenganToleransi = Carbon::parse($qrLog->waktu_kadaluarsa)->addSeconds(15);
            if ($today->isAfter($waktuKadaluarsaDenganToleransi)) {
                $qrLog->update(['status' => 'Kedaluwarsa']);
                throw new \Exception('QR Code sudah kedaluwarsa.');
            }
            
            // Jika berhasil, update status log
            $qrLog->increment('jumlah_scan');
            $qrLog->update(['status' => 'Di-scan']);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Gagal validasi QR Code: ' . $e->getMessage()]);
        }

        // REVISI: Patokan keterlambatan jadi 15 menit
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
        
        // REVISI: Beri toleransi 15 menit dari jam mulai
        $batasWaktuMasuk = Carbon::parse($masterJamPertama->jam_mulai)->addMinutes(15);
        $statusKeterlambatan = ($today->isAfter($batasWaktuMasuk)) ? 'Terlambat' : 'Tepat Waktu';

        // ... (Sisa kode untuk Simpan Foto dan Simpan Laporan tidak berubah)
        $pathFoto = $request->file('foto_selfie')->store('public/selfies/' . $today->format('Y-m'));
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