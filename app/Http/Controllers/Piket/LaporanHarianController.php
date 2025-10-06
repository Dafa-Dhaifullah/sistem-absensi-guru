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
        $request->validate([
            'status_guru' => 'nullable|array', 
            'status_guru.*' => 'nullable|in:Sakit,Izin,DL,Alpa',
            'keterangan_piket' => 'nullable|array', // Validasi untuk keterangan
            'keterangan_piket.*' => 'nullable|string|max:255',
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

                // PENTING: Jika laporan sudah ada DAN statusnya 'Hadir' (absen mandiri),
                // maka lewati (JANGAN diubah oleh piket).
                if ($laporanExist && $laporanExist->status == 'Hadir') {
                    continue; // Lanjut ke guru berikutnya
                }

                $keterangan = $keteranganInput[$idGuru] ?? null;

                if (!empty($status)) {
                    LaporanHarian::updateOrCreate(
                        ['tanggal' => $today, 'user_id' => $idGuru],
                        [
                            'status' => $status, 
                            'diabsen_oleh' => auth()->id(),
                            'keterangan_piket' => $keterangan // Simpan keterangan
                        ]
                    );
                } else {
                    LaporanHarian::where('tanggal', $today)
                                 ->where('user_id', $idGuru)
                                 ->delete();
                }
            }
        }
        
        // Simpan Logbook (tidak berubah)
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

