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
        // 1. Validasi input (Ini sudah benar)
        $request->validate([
            'status_guru' => 'nullable|array', 
            'status_guru.*' => 'nullable|in:Hadir,Sakit,Izin,DL,Alpa',
            'kejadian_penting' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
        ]);

        $today = now('Asia/Jakarta');
        $statusInput = $request->input('status_guru', []);

        // ========================================================
        // ## 2. SINKRONISASI LOGIKA QUERY ##
        // (Logika ini disamakan persis dengan DashboardController)
        // ========================================================
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $hariIni = $hariMap[$today->format('l')];
        
        $tipeMinggu = KalenderBlok::where('tanggal_mulai', '<=', $today)
                                  ->where('tanggal_selesai', '>=', $today)
                                  ->first();
        
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }
        
        // REVISI: Ambil user_id, bukan data_guru_id
        $guruWajibHadirIds = JadwalPelajaran::where('hari', $hariIni)
                                ->whereIn('tipe_blok', $blokValid)
                                ->pluck('user_id')
                                ->unique();
        // ========================================================

        // 3. Simpan Laporan Harian (Logika ini sudah benar)
        if ($request->filled('status_guru')) {
            foreach ($request->status_guru as $idGuru => $status) {
                if (!empty($status)) {
                    LaporanHarian::updateOrCreate(
                        // REVISI: Gunakan user_id sebagai kunci
                        ['tanggal' => $today->toDateString(), 'user_id' => $idGuru],
                        ['status' => $status, 'diabsen_oleh' => auth()->id()] // Catat siapa yang mengabsenkan
                    );
                } else {
                    LaporanHarian::where('tanggal', $today->toDateString())
                                 // REVISI: Hapus berdasarkan user_id
                                 ->where('user_id', $idGuru)
                                 ->delete();
                }
            }
        }
        
        // 4. Simpan Logbook (Logika ini sudah benar)
        LogbookPiket::updateOrCreate(
            ['tanggal' => $today->toDateString()],
            [
                'kejadian_penting' => $request->kejadian_penting,
                'tindak_lanjut' => $request->tindak_lanjut,
            ]
        );
        
        return redirect()->route('piket.dashboard')->with('success', 'Laporan berhasil diperbarui.');
    }
}
