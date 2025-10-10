<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use App\Models\LogbookPiket;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\OverrideLog;
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
            'keterangan_piket' => 'nullable|array',
            'keterangan_piket.*' => 'nullable|string|max:255',
        ],[
            'status_guru.*.in' => 'Anda hanya dapat mengubah status menjadi Sakit, Izin, Alpa, atau DL.'
        ]);

        $today = now('Asia/Jakarta'); // Ubah ini agar kita bisa ambil jamnya
        $statusInput = $request->input('status_guru', []);
        $keteranganInput = $request->input('keterangan_piket', []);
        
        if (!empty($statusInput)) {
            foreach ($statusInput as $idGuru => $status) {
                
                $laporanExist = \App\Models\LaporanHarian::where('tanggal', $today->toDateString())
                                            ->where('user_id', $idGuru)
                                            ->first();

                if ($laporanExist && $laporanExist->status == 'Hadir') {
                    continue; 
                }

                $keterangan = $keteranganInput[$idGuru] ?? null;
                
                $statusLama = $laporanExist ? $laporanExist->status : 'Belum Absen';
                $statusBaru = $status ?: 'Belum Absen';
                
                if ($statusLama !== $statusBaru) {
                    \App\Models\OverrideLog::create([
                        'piket_user_id' => auth()->id(),
                        'guru_user_id' => $idGuru,
                        'tanggal' => $today->toDateString(),
                        'status_lama' => $statusLama,
                        'status_baru' => $statusBaru,
                        'keterangan' => $keterangan,
                    ]);
                }

                if (!empty($status)) {
                    \App\Models\LaporanHarian::updateOrCreate(
                        ['tanggal' => $today->toDateString(), 'user_id' => $idGuru],
                        [
                            'status' => $status, 
                            'diabsen_oleh' => auth()->id(),
                            'keterangan_piket' => $keterangan,
                            'jam_absen' => $today->toTimeString(),
                        ]
                    );
                } else {
                    if ($laporanExist) {
                        $laporanExist->delete();
                    }
                }
            }
        }
        
        \App\Models\LogbookPiket::updateOrCreate(
            ['tanggal' => $today->toDateString()],
            [
                'kejadian_penting' => $request->kejadian_penting,
                'tindak_lanjut' => $request->tindak_lanjut,
            ]
        );
        
        return redirect()->route('piket.dashboard')->with('success', 'Laporan berhasil diperbarui.');
    }
}

