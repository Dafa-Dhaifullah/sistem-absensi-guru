<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use App\Models\LaporanHarian;
use App\Models\LogbookPiket;
use App\Models\DataGuru;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanHarianController extends Controller
{
    /**
     * Menyimpan laporan harian dari form absensi Piket.
     */
    public function store(Request $request)
    {
        // 1. Validasi input dasar
        $request->validate([
            'status_guru' => 'required|array', // Pastikan input 'status_guru' ada
            'status_guru.*' => 'required|in:Hadir,Sakit,Izin,DL', // Validasi status yang masuk
            'kejadian_penting' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
        ]);

        $today = now()->toDateString();

        // TODO: Cek lagi, jangan-jangan sudah di-submit?

        // 2. Ambil daftar ID guru yang "Wajib Hadir" hari itu
        // (Kita ulangi query singkat dari Piket/DashboardController)
        // Ini untuk menentukan siapa yang "Alpa"
        
        Carbon::setLocale('id_ID');
        $hariIni = now()->translatedFormat('l');
        $tipeMinggu = \App\Models\KalenderBlok::where('tanggal_mulai', '<=', $today)
                                             ->where('tanggal_selesai', '>=', $today)
                                             ->first();
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) { $blokValid[] = $tipeMinggu->tipe_minggu; }

        $guruWajibHadirIds = \App\Models\JadwalPelajaran::where('hari', $hariIni)
                                ->whereIn('tipe_blok', $blokValid)
                                ->pluck('data_guru_id') // Ambil ID gurunya
                                ->unique();

        $statusInput = $request->input('status_guru');
        
        // 3. Simpan Laporan Harian (termasuk "Alpa" otomatis)
        foreach ($guruWajibHadirIds as $idGuru) {
            
            // Cek status yang di-input dari form
            $status = $statusInput[$idGuru] ?? 'Alpa'; // Jika tidak ada di input, berarti ALPA

            LaporanHarian::create([
                'tanggal' => $today,
                'data_guru_id' => $idGuru,
                'status' => $status,
            ]);
        }

        // 4. Simpan Logbook
        if ($request->filled('kejadian_penting') || $request->filled('tindak_lanjut')) {
            LogbookPiket::create([
                'tanggal' => $today,
                'kejadian_penting' => $request->kejadian_penting,
                'tindak_lanjut' => $request->tindak_lanjut,
            ]);
        }

        // 5. Kembalikan ke dashboard dengan pesan sukses
        return redirect()->route('piket.dashboard')->with('success', 'Laporan harian berhasil disimpan.');
    }
}