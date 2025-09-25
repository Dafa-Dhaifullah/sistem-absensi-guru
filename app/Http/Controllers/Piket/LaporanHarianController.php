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
    // 1. Validasi
    $request->validate([
        'status_guru' => 'nullable|array', 
        'status_guru.*' => 'nullable|in:Hadir,Sakit,Izin,DL,Alpa',
        'kejadian_penting' => 'nullable|string',
        'tindak_lanjut' => 'nullable|string',
    ]);

    $today = now()->toDateString();

    // ========================================================
    // ## LOGIKA BARU: HANYA PROSES YANG DI-SUBMIT DARI FORM ##
    // ========================================================
    if ($request->filled('status_guru')) {

        foreach ($request->status_guru as $idGuru => $status) {

            // Jika user memilih status (bukan '-- Belum Diabsen --')
            if (!empty($status)) {
                // Update atau Buat record baru
                \App\Models\LaporanHarian::updateOrCreate(
                    // Kunci pencarian
                    ['tanggal' => $today, 'data_guru_id' => $idGuru],
                    // Data yang di-update/insert
                    ['status' => $status]
                );
            } 
            // Jika user memilih '-- Belum Diabsen --' (status dikirim kosong)
            else {
                // Hapus record-nya dari database
                \App\Models\LaporanHarian::where('tanggal', $today)
                                         ->where('data_guru_id', $idGuru)
                                         ->delete();
            }
        }
    }

    // 2. SIMPAN LOGBOOK
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