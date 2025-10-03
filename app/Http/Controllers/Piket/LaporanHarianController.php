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
        // 1. Validasi
        $request->validate([
            'status_guru' => 'nullable|array', 
            // REVISI: 'Hadir' DIHAPUS dari daftar status yang valid untuk override.
            'status_guru.*' => 'nullable|in:Sakit,Izin,DL,Alpa',
            'kejadian_penting' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
        ],[
            'status_guru.*.in' => 'Anda hanya dapat mengubah status menjadi Sakit, Izin, Alpa, atau DL.'
        ]);

        $today = now('Asia/Jakarta')->toDateString();
        
        // Hanya proses data yang di-submit dari form
        if ($request->filled('status_guru')) {
            foreach ($request->status_guru as $idGuru => $status) {
                
                // Jika user memilih status (bukan '-- Belum Diabsen --')
                if (!empty($status)) {
                    LaporanHarian::updateOrCreate(
                        // Kunci
                        ['tanggal' => $today, 'user_id' => $idGuru],
                        // Data (catat siapa yang mengabsenkan)
                        ['status' => $status, 'diabsen_oleh' => auth()->id()]
                    );
                } 
                // Jika user memilih '-- Belum Diabsen --'
                else {
                    // Hapus record-nya agar kembali ke status default
                    LaporanHarian::where('tanggal', $today)
                                 ->where('user_id', $idGuru)
                                 ->delete();
                }
            }
        }
        
        // Simpan Logbook
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

