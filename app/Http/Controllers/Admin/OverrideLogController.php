<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OverrideLog;
use App\Models\JadwalPelajaran;

class OverrideLogController extends Controller
{
    public function index()
    {
        $logs = OverrideLog::with(['piket', 'jadwalPelajaran.user'])
                            ->latest()
                            ->paginate(20);

        // Siapkan data blok untuk setiap log
        foreach ($logs as $log) {
            if ($log->jadwalPelajaran) {
                $jadwalPertama = $log->jadwalPelajaran;
                
                // Cari jam terakhir di blok yang sama
                $jadwalIdsInBlock = JadwalPelajaran::where('user_id', $jadwalPertama->user_id)
                    ->where('kelas', $jadwalPertama->kelas)
                    ->where('hari', $jadwalPertama->hari)
                    ->where('tipe_blok', $jadwalPertama->tipe_blok)
                    ->where('jam_ke', '>=', $jadwalPertama->jam_ke)
                    ->orderBy('jam_ke', 'asc')
                    ->pluck('jam_ke');
                
                $lastJam = $jadwalPertama->jam_ke;
                foreach($jadwalIdsInBlock as $jam) {
                    if ($jam == $lastJam + 1) {
                        $lastJam = $jam;
                    } elseif ($jam > $lastJam + 1) {
                        break;
                    }
                }
                $log->jam_terakhir = $lastJam;
            }
        }
                            
        return view('admin.laporan.override_log', compact('logs'));
    }
}