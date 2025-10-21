<?php

namespace App\Http\Controllers\KepalaSekolah;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use App\Models\User;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use App\Models\KalenderBlok;
use App\Models\HariLibur;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now('Asia/Jakarta');
        $hariMap = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
        $hariIni = $hariMap[$today->format('l')];
        
        $jamKeSekarang = null;
        $snapshotStats = ['totalKelas' => 0, 'guruDiKelas' => 0, 'kelasKosong' => 0];
        $summaryHariIni = ['hadir' => 0, 'terlambat' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'dl' => 0];
        $tipeMinggu = null;

        // Cek hari libur
        if (HariLibur::where('tanggal', $today->toDateString())->exists()) {
            $tipeMinggu = 'Hari Libur';
        } else {
            // --- 1. LOGIKA SNAPSHOT SAAT INI (MIKRO) ---
            $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                                ->where('jam_mulai', '<=', $today->toTimeString())
                                ->where('jam_selesai', '>=', $today->toTimeString())
                                ->first();

            $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)->whereDate('tanggal_selesai', '>=', $today)->first();
            $blokValid = ['Setiap Minggu'];
            if ($tipeMinggu) {
                if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
                if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
            }

            if ($jamKeSekarang) {
                $jadwalSekarang = JadwalPelajaran::where('hari', $hariIni)
                                    ->where('jam_ke', $jamKeSekarang->jam_ke)
                                    ->whereIn('tipe_blok', $blokValid)
                                    ->pluck('id');
                
                $laporanUntukJamIni = LaporanHarian::whereIn('jadwal_pelajaran_id', $jadwalSekarang)
                                    ->where('tanggal', $today->toDateString())
                                    ->where('status', 'Hadir')
                                    ->count();

                $snapshotStats['totalKelas'] = $jadwalSekarang->count();
                $snapshotStats['guruDiKelas'] = $laporanUntukJamIni;
                $snapshotStats['kelasKosong'] = $snapshotStats['totalKelas'] - $snapshotStats['guruDiKelas'];
            }

            // --- 2. LOGIKA RINGKASAN HARIAN (MAKRO) ---
            // (Menggunakan logika peringkasan yang sama dengan Laporan Bulanan)
            $semuaGuru = User::where('role', 'guru')->with(['jadwalPelajaran'])->get();
            $laporanFullDay = LaporanHarian::where('tanggal', $today->toDateString())->get()->groupBy('user_id');

            foreach ($semuaGuru as $guru) {
                $jadwalHariGuru = $guru->jadwalPelajaran->where('hari', $hariIni)->filter(function ($jadwal) use ($blokValid) {
                    return in_array($jadwal->tipe_blok, $blokValid);
                });

                if ($jadwalHariGuru->isEmpty()) continue; // Guru ini tidak ada jadwal hari ini

                $laporanGuruHariIni = $laporanFullDay->get($guru->id);

                if ($laporanGuruHariIni && $laporanGuruHariIni->isNotEmpty()) {
                    if ($laporanGuruHariIni->contains('status', 'Hadir')) {
                        $summaryHariIni['hadir']++;
                        if ($laporanGuruHariIni->contains('status_keterlambatan', 'Terlambat')) {
                            $summaryHariIni['terlambat']++;
                        }
                    } elseif ($laporanGuruHariIni->contains('status', 'DL')) {
                        $summaryHariIni['dl']++;
                    } elseif ($laporanGuruHariIni->contains('status', 'Sakit')) {
                        $summaryHariIni['sakit']++;
                    } elseif ($laporanGuruHariIni->contains('status', 'Izin')) {
                        $summaryHariIni['izin']++;
                    } else {
                        $summaryHariIni['alpa']++; // Tercatat Alpa oleh Piket
                    }
                } else {
                    // Jika tidak ada data laporan sama sekali, guru ini dianggap Alpa
                    $summaryHariIni['alpa']++;
                }
            }
        }

        // --- 3. LOGIKA NOTIFIKASI PERINGATAN (PER HARI, TIDAK BERUBAH) ---
        $statusTidakHadir = ['Sakit', 'Izin', 'Alpa'];
        $batasAbsen = 4;
        $guruWarning = User::where('role', 'guru')
            ->withCount(['laporanHarian as total_tidak_hadir' => function ($query) use ($statusTidakHadir) {
                $query->whereIn('status', $statusTidakHadir)
                      ->whereMonth('tanggal', now()->month)
                      ->whereYear('tanggal', now()->year)
                      ->select(DB::raw('COUNT(DISTINCT DATE(tanggal))')); // Hitung hari unik
            }])
            ->having('total_tidak_hadir', '>=', $batasAbsen)
            ->get();

        return view('kepala_sekolah.dashboard', [
            'jamKeSekarang' => $jamKeSekarang,
            'snapshotStats' => $snapshotStats,
            'summaryHariIni' => $summaryHariIni,
            'guruWarning' => $guruWarning,
            'batasAbsen' => $batasAbsen,
        ]);
    }
}