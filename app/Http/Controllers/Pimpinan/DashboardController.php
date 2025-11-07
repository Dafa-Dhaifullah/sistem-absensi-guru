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
use App\Models\MasterHariKerja; // <-- 1. TAMBAHKAN IMPORT
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
        $summaryHariIni = ['hadir' => 0, 'terlambat' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'dl' => 0, 'totalWajibHadir' => 0];
        $tipeMingguString = 'Reguler'; // Default
        $guruWarning = collect();
        $batasAbsen = 4;

        // ==========================================================
        // ## MODIFIKASI 1: PENGECEKAN HARI AKTIF & HARI LIBUR ##
        // ==========================================================

        // Cek hari libur nasional
        if (HariLibur::where('tanggal', $today->toDateString())->exists()) {
            $tipeMingguString = 'Hari Libur';
        } else {
            // Cek hari kerja aktif
            $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
            if (!$hariKerjaAktif->contains($hariIni)) {
                $tipeMingguString = 'Hari Tidak Aktif';
            } else {
                // ==========================================================
                // ## HARI INI ADALAH HARI KERJA AKTIF ##
                // ==========================================================

                // --- 1. LOGIKA SNAPSHOT SAAT INI (MIKRO) ---
                $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                                    ->where('jam_mulai', '<=', $today->toTimeString())
                                    ->where('jam_selesai', '>=', $today->toTimeString())
                                    ->first();

                // ==========================================================
                // ## MODIFIKASI 2: LOGIKA TIPE BLOK KONSISTEN (str_contains) ##
                // ==========================================================
                $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)->whereDate('tanggal_selesai', '>=', $today)->first();
                $tipeMingguString = $tipeMinggu->tipe_minggu ?? 'Reguler';
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMingguString));
                
                if ($jamKeSekarang) {
                    $jadwalSekarang = JadwalPelajaran::where('hari', $hariIni)
                                    ->where('jam_ke', $jamKeSekarang->jam_ke)
                                    ->get() // Ambil dulu
                                    ->filter(function ($jadwal) use ($tipeMingguString, $nomorMinggu) { // Filter
                                        $tipeBlokJadwal = $jadwal->tipe_blok;
                                        if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                                        if ($tipeMingguString == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                                        if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                                        if ($tipeBlokJadwal == $tipeMingguString) return true;
                                        return false;
                                    })
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
                    // Gunakan logika filter Tipe Blok yang KONSISTEN
                    $jadwalMentahHariIni = $guru->jadwalPelajaran->where('hari', $hariIni);
                    $jadwalHariGuru = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMingguString, $nomorMinggu) {
                        $tipeBlokJadwal = $jadwal->tipe_blok;
                        if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                        if ($tipeMingguString == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                        if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                        if ($tipeBlokJadwal == $tipeMingguString) return true;
                        return false;
                    });

                    if ($jadwalHariGuru->isEmpty()) continue; // Guru ini tidak ada jadwal hari ini

                    $summaryHariIni['totalWajibHadir']++; // Tambahkan total guru yang wajib hadir
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
                        $summaryHariIni['alpa']++;
                    }
                }

                // --- 3. LOGIKA NOTIFIKASI PERINGATAN (BULANAN) ---
                // ==========================================================
                // ## MODIFIKASI 3: MENYALIN LOGIKA DARI DASBOR ADMIN ##
                // ==========================================================
                $bulanIni = $today->month;
                $tahunIni = $today->year;
                $awalBulan = Carbon::create($tahunIni, $bulanIni, 1)->startOfMonth();
                $akhirBulan = $awalBulan->clone()->endOfMonth();

                // (Variabel $semuaGuru sudah diambil di atas, $hariKerjaAktif juga sudah ada)
                
                $hariLiburBulanan = HariLibur::whereMonth('tanggal', $bulanIni)
                    ->whereYear('tanggal', $tahunIni)
                    ->pluck('tanggal')
                    ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());
            
                $kalenderBlokBulanIni = KalenderBlok::where(function ($query) use ($awalBulan, $akhirBulan) {
                    $query->where('tanggal_mulai', '<=', $akhirBulan)
                          ->where('tanggal_selesai', '>=', $awalBulan);
                })->get();

                // (Loop $semuaGuru sudah ada di atas, kita gunakan $semuaGuru dari $summaryHariIni)
                foreach ($semuaGuru as $guru) {
                    $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
                    $laporanGuru = $guru->laporanHarian; // laporan sebulan penuh
                    $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0;

                    for ($i = 1; $i <= $today->day; $i++) {
                        $tanggal = Carbon::create($tahunIni, $bulanIni, $i)->startOfDay();
                        $tanggalCek = $tanggal->toDateString();
                        $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                        if (!$hariKerjaAktif->contains($namaHari)) continue; // Cek hari aktif
                        if ($hariLiburBulanan->contains($tanggalCek) || !$jadwalHariGuru->has($namaHari)) continue;
                        
                        // Logika Tipe Blok (str_contains)
                        $kalenderBlokHariIni = $kalenderBlokBulanIni->firstWhere(function ($blok) use ($tanggal) {
                            $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                            $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                            return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                        });
                        $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                        $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu));
                        
                        $jadwalMentahHariIni = $jadwalHariGuru->get($namaHari);
                        $jadwalValid = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                            $tipeBlokJadwal = $jadwal->tipe_blok;
                            if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                            if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                            if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                            if ($tipeBlokJadwal == $tipeMinggu) return true;
                            return false;
                        });

                        if ($jadwalValid->isNotEmpty()) {
                            $laporanPerHari = $laporanGuru->where('tanggal', $tanggalCek);
                            if ($laporanPerHari->isNotEmpty()) {
                                if ($laporanPerHari->contains('status', 'Sakit')) $totalSakit++;
                                elseif ($laporanPerHari->contains('status', 'Izin')) $totalIzin++;
                            } else {
                               $totalAlpa++;
                            }
                        }
                    }
                    
                    $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa;
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
            'tipeMinggu' => $tipeMingguString, // Kirim tipe minggu yang sudah diproses
        ]);
    }
}