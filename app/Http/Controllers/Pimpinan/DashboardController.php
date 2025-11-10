<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LaporanHarian;
use App\Models\User;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use App\Models\KalenderBlok;
use App\Models\HariLibur;
use App\Models\MasterHariKerja;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $now   = now('Asia/Jakarta');

        $hariMap = [
            'Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
            'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'
        ];
        $hariIni = $hariMap[$today->format('l')];

        $jamKeSekarang = null;
        $snapshotStats = ['totalKelas' => 0, 'guruDiKelas' => 0, 'kelasKosong' => 0];

        $summaryHariIni = [
            'hadir' => 0, 'terlambat' => 0, 'sakit' => 0, 'izin' => 0,
            'alpa' => 0, 'dl' => 0, 'belumAbsen' => 0, 'totalWajibHadir' => 0
        ];

        $tipeMingguString = 'Reguler';
        $guruWarning = collect();
        $batasAbsen = 4;

        // ===== ambil jam terakhir per hari (UNTUK BATAS ABSEN HARI INI) =====
         $jamTerakhirPerHari = \App\Models\MasterJamPelajaran::select('hari', \DB::raw('MAX(jam_selesai) as jam_terakhir'))
                            ->groupBy('hari')
                            ->pluck('jam_terakhir', 'hari');;

        // ===== cek libur / hari aktif =====
        if (HariLibur::whereDate('tanggal', $today)->exists()) {
            $tipeMingguString = 'Hari Libur';
        } else {
            $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

            if (!$hariKerjaAktif->contains($hariIni)) {
                $tipeMingguString = 'Hari Tidak Aktif';
            } else {
                // ===== SNAPSHOT SAAT INI =====
                $jamSekarangTime = $now->toTimeString();
                $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                    ->where('jam_mulai', '<=', $jamSekarangTime)
                    ->where('jam_selesai', '>=', $jamSekarangTime)
                    ->first();

                $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)
                    ->whereDate('tanggal_selesai', '>=', $today)
                    ->first();

                $tipeMingguString = $tipeMinggu->tipe_minggu ?? 'Reguler';
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMingguString));

                if ($jamKeSekarang) {
                    $jadwalSekarang = JadwalPelajaran::where('hari', $hariIni)
                        ->where('jam_ke', $jamKeSekarang->jam_ke)
                        ->get()
                        ->filter(function ($jadwal) use ($tipeMingguString, $nomorMinggu) {
                            $t = $jadwal->tipe_blok;
                            if ($t == 'Setiap Minggu') return true;
                            if ($tipeMingguString == 'Reguler' && $t == 'Reguler') return true;
                            if ($nomorMinggu != 'Reguler' && str_contains($t, $nomorMinggu)) return true;
                            if ($t == $tipeMingguString) return true;
                            return false;
                        })
                        ->pluck('id');

                    $laporanUntukJamIni = LaporanHarian::whereIn('jadwal_pelajaran_id', $jadwalSekarang)
                        ->whereDate('tanggal', $today)
                        ->where('status', 'Hadir')
                        ->count();

                    $snapshotStats['totalKelas']  = $jadwalSekarang->count();
                    $snapshotStats['guruDiKelas'] = $laporanUntukJamIni;
                    $snapshotStats['kelasKosong'] = $snapshotStats['totalKelas'] - $snapshotStats['guruDiKelas'];
                }

                // ===== RINGKASAN HARIAN =====
                $semuaGuru = User::where('role', 'guru')->with(['jadwalPelajaran'])->get();
                $laporanFullDay = LaporanHarian::whereDate('tanggal', $today)->get()->groupBy('user_id');

                foreach ($semuaGuru as $guru) {
                    $jadwalMentahHariIni = $guru->jadwalPelajaran->where('hari', $hariIni);
                    $jadwalHariGuru = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMingguString, $nomorMinggu) {
                        $t = $jadwal->tipe_blok;
                        if ($t == 'Setiap Minggu') return true;
                        if ($tipeMingguString == 'Reguler' && $t == 'Reguler') return true;
                        if ($nomorMinggu != 'Reguler' && str_contains($t, $nomorMinggu)) return true;
                        if ($t == $tipeMingguString) return true;
                        return false;
                    });

                    if ($jadwalHariGuru->isEmpty()) continue;

                    $summaryHariIni['totalWajibHadir']++;
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
                        } elseif ($laporanGuruHariIni->contains('status', 'Alpa')) {
                            $summaryHariIni['alpa']++;
                        } else {
                            $summaryHariIni['belumAbsen']++;
                        }
                    } else {
                        $jamTerakhirString = $jamTerakhirPerHari->get($hariIni);

                                if ($jamTerakhirString && $now->toTimeString() > $jamTerakhirString) {
                                    // Jam sekolah sudah selesai, hitung sebagai ALPA
                                    $summaryHariIni['alpa']++;
                                } else {
                                    // Jam sekolah masih berlangsung, hitung sebagai "Belum Absen"
                                    $summaryHariIni['belumAbsen']++;
                                }
                    }
                }

                // ===== PERINGATAN BULANAN (ALPA HARI INI DITETAPKAN SETELAH JAM TERAKHIR) =====
                $bulanIni = $today->month;
                $tahunIni = $today->year;
                $awalBulan = Carbon::create($tahunIni, $bulanIni, 1, 0, 0, 0, 'Asia/Jakarta')->startOfMonth();
                $akhirBulan = $awalBulan->copy()->endOfMonth();

                $hariLiburBulanan = HariLibur::whereMonth('tanggal', $bulanIni)
                    ->whereYear('tanggal', $tahunIni)
                    ->pluck('tanggal')
                    ->map(fn($d) => Carbon::parse($d)->toDateString());

                $kalenderBlokBulanIni = KalenderBlok::where(function ($q) use ($awalBulan, $akhirBulan) {
                    $q->where('tanggal_mulai', '<=', $akhirBulan)
                      ->where('tanggal_selesai', '>=', $awalBulan);
                })->get();

                $semuaGuruBulan = User::where('role', 'guru')
                    ->with([
                        'jadwalPelajaran',
                        'laporanHarian' => function ($q) use ($bulanIni, $tahunIni) {
                            $q->whereMonth('tanggal', $bulanIni)->whereYear('tanggal', $tahunIni);
                        }
                    ])->get();

                foreach ($semuaGuruBulan as $guru) {
                    $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
                    $laporanGuru = $guru->laporanHarian;

                    $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

                    for ($i = 1; $i <= $today->day; $i++) {
                        $tanggal   = Carbon::create($tahunIni, $bulanIni, $i, 0, 0, 0, 'Asia/Jakarta')->startOfDay();
                        $tanggalCek = $tanggal->toDateString();
                        $namaHari   = $tanggal->locale('id_ID')->isoFormat('dddd');

                        if (!$hariKerjaAktif->contains($namaHari)) continue;
                        if ($hariLiburBulanan->contains($tanggalCek) || !$jadwalHariGuru->has($namaHari)) continue;

                        // blok hari itu (first(callback), bukan firstWhere(callback))
                        $kalenderBlokHariIni = $kalenderBlokBulanIni->first(function ($blok) use ($tanggal) {
                            $mulai   = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                            $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                            return $tanggal->betweenIncluded($mulai, $selesai);
                        });

                        $tipeMingguTgl = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                        $nomorMingguTgl = trim(str_replace('Minggu', '', $tipeMingguTgl));

                        $jadwalMentahHariIni = $jadwalHariGuru->get($namaHari);
                        $jadwalValid = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMingguTgl, $nomorMingguTgl) {
                            $t = $jadwal->tipe_blok;
                            if ($t == 'Setiap Minggu') return true;
                            if ($tipeMingguTgl == 'Reguler' && $t == 'Reguler') return true;
                            if ($nomorMingguTgl != 'Reguler' && str_contains($t, $nomorMingguTgl)) return true;
                            if ($t == $tipeMingguTgl) return true;
                            return false;
                        });

                        if ($jadwalValid->isEmpty()) continue;

                        $laporanPerHari = $laporanGuru->where('tanggal', $tanggalCek);

                        if ($laporanPerHari->isNotEmpty()) {
                            if ($laporanPerHari->contains('status', 'Hadir')) {
                                // hadir -> tidak menambah ketidakhadiran
                            } elseif ($laporanPerHari->contains('status', 'DL')) {
                                $totalDL++;
                            } elseif ($laporanPerHari->contains('status', 'Sakit')) {
                                $totalSakit++;
                            } elseif ($laporanPerHari->contains('status', 'Izin')) {
                                $totalIzin++;
                            } elseif ($laporanPerHari->contains('status', 'Alpa')) {
                                $totalAlpa++;
                            }
                        } else {
                            // ======= BATAS ABSEN: JAM TERAKHIR HARI ITU =======
                            if ($tanggal->isBefore($today)) {
                                $totalAlpa++;
                            } elseif ($tanggal->equalTo($today)) {
                                // Ambil jam terakhir untuk $namaHari dari tabel MasterJamPelajaran
                                $jamTerakhirString = $jamTerakhirPerHari->get($namaHari);

                                if ($jamTerakhirString) {
                                    // jika waktu sekarang > jam terakhir -> alpa
                                    if ($now->toTimeString() > $jamTerakhirString) {
                                        $totalAlpa++;
                                    }
                                } else {
                                    // fallback (jika master jam kosong), mis. 14:00
                                     if ($now->toTimeString() > $jamTerakhirString) {
                                    $totalAlpa++;
                                 }
                                }
                            }
                        }
                    }

                    $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa + $totalDL; 
                    if ($totalTidakHadir >= $batasAbsen) {
                        $guru->total_tidak_hadir = $totalTidakHadir;
                        $guruWarning->push($guru);
                    }
                }
            }
        }

        return view('pimpinan.dashboard', [
            'jamKeSekarang' => $jamKeSekarang,
            'snapshotStats' => $snapshotStats,
            'summaryHariIni' => $summaryHariIni,
            'guruWarning' => $guruWarning,
            'batasAbsen' => $batasAbsen,
            'tipeMinggu' => $tipeMingguString,
        ]);
    }
}
