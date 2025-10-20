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
                if (empty($status)) continue; // Lewati jika tidak ada status override dipilih

                $jadwalPertama = JadwalPelajaran::findOrFail($firstJadwalId);
                $keterangan = $request->input('keterangan_piket.' . $firstJadwalId);
                
                // --- Logika baru untuk menemukan semua jadwal dalam satu blok ---
                // Asumsi blok adalah jam berurutan di kelas yang sama oleh guru yang sama
                $jadwalIdsInBlock = JadwalPelajaran::where('user_id', $jadwalPertama->user_id)
                    ->where('kelas', $jadwalPertama->kelas)
                    ->where('hari', $jadwalPertama->hari)
                    ->where('tipe_blok', $jadwalPertama->tipe_blok)
                    ->where('jam_ke', '>=', $jadwalPertama->jam_ke)
                    ->orderBy('jam_ke', 'asc')
                    ->pluck('id');
                
                // Kumpulkan ID jadwal yang berurutan
                $blokIds = [];
                $lastJam = $jadwalPertama->jam_ke - 1;
                foreach($jadwalIdsInBlock as $jadwalId) {
                    $currentJadwal = JadwalPelajaran::find($jadwalId); // Ambil data jam_ke
                    if ($currentJadwal->jam_ke == $lastJam + 1) {
                        $blokIds[] = $currentJadwal->id;
                        $lastJam = $currentJadwal->jam_ke;
                    } else {
                        break; // Berhenti jika jam tidak berurutan
                    }
                }
                // --- Akhir logika blok ---

                // Proses setiap jadwal dalam blok
                foreach ($blokIds as $jadwalId) {
                    $laporan = LaporanHarian::firstOrNew([
                        'tanggal' => $today->toDateString(),
                        'jadwal_pelajaran_id' => $jadwalId
                    ]);
                    
                    // Keamanan: Jangan override jika guru sudah absen mandiri
                    if ($laporan->exists && $laporan->status === 'Hadir' && $laporan->diabsen_oleh == $laporan->user_id) {
                        continue;
                    }
                    
                    $jadwalTerkait = JadwalPelajaran::find($jadwalId);

                    // Catat log override (hanya jika status berubah)
                    if ($laporan->status !== $status) {
                        OverrideLog::create([
                            'piket_user_id' => auth()->id(),
                            'jadwal_pelajaran_id' => $jadwalId,
                            'status_lama' => $laporan->status ?? 'Belum Absen',
                            'status_baru' => $status,
                            'keterangan' => "Override: " . $keterangan,
                        ]);
                    }

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
                [
                    'kejadian_penting' => $request->kejadian_penting,
                    'tindak_lanjut' => $request->tindak_lanjut,
                ]
            );
            
            DB::commit(); // Simpan semua perubahan jika berhasil
            
            return redirect()->route('piket.dashboard')->with('success', 'Perubahan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua jika ada error
            return redirect()->back()->withErrors('Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }
}