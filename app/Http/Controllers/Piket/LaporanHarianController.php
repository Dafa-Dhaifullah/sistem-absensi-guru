<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use App\Models\LogbookPiket;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use Illuminate\Support\Carbon;

class LaporanHarianController extends Controller
{
    /**
     * Menyimpan atau memperbarui laporan harian dari form absensi Piket.
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'status_guru' => 'nullable|array', 
            'status_guru.*' => 'nullable|in:Sakit,Izin,DL,Alpa',
            'keterangan_piket' => 'nullable|array',
            'keterangan_piket.*' => 'nullable|string|max:255',
        ],[
            'status_guru.*.in' => 'Anda hanya dapat mengubah status menjadi Sakit, Izin, Alpa, atau DL.'
        ]);

        $today = now('Asia/Jakarta')->toDateString();
        $statusInput = $request->input('status_guru', []);
        $keteranganInput = $request->input('keterangan_piket', []);
        
        if (!empty($statusInput)) {
            foreach ($statusInput as $idGuru => $status) {
                
                // Cari laporan yang sudah ada untuk guru ini hari ini
                $laporanExist = LaporanHarian::where('tanggal', $today)
                                            ->where('user_id', $idGuru)
                                            ->first();

                // PENTING: Jika guru sudah absen mandiri (status 'Hadir'), lewati.
                if ($laporanExist && $laporanExist->status == 'Hadir') {
                    continue; 
                }

                $keterangan = $keteranganInput[$idGuru] ?? null;
                
                // ==========================================================
                // ## LOGIKA BARU: CATAT LOG OVERRIDE ##
                // ==========================================================
                $statusLama = $laporanExist ? $laporanExist->status : 'Belum Absen';
                $statusBaru = $status ?: 'Belum Absen'; // Jika dikosongkan, anggap 'Belum Absen'
                
                // Catat log HANYA JIKA ada perubahan status
                if ($statusLama !== $statusBaru) {
                    OverrideLog::create([
                        'piket_user_id' => auth()->id(),
                        'guru_user_id' => $idGuru,
                        'tanggal' => $today,
                        'status_lama' => $statusLama,
                        'status_baru' => $statusBaru,
                        'keterangan' => $keterangan,
                    ]);
                }
                // ==========================================================


                if (!empty($status)) {
                    LaporanHarian::updateOrCreate(
                        ['tanggal' => $today, 'user_id' => $idGuru],
                        [
                            'status' => $status, 
                            'diabsen_oleh' => auth()->id(), // Catat ID Guru Piket
                            'keterangan_piket' => $keterangan
                        ]
                    );
                } else {
                    // Jika status dikosongkan, hapus record-nya
                    if ($laporanExist) {
                        $laporanExist->delete();
                    }
                }
            }
        }
        
        // Simpan Logbook Kejadian
        LogbookPiket::updateOrCreate(
            ['tanggal' => $today],
            [
                'kejadian_penting' => $request->kejadian_penting,
                'tindak_lanjut' => $request->tindak_lanjut,
            ]
        );
        
        return redirect()->route('piket.dashboard')->with('success', 'Laporan berhasil diperbarui.');
    }
}

