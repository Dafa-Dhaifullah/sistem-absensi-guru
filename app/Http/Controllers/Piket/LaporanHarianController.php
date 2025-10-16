<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use App\Models\LogbookPiket;
use App\Models\JadwalPelajaran;
use App\Models\OverrideLog;

class LaporanHarianController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'status_override' => 'nullable|array', 
            'status_override.*' => 'nullable|in:Sakit,Izin,DL,Alpa',
            'keterangan_piket' => 'nullable|array',
        ]);

        $today = now('Asia/Jakarta');
        $statusOverrides = $request->input('status_override', []);
        
        foreach ($statusOverrides as $firstJadwalId => $status) {
            if (empty($status)) continue;

            $jadwalPertama = JadwalPelajaran::findOrFail($firstJadwalId);
            $keterangan = $request->input('keterangan_piket.' . $firstJadwalId);
            
            // Temukan semua jadwal yang ada di blok ini
            $jadwalIdsInBlock = JadwalPelajaran::where('user_id', $jadwalPertama->user_id)
                ->where('kelas', $jadwalPertama->kelas)
                ->where('hari', $jadwalPertama->hari)
                // Logika sederhana untuk blok: cari jam berurutan
                ->where('jam_ke', '>=', $jadwalPertama->jam_ke) 
                ->pluck('id');

            // Proses setiap jadwal dalam blok
            foreach ($jadwalIdsInBlock as $jadwalId) {
                $laporan = LaporanHarian::firstOrNew(['jadwal_pelajaran_id' => $jadwalId]);
                
                if ($laporan->exists && $laporan->status === 'Hadir') continue;
                
                // (Logika OverrideLog sama, bisa ditambahkan di sini)

                $laporan->fill([
                    'user_id' => $jadwalPertama->user_id,
                    'tanggal' => $today->toDateString(),
                    'status' => $status,
                    'jam_absen' => $today->toTimeString(),
                    'diabsen_oleh' => auth()->id(),
                    'keterangan_piket' => $keterangan,
                ])->save();
            }
        }

        // Simpan Logbook
        LogbookPiket::updateOrCreate(
            ['tanggal' => $today->toDateString()],
            ['kejadian_penting' => $request->kejadian_penting, 'tindak_lanjut' => $request->tindak_lanjut]
        );
        
        return redirect()->route('piket.dashboard')->with('success', 'Perubahan berhasil disimpan.');
    }
}