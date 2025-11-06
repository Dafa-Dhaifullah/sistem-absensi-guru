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
                
                // (Optimasi kecil: Ambil $guruId satu kali)
                $guruId = $jadwalPertama->user_id;

                // --- Logika untuk menemukan semua jadwal dalam satu blok ---
                $jadwalIdsInBlock = JadwalPelajaran::where('user_id', $guruId)
                    ->where('kelas', $jadwalPertama->kelas)
                    ->where('hari', $jadwalPertama->hari)
                    ->where('tipe_blok', $jadwalPertama->tipe_blok)
                    ->where('jam_ke', '>=', $jadwalPertama->jam_ke)
                    ->orderBy('jam_ke', 'asc')
                    ->pluck('id', 'jam_ke'); // Ambil jam_ke sebagai kunci

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

                // Cek status lama HANYA pada jam pertama
                $laporanPertama = LaporanHarian::where('tanggal', $today->toDateString())
                                    ->where('jadwal_pelajaran_id', $firstJadwalId)
                                    ->first();
                $statusLama = $laporanPertama->status ?? 'Belum Absen';

                // Catat log (hanya jika status berubah)
                if ($statusLama !== $status) {
                    OverrideLog::create([
                        'piket_user_id' => auth()->id(),
                        'jadwal_pelajaran_id' => $firstJadwalId, 
                        'guru_id' => $guruId, // Pastikan kolom 'guru_id' ada
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
                    
                    // Keamanan: Jangan override jika guru sudah absen mandiri
                    if ($laporan->exists && $laporan->status === 'Hadir' && $laporan->diabsen_oleh == $laporan->user_id) {
                        continue;
                    }
                    
                    // (find() tidak perlu di dalam loop, kita sudah punya $guruId)

                    // Simpan atau update record absensi
                    $laporan->fill([
                        'user_id' => $guruId, // Gunakan $guruId dari atas
                        'status' => $status,
                        'jam_absen' => $today->toTimeString(),
                        'diabsen_oleh' => auth()->id(),
                        'keterangan_piket' => $keterangan,
                    ])->save();
                }
            }

            // ==========================================================
            // ## PERBAIKAN BUG LOGBOOK ##
            // ==========================================================
            // Hanya update/create Logbook JIKA user mengisinya di form
            if ($request->filled('kejadian_penting') || $request->filled('tindak_lanjut')) 
            {
                LogbookPiket::updateOrCreate(
                    ['tanggal' => $today->toDateString()],
                    [
                        'kejadian_penting' => $request->kejadian_penting, 
                        'tindak_lanjut' => $request->tindak_lanjut
                    ]
                );
            }
            // Jika request->kejadian_penting kosong, JANGAN LAKUKAN APA-APA
            // (data logbook sebelumnya akan tetap aman).
            
            DB::commit();
            
            return redirect()->route('piket.dashboard')->with('success', 'Perubahan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors('Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }
}