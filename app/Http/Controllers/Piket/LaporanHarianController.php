<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\LaporanHarian;
use App\Models\LogbookPiket;
use App\Models\DataGuru;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;

class LaporanHarianController extends Controller
{
    /**
     * Menyimpan laporan harian dari form absensi Piket.
     */
   public function store(Request $request)
    {
        // 1. Validasi (status_guru sekarang boleh null)
        $request->validate([
            'status_guru' => 'nullable|array', 
            'status_guru.*' => 'nullable|in:Hadir,Sakit,Izin,DL,Alpa', // 'Alpa' sekarang valid
            'kejadian_penting' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
        ]);

        $today = now()->toDateString();
        
        // ========================================================
        // ## LOGIKA BARU: HANYA SIMPAN YANG DI-SUBMIT DARI FORM ##
        // ========================================================
        if ($request->filled('status_guru')) {
            
            foreach ($request->status_guru as $idGuru => $status) {
                
                // Jika statusnya TIDAK KOSONG (bukan '-- Belum Diabsen --')
                if (!empty($status)) {
                    LaporanHarian::updateOrCreate(
                        // Kunci
                        ['tanggal' => $today, 'data_guru_id' => $idGuru],
                        // Data
                        ['status' => $status]
                    );
                } 
                // (Opsional: Jika user pilih '-- Belum Diabsen --', hapus record-nya)
                else {
                    LaporanHarian::where('tanggal', $today)
                                 ->where('data_guru_id', $idGuru)
                                 ->delete();
                }
            }
        }
        
        // 2. SIMPAN LOGBOOK (Logika ini sudah benar)
        \App\Models\LogbookPiket::updateOrCreate(
            ['tanggal' => $today],
            [
                'kejadian_penting' => $request->kejadian_penting,
                'tindak_lanjut' => $request->tindak_lanjut,
            ]
        );
        
        return redirect()->route('piket.dashboard')->with('success', 'Laporan berhasil diperbarui.');
    }
}