<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use App\Models\LogbookPiket;
use App\Models\JadwalPelajaran;
use App\Models\OverrideLog;
use Illuminate\Support\Facades\DB;

class LaporanHarianController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'status_override' => 'nullable|array', 
            'status_override.*' => 'nullable|in:Sakit,Izin,DL,Alpa',
            'keterangan_piket' => 'nullable|array',
            'keterangan_piket.*' => 'nullable|string|max:255',
            'kejadian_penting' => 'nullable|string',
            'tindak_lanjut' => 'nullable|string',
        ]);

        $today = now('Asia/Jakarta');
        $statusOverrides = $request->input('status_override', []);
        
        DB::beginTransaction();
        try {
            foreach ($statusOverrides as $firstJadwalId => $status) {
                if (empty($status)) continue; 

                $jadwalPertama = JadwalPelajaran::findOrFail($firstJadwalId);
                $keterangan = $request->input('keterangan_piket.' . $firstJadwalId);
                
                // --- Logika untuk menemukan semua jadwal dalam satu blok ---
                $jadwalIdsInBlock = JadwalPelajaran::where('user_id', $jadwalPertama->user_id)
                    ->where('kelas', $jadwalPertama->kelas)
                    ->where('hari', $jadwalPertama->hari)
                    ->where('tipe_blok', $jadwalPertama->tipe_blok)
                    ->where('jam_ke', '>=', $jadwalPertama->jam_ke)
                    ->orderBy('jam_ke', 'asc')
                    ->pluck('id', 'jam_ke');

                $blokIds = [];
                $lastJam = $jadwalPertama->jam_ke - 1;
                foreach($jadwalIdsInBlock as $jam => $id) {
                    if ($jam == $lastJam + 1) {
                        $blokIds[] = $id;
                        $lastJam = $jam;
                    } else {
                        break; 
                    }
                }
                // --- Akhir logika blok ---

                $laporanPertama = LaporanHarian::where('jadwal_pelajaran_id', $firstJadwalId)->first();
                $statusLama = $laporanPertama->status ?? 'Belum Absen';

                // ==========================================================
                // ## REVISI: LOG HANYA DIBUAT SEKALI PER BLOK ##
                // ==========================================================
                // Catat log (hanya jika status berubah)
                if ($statusLama !== $status) {
                    OverrideLog::create([
                        'piket_user_id' => auth()->id(),
                        'jadwal_pelajaran_id' => $firstJadwalId, // Simpan ID jadwal pertama sebagai referensi
                        'status_lama' => $statusLama,
                        'status_baru' => $status,
                        'keterangan' => $keterangan,
                    ]);
                }

                // Proses setiap jadwal dalam blok
                foreach ($blokIds as $jadwalId) {
                    $laporan = LaporanHarian::firstOrNew([
                        'tanggal' => $today->toDateString(),
                        'jadwal_pelajaran_id' => $jadwalId
                    ]);
                    
                    if ($laporan->exists && $laporan->status === 'Hadir' && $laporan->diabsen_oleh == $laporan->user_id) {
                        continue;
                    }
                    
                    $jadwalTerkait = JadwalPelajaran::find($jadwalId);

                    // Simpan atau update record absensi
                    $laporan->fill([
                        'user_id' => $jadwalTerkait->user_id,
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
            
            DB::commit();
            
            return redirect()->route('piket.dashboard')->with('success', 'Perubahan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors('Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }
}