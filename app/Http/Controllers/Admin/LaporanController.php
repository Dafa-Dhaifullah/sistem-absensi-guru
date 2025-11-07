<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LaporanHarian;
use App\Models\LogbookPiket;
use Illuminate\Support\Carbon;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use App\Exports\ArsipLogbookExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LaporanIndividuExport;
use App\Exports\LaporanBulananExport;
use App\Exports\LaporanMingguanExport;
use App\Models\HariLibur;
use App\Models\MasterHariKerja;
use App\Exports\LaporanSesiExport;
use Illuminate\Support\Facades\Cache;

class LaporanController extends Controller
{

  public function bulanan(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $daysInMonth = Carbon::createFromDate($tahun, $bulan)->daysInMonth;
        $today = now('Asia/Jakarta')->startOfDay(); 
        $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $akhirBulan = $awalBulan->clone()->endOfMonth();

        // Kueri ini tetap efisien (sudah benar)
        $semuaGuru = \App\Models\User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) use ($bulan, $tahun) {
                $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        $hariLibur = \App\Models\HariLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('tanggal')
           ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());


        // 2. Ambil data KalenderBlok satu kali
        $kalenderBlokBulanIni = KalenderBlok::where(function ($query) use ($awalBulan, $akhirBulan) {
            $query->where('tanggal_mulai', '<=', $akhirBulan)
                  ->where('tanggal_selesai', '>=', $awalBulan);
        })->get();

       $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        $hariKerjaEfektif = [];
        foreach($semuaGuru as $guru) {
            $jadwalPerHari = $guru->jadwalPelajaran->groupBy('hari');
            $hariKerjaList = collect(); 

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = Carbon::create($tahun, $bulan, $i)->startOfDay();
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                // ==========================================================
                // 2. Tambahkan pengecekan apakah hari ini (Senin, Sabtu, dll) aktif
                // ==========================================================
                if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }
                // ==========================================================

                // 4. Cek Libur Nasional atau tidak ada jadwal sama sekali hari itu
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalPerHari->has($namaHari)) {
                    continue;
                }
                
                // 5. Cari Tipe Minggu (Minggu 1 / Minggu 2)
                $kalenderBlokHariIni = $kalenderBlokBulanIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu));

                // 6. Filter jadwal berdasarkan Tipe Minggu
                $jadwalMentahHariIni = $jadwalPerHari->get($namaHari);
                
                $jadwalValid = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                });

                // 7. Jika ada jadwal valid, baru hitung sebagai hari kerja
                if ($jadwalValid->isNotEmpty()) {
                    $hariKerjaList->push($tanggal->toDateString());
                }
            }
            $hariKerjaEfektif[$guru->id] = $hariKerjaList;
        }
        // ==========================================================
        // == AKHIR PERBAIKAN ==
        // ==========================================================

        $laporanHarianTeringkas = collect();
        $summaryTotal = [];

        foreach ($semuaGuru as $index => $guru) {
            $laporanGuru = $guru->laporanHarian; 
            $dataHarian = [];
            $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = Carbon::create($tahun, $bulan, $i)->startOfDay();
                $tanggalCek = $tanggal->toDateString();
                
                $statusFinal = '-'; 
                // Logika $isHariKerja sekarang akurat berdasarkan 'tipe_blok'
                $isHariKerja = isset($hariKerjaEfektif[$guru->id]) && $hariKerjaEfektif[$guru->id]->contains($tanggalCek);
                
                if ($isHariKerja) {
                    $laporanPerHari = $laporanGuru->filter(function ($laporan) use ($tanggalCek) {
                        return $laporan->tanggal->toDateString() === $tanggalCek;
                    });
                    
                    if ($laporanPerHari->isNotEmpty()) {
                        // Penjelasan Anda: "jika ada satu jadwal yg statusnya hadir, maka guru itu hadir"
                        // Kode 'contains' ini sudah melakukan itu.
                        if ($laporanPerHari->contains('status', 'Hadir')) {
                            $statusFinal = 'H'; $totalHadir++;
                        } elseif ($laporanPerHari->contains('status', 'DL')) {
                            $statusFinal = 'DL'; $totalDL++;
                        } elseif ($laporanPerHari->contains('status', 'Sakit')) {
                            $statusFinal = 'S'; $totalSakit++;
                        } elseif ($laporanPerHari->contains('status', 'Izin')) {
                            $statusFinal = 'I'; $totalIzin++;
                        } else {
                            $statusFinal = 'A'; $totalAlpa++; // Jika ada laporan tapi bukan H, S, I, DL (cth: Alpa)
                        }
                    } else {
                        if ($tanggal->isBefore($today)) {
                            $statusFinal = 'A'; 
                            $totalAlpa++;
                        } else {
                            $statusFinal = '-'; 
                        }
                    }
                }
                $dataHarian[$i] = $statusFinal;
            }
            
            $laporanHarianTeringkas->push(['name' => $guru->name, 'dataHarian' => $dataHarian]);
            $summaryTotal[$guru->id] = compact('totalHadir', 'totalSakit', 'totalIzin', 'totalAlpa', 'totalDL');
        }

        return view('admin.laporan.bulanan', [
            'laporanHarianTeringkas' => $laporanHarianTeringkas,
            'summaryTotal' => $summaryTotal,
            'semuaGuru' => $semuaGuru, 
            'bulan' => $bulan,
            'tahun' => $tahun,
            'daysInMonth' => $daysInMonth,
            'hariKerjaEfektif' => $hariKerjaEfektif
        ]);
    }
   public function bulananSesi(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $today = now('Asia/Jakarta')->startOfDay(); 

        $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $akhirBulan = $awalBulan->clone()->endOfMonth();

        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($awalBulan, $akhirBulan) {
                $query->whereBetween('tanggal', [$awalBulan, $akhirBulan]);
            }])
            ->orderBy('name', 'asc')->get();
        
        $hariLibur = HariLibur::whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->pluck('tanggal')->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());

        $kalenderBlokBulanIni = KalenderBlok::where(function ($query) use ($awalBulan, $akhirBulan) {
            $query->where('tanggal_mulai', '<=', $akhirBulan)
                  ->where('tanggal_selesai', '>=', $awalBulan);
        })->get();

        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        $laporanPerSesi = collect();

        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0; $totalHadir = 0; $totalTepatWaktu = 0; $totalTerlambat = 0;
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
            $daysInMonth = $awalBulan->daysInMonth;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = $awalBulan->clone()->addDays($i - 1);
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                
                // ==========================================================
                // 2. Tambahkan pengecekan hari aktif
                // ==========================================================
                if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }
                
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) continue;

                // --- Logika Pencarian Tipe Blok ---
                $kalenderBlokHariIni = $kalenderBlokBulanIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler'; 
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu)); 

                // --- Logika Filter Jadwal (str_contains) ---
                $jadwalMentahHariIni = $jadwalHariGuru->get($namaHari);
                $jadwalUntukHariIni = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                })->sortBy('jam_ke');

                // --- Logika Pengelompokan Blok ---
                $tempBlock = null;
                $jadwalBlok = collect();
                foreach ($jadwalUntukHariIni as $jadwal) {
                    if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                        $tempBlock['jadwal_ids'][] = $jadwal->id; $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                    } else {
                        if ($tempBlock) $jadwalBlok->push($tempBlock);
                        $tempBlock = ['jadwal_ids' => [$jadwal->id], 'jam_pertama' => $jadwal->jam_ke, 'jam_terakhir' => $jadwal->jam_ke, 'kelas' => $jadwal->kelas];
                    }
                }
                if ($tempBlock) $jadwalBlok->push($tempBlock);

                
                // 1. Total Sesi Wajib dihitung untuk SEMUA HARI (termasuk masa depan)
                $totalSesiWajib += $jadwalBlok->count();

                // 2. JANGAN LANJUTKAN jika tanggal ini ada di masa depan
                if ($tanggal->gt($today)) {
                    continue; 
                }

                // 3. Status (Hadir/Alpa/dll) HANYA dihitung untuk hari ini ke belakang
                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];
                    $laporan = $guru->laporanHarian
                        ->where('jadwal_pelajaran_id', $jadwalPertamaId)
                        ->where('tanggal', $tanggal) 
                        ->first();

                    if ($laporan) {
                        switch ($laporan->status) {
                            case 'Hadir':
                                $totalHadir++;
                                if ($laporan->status_keterlambatan == 'Tepat Waktu') $totalTepatWaktu++;
                                if ($laporan->status_keterlambatan == 'Terlambat') $totalTerlambat++;
                                break;
                            case 'Sakit': $totalSakit++; break;
                            case 'Izin': $totalIzin++; break;
                            case 'DL': $totalDL++; break;
                            default: $totalAlpa++; break;
                        }
                    } else {
                        // Hanya hitung Alpa jika tanggalnya sudah lewat (sama dengan $today atau sebelumnya)
                        // (Cek $tanggal->gt($today) di atas sudah menangani ini)
                        $totalAlpa++;
                    }
                }
                // ==========================================================
            } 
            
            $persentaseHadir = ($totalSesiWajib > 0) ? ($totalHadir / $totalSesiWajib) * 100 : 0;
            $persentaseTepatWaktu = ($totalHadir > 0) ? ($totalTepatWaktu / $totalHadir) * 100 : 0;

            $laporanPerSesi->push([
                'name' => $guru->name, 'totalSesiWajib' => $totalSesiWajib,
                'totalHadir' => $totalHadir, 'totalTerlambat' => $totalTerlambat,
                'totalSakit' => $totalSakit, 'totalIzin' => $totalIzin,
                'totalAlpa' => $totalAlpa, 'totalDL' => $totalDL,
                'persentaseHadir' => round($persentaseHadir, 2),
                'persentaseTepatWaktu' => round($persentaseTepatWaktu, 2),
            ]);
        } 

        return view('admin.laporan.bulanan_sesi', [
            'laporanPerSesi' => $laporanPerSesi, 
            'bulan' => $bulan, 
            'tahun' => $tahun,
        ]);
    }

    public function mingguan(Request $request)
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $tanggalSelesai = $request->input('tanggal_selesai', $today->toDateString());
        $tanggalMulai = $request->input('tanggal_mulai', $today->copy()->subDays(6)->toDateString());

        $tanggalRange = Carbon::parse($tanggalMulai)->locale('id_ID')->toPeriod(Carbon::parse($tanggalSelesai));
        
        $semuaGuru = \App\Models\User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        $hariLibur = \App\Models\HariLibur::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->pluck('tanggal')
            ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());

       $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        // 2. Ambil data KalenderBlok satu kali
        $kalenderBlokMingguIni = KalenderBlok::where(function ($query) use ($tanggalMulai, $tanggalSelesai) {
            $query->where('tanggal_mulai', '<=', $tanggalSelesai)
                  ->where('tanggal_selesai', '>=', $tanggalMulai);
        })->get();

        $hariKerjaEfektif = [];
        foreach($semuaGuru as $guru) {
            // 3. Ambil jadwal lengkap (di-grup per hari)
            $jadwalPerHari = $guru->jadwalPelajaran->groupBy('hari');
            $hariKerjaList = collect(); 

            // 4. Looping setiap hari di rentang
            foreach ($tanggalRange as $tanggal) {
                $tanggal = $tanggal->startOfDay();
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                // 5. Pengecekan baru (Hari Aktif)
                if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }

                // 6. Pengecekan lama (Libur Nasional / Tiada Jadwal)
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalPerHari->has($namaHari)) {
                    continue;
                }
                
                // 7. Cari Tipe Minggu (Minggu 1 / Minggu 2)
                $kalenderBlokHariIni = $kalenderBlokMingguIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu));

                // 8. Filter jadwal berdasarkan Tipe Minggu
                $jadwalMentahHariIni = $jadwalPerHari->get($namaHari);
                
                $jadwalValid = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                });

                // 9. Jika ada jadwal valid, baru hitung sebagai hari kerja
                if ($jadwalValid->isNotEmpty()) {
                    $hariKerjaList->push($tanggal->toDateString());
                }
            }
            $hariKerjaEfektif[$guru->id] = $hariKerjaList;
        }

        $laporanHarianTeringkas = collect();
        $summaryTotal = [];

        foreach ($semuaGuru as $index => $guru) {
            $laporanGuru = $guru->laporanHarian;
            $dataHarian = [];
            
            $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            foreach ($tanggalRange as $tanggal) {
                $tanggalCek = $tanggal->toDateString();
                $statusFinal = '-'; 

                $isHariKerja = isset($hariKerjaEfektif[$guru->id]) && $hariKerjaEfektif[$guru->id]->contains($tanggalCek);

                if ($isHariKerja) {
                    $laporanPerHari = $laporanGuru->filter(function ($laporan) use ($tanggalCek) {
                        return $laporan->tanggal->toDateString() === $tanggalCek;
                    });
                    
                    if ($laporanPerHari->isNotEmpty()) {
                        if ($laporanPerHari->contains('status', 'Hadir')) {
                            $statusFinal = 'H'; $totalHadir++;
                        } elseif ($laporanPerHari->contains('status', 'DL')) {
                            $statusFinal = 'DL'; $totalDL++;
                        } elseif ($laporanPerHari->contains('status', 'Sakit')) {
                            $statusFinal = 'S'; $totalSakit++;
                        } elseif ($laporanPerHari->contains('status', 'Izin')) {
                            $statusFinal = 'I'; $totalIzin++;
                        } else {
                            $statusFinal = 'A'; $totalAlpa++;
                        }
                    } else {
                        if ($tanggal->isBefore($today)) {
                            $statusFinal = 'A'; 
                            $totalAlpa++;
                        } else {
                            $statusFinal = '-';
                        }
                    }
                }
                $dataHarian[$tanggalCek] = $statusFinal;
            }
            
            $laporanHarianTeringkas->push(['name' => $guru->name, 'dataHarian' => $dataHarian]);
            $summaryTotal[$guru->id] = compact('totalHadir', 'totalSakit', 'totalIzin', 'totalAlpa', 'totalDL');
        }

        return view('admin.laporan.mingguan', [
            'laporanHarianTeringkas' => $laporanHarianTeringkas,
            'summaryTotal' => $summaryTotal,
            'semuaGuru' => $semuaGuru,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'tanggalRange' => $tanggalRange,
            'hariKerjaEfektif' => $hariKerjaEfektif // Kirim data ini
        ]);
    }

   public function mingguanSesi(Request $request)
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $tanggalSelesaiInput = $request->input('tanggal_selesai', $today->toDateString());
        $tanggalMulaiInput = $request->input('tanggal_mulai', $today->copy()->subDays(6)->toDateString());

        // --- 1. PENGATURAN TANGGAL & DATA AWAL ---
        $awalMinggu = Carbon::parse($tanggalMulaiInput)->startOfDay();
        $akhirMinggu = Carbon::parse($tanggalSelesaiInput)->startOfDay();
        $tanggalRange = $awalMinggu->locale('id_ID')->toPeriod($akhirMinggu);

        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($awalMinggu, $akhirMinggu) {
                $query->whereBetween('tanggal', [$awalMinggu, $akhirMinggu]);
            }])
            ->orderBy('name', 'asc')
            ->get();
        
        $hariLibur = HariLibur::whereBetween('tanggal', [$awalMinggu, $akhirMinggu])
            ->pluck('tanggal')
            ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());

        // --- OPTIMASI (N+1): Ambil data KalenderBlok 1x ---
        $kalenderBlokMingguIni = KalenderBlok::where(function ($query) use ($awalMinggu, $akhirMinggu) {
            $query->where('tanggal_mulai', '<=', $akhirMinggu)
                  ->where('tanggal_selesai', '>=', $awalMinggu);
        })->get();
        // --- Akhir Optimasi ---

        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        $laporanPerSesi = collect();

        foreach ($semuaGuru as $guru) {
            // ... (Inisialisasi total) ...
            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');

            foreach ($tanggalRange as $tanggal) {
                $tanggal = $tanggal->startOfDay(); 
                
                if ($tanggal->gt($today)) break; 
                
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) continue;

                // --- OPTIMASI: Cari tipe minggu dari koleksi ---
                $kalenderBlokHariIni = $kalenderBlokMingguIni->firstWhere(function ($blok) use ($tanggal) {
                    // Gunakan perbandingan Carbon yang kuat
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                // --- Akhir Optimasi ---
                
                
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu)); 
                $jadwalMentahHariIni = $jadwalHariGuru->get($namaHari);

                $jadwalUntukHariIni = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                })->sortBy('jam_ke');
                // ==========================================================
                
                // --- Logika Pengelompokan Blok ---
                $tempBlock = null;
                $jadwalBlok = collect();
                foreach ($jadwalUntukHariIni as $jadwal) {
                    if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                        $tempBlock['jadwal_ids'][] = $jadwal->id; $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                    } else {
                        if ($tempBlock) $jadwalBlok->push($tempBlock);
                        $tempBlock = ['jadwal_ids' => [$jadwal->id], 'jam_pertama' => $jadwal->jam_ke, 'jam_terakhir' => $jadwal->jam_ke, 'kelas' => $jadwal->kelas];
                    }
                }
                if ($tempBlock) $jadwalBlok->push($tempBlock);
                // --- Akhir Logika Blok ---

                $totalSesiWajib += $jadwalBlok->count();

                // --- 4. LOOPING PER BLOK (CEK KEHADIRAN) ---
                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];

                  
                    $laporan = $guru->laporanHarian
                        ->where('jadwal_pelajaran_id', $jadwalPertamaId)
                        ->where('tanggal', $tanggal) // Filter berdasarkan hari
                        ->first();

                    if ($laporan) {
                        switch ($laporan->status) {
                            case 'Hadir':
                                $totalHadir++;
                                if ($laporan->status_keterlambatan == 'Tepat Waktu') $totalTepatWaktu++;
                                if ($laporan->status_keterlambatan == 'Terlambat') $totalTerlambat++;
                                break;
                            case 'Sakit': $totalSakit++; break;
                            case 'Izin': $totalIzin++; break;
                            case 'DL': $totalDL++; break;
                            default: $totalAlpa++; break;
                        }
                    } else {
                        $totalAlpa++;
                    }
                }
            } // End looping per hari
            
            // --- 5. KALKULASI PERSENTASE ---
            $persentaseHadir = ($totalSesiWajib > 0) ? ($totalHadir / $totalSesiWajib) * 100 : 0;
            $persentaseTepatWaktu = ($totalHadir > 0) ? ($totalTepatWaktu / $totalHadir) * 100 : 0;

            $laporanPerSesi->push([
                'name' => $guru->name, 'totalSesiWajib' => $totalSesiWajib,
                'totalHadir' => $totalHadir, 'totalTerlambat' => $totalTerlambat,
                'totalSakit' => $totalSakit, 'totalIzin' => $totalIzin,
                'totalAlpa' => $totalAlpa, 'totalDL' => $totalDL,
                'persentaseHadir' => round($persentaseHadir, 2),
                'persentaseTepatWaktu' => round($persentaseTepatWaktu, 2),
            ]);
        } // End looping per guru

        return view('admin.laporan.mingguan_sesi', [
            'laporanPerSesi' => $laporanPerSesi,
            'tanggalMulai' => $tanggalMulaiInput, // Kirim tanggal input asli
            'tanggalSelesai' => $tanggalSelesaiInput, // Kirim tanggal input asli
        ]);
    }
    

    // Method baru untuk export laporan sesi mingguan
    public function exportMingguanSesi(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;

        $namaFile = "laporan_sesi_mingguan_{$tanggalMulai}_sd_{$tanggalSelesai}.xlsx";

        return Excel::download(new \App\Exports\LaporanSesiMingguanExport($tanggalMulai, $tanggalSelesai), $namaFile);
    }

   public function individu(Request $request)
    {
        $semuaGuru = \App\Models\User::where('role', 'guru')->orderBy('name', 'asc')->get();
        $laporanFinal = null;
        $summary = null;
        $guruTerpilih = null;

        if ($request->filled('user_id') && $request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            ]);

            $guruTerpilih = \App\Models\User::findOrFail($request->user_id);
            $tanggalMulai = \Illuminate\Support\Carbon::parse($request->tanggal_mulai);
            $tanggalSelesai = \Illuminate\Support\Carbon::parse($request->tanggal_selesai);
            $today = now('Asia/Jakarta')->startOfDay();

            // 1. Ambil data yang relevan
            $laporanTersimpan = \App\Models\LaporanHarian::where('user_id', $guruTerpilih->id)
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                ->with('piket')
                ->get()
                ->keyBy(function ($item) {
                    return $item->tanggal->toDateString() . '_' . $item->jadwal_pelajaran_id;
                });

            $jadwalGuru = $guruTerpilih->jadwalPelajaran->groupBy('hari');
            $hariLibur = \App\Models\HariLibur::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                ->pluck('tanggal')->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());
            
            // ==========================================================
            // ## PERBAIKAN N+1 QUERY ##
            // ==========================================================
            $kalenderBlokPeriodeIni = \App\Models\KalenderBlok::where(function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->where('tanggal_mulai', '<=', $tanggalSelesai)
                      ->where('tanggal_selesai', '>=', $tanggalMulai);
            })->get();
            // ==========================================================
            
            $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
            
            $laporanFinal = collect(); 

            foreach (\Illuminate\Support\Carbon::parse($tanggalMulai)->toPeriod($tanggalSelesai) as $tanggal) {
                
                if ($tanggal->isFuture()) break; 

                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                
                if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }
                
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalGuru->has($namaHari)) {
                    continue;
                }

               
                $kalenderBlokHariIni = $kalenderBlokPeriodeIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = \Illuminate\Support\Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = \Illuminate\Support\Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                // ==========================================================
                
                
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu)); 
                $jadwalMentahHariIni = $jadwalGuru->get($namaHari);

                $jadwalHariIni = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                })->sortBy('jam_ke');
                // ==========================================================


                // 3. LOGIKA PENGELOMPOKKAN BLOK (SESI MENGAJAR) - (Ini sudah benar)
                $tempBlock = null;
                $jadwalBlok = collect();
                foreach ($jadwalHariIni as $jadwal) {
                    if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                        $tempBlock['jadwal_ids'][] = $jadwal->id;
                        $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                    } else {
                        if ($tempBlock) $jadwalBlok->push($tempBlock);
                        $tempBlock = [
                            'jadwal_ids' => [$jadwal->id], 'jam_pertama' => $jadwal->jam_ke,
                            'jam_terakhir' => $jadwal->jam_ke, 'kelas' => $jadwal->kelas
                        ];
                    }
                }
                if ($tempBlock) $jadwalBlok->push($tempBlock);
                // --- AKHIR LOGIKA BLOK ---

                // 4. Loop setiap BLOK sesi wajib pada hari itu
                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];
                    
                    $key = $tanggal->toDateString() . '_' . $jadwalPertamaId;
                    $laporan = $laporanTersimpan->get($key);

                    // Buat object "log" baru yang mewakili BLOK ini
                    $logSesi = new \stdClass();
                    $logSesi->tanggal = $tanggal->toDateString();
                    $logSesi->jam_pertama = $blok['jam_pertama'];
                    $logSesi->jam_terakhir = $blok['jam_terakhir'];
                    $logSesi->kelas = $blok['kelas'];

                    if ($laporan) {
                        $logSesi->status = $laporan->status;
                        $logSesi->status_keterlambatan = $laporan->status_keterlambatan;
                        $logSesi->jam_absen = $laporan->jam_absen;
                        $logSesi->foto_selfie_path = $laporan->foto_selfie_path;
                        $logSesi->keterangan_piket = $laporan->keterangan_piket;
                        $logSesi->diabsen_oleh = $laporan->diabsen_oleh;
                        $logSesi->piket = $laporan->piket;
                    } else {
                        $logSesi->status = 'Alpa';
                        $logSesi->status_keterlambatan = null;
                        $logSesi->jam_absen = null;
                        $logSesi->foto_selfie_path = null;
                        $logSesi->keterangan_piket = null;
                        $logSesi->diabsen_oleh = null;
                        $logSesi->piket = null;
                    }
                    $laporanFinal->push($logSesi);
                }
            }

            // 5. Hitung summary (Logika ini sudah benar)
            $summary = [
                'Hadir' => $laporanFinal->where('status', 'Hadir')->count(),
                'Terlambat' => $laporanFinal->where('status', 'Hadir')->where('status_keterlambatan', 'Terlambat')->count(),
                'Sakit' => $laporanFinal->where('status', 'Sakit')->count(),
                'Izin' => $laporanFinal->where('status', 'Izin')->count(),
                'Alpa' => $laporanFinal->where('status', 'Alpa')->count(),
                'DL' => $laporanFinal->where('status', 'DL')->count(),
                'Total' => $laporanFinal->count()
            ];
        }
        
        return view('admin.laporan.individu', [
            'semuaGuru' => $semuaGuru,
            'laporan' => $laporanFinal,
            'summary' => $summary,
            'guruTerpilih' => $guruTerpilih,
        ]);
    }
    /**
    * Menampilkan jadwal pelajaran yang sedang berlangsung.
    */
     public function realtime(Request $request)
    {
        $today = now('Asia/Jakarta');
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $hariIni = $hariMap[$today->format('l')];
        $jamSekarang = $today->toTimeString();

        // 1. Cek hari libur
        if (HariLibur::where('tanggal', $today->toDateString())->exists()) {
            $jamKeSekarang = null;
            $jadwalSekarang = collect();
            $laporanHariIni = collect();
            $tipeMingguString = 'Hari Libur'; // REVISI: Variabel string yang aman
        } else {
            $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

            if (!$hariKerjaAktif->contains($hariIni)) {
                // Jika hari ini TIDAK AKTIF (misal: Sabtu libur)
                $jamKeSekarang = null;
                $jadwalSekarang = collect();
                $laporanHariIni = collect();
                $tipeMingguString = 'Hari Tidak Aktif';
            } else {
            
            // 2. Cari Jam Ke- berapa sekarang
                $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                                    ->where('jam_mulai', '<=', $jamSekarang)
                                    ->where('jam_selesai', '>=', $jamSekarang)
                                    ->first();
                
                $tipeMinggu = Cache::remember('kalender_blok_today', 60, function () use ($today) {
                    $nowStr = $today->format('Y-m-d H:i:s');
                    return KalenderBlok::where('tanggal_mulai', '<=', $nowStr)
                                         ->where('tanggal_selesai', '>=', $nowStr)
                                         ->first();
                });
            
            // ==========================================================
            // REVISI: Logika aman untuk string tipeMinggu
            // ==========================================================
                $tipeMingguString = $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler';
                
                $jadwalSekarang = collect();
            
            // 3. Jika sedang jam pelajaran, cari semua jadwal
            if ($jamKeSekarang) {
                
                // ==========================================================
                // ## REVISI KRITIS: LOGIKA FILTER BLOK ##
                // ==========================================================
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMingguString)); // Cth: "1" atau "2"

                $jadwalSekarang = JadwalPelajaran::where('hari', $hariIni)
                    ->where('jam_ke', $jamKeSekarang->jam_ke)
                    ->where(function ($query) use ($tipeMingguString, $nomorMinggu) {
                        
                        // 1. Selalu sertakan 'Setiap Minggu'
                        $query->where('tipe_blok', 'Setiap Minggu');
                        
                        // 2. Sertakan jika nama blok sama persis (cth: "Reguler" == "Reguler")
                        $query->orWhere('tipe_blok', $tipeMingguString);
                        
                        // 3. Sertakan jika $nomorMinggu (cth: "1") ada di dalam string 
                        //    (untuk mencocokkan "Hanya Minggu 1,2")
                        if (is_numeric($nomorMinggu)) {
                            $query->orWhere('tipe_blok', 'like', '%' . $nomorMinggu . '%'); 
                        }
                        
                        // 4. (Pengaman) Jika tipe minggu reguler, pastikan jadwal reguler terambil
                        if ($tipeMingguString === 'Reguler') {
                             $query->orWhere('tipe_blok', 'Reguler');
                        }
                    })
                    ->with('user')
                    ->orderBy('kelas', 'asc')
                    ->get();
            }

            // 4. Ambil semua laporan hari ini untuk dicek statusnya
            $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                                ->get()
                                ->keyBy('jadwal_pelajaran_id'); // Gunakan jadwal_id sebagai kunci
        }
    }

        return view('admin.laporan.realtime', [
            'jadwalSekarang' => $jadwalSekarang,
            'hariIni' => $hariIni,
            'jamKeSekarang' => $jamKeSekarang,
            'tipeMinggu' => $tipeMingguString, // REVISI: Kirim string yang aman
            'laporanHariIni' => $laporanHariIni
        ]);
    }

    // ... sisa method (arsip, export) tidak berubah ...
    public function arsip(Request $request)
    {
        $logbook = LogbookPiket::latest()->paginate(15);
        return view('admin.laporan.arsip', ['logbook' => $logbook]);
    }

    public function exportBulanan(Request $request)
    {
        $request->validate(['bulan' => 'required|integer|between:1,12', 'tahun' => 'required|integer|min:2000']);
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $namaBulan = Carbon::create()->month($bulan)->isoFormat('MMMM');
        $namaFile = "laporan_bulanan_{$namaBulan}_{$tahun}.xlsx";
        return Excel::download(new LaporanBulananExport($bulan, $tahun), $namaFile);
    }
    public function exportBulananSesi(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $namaBulan = \Carbon\Carbon::create()->month($bulan)->locale('id_ID')->isoFormat('MMMM');
        $namaFile = "laporan_sesi_{$namaBulan}_{$tahun}.xlsx";

        return Excel::download(new LaporanSesiExport($bulan, $tahun), $namaFile);
    }

    public function exportMingguan(Request $request)
    {
        // 1. Validasi filter
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;
        
        // 2. Ambil data yang sudah diproses (Logika ini SAMA dengan method 'mingguan()')
        $today = now('Asia/Jakarta')->startOfDay();
        $tanggalRange = Carbon::parse($tanggalMulai)->locale('id_ID')->toPeriod(Carbon::parse($tanggalSelesai));
        
        $semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        $hariLibur = HariLibur::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->pluck('tanggal')
            ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());
 // 1. Ambil daftar hari kerja yang aktif
        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        // 2. Ambil data KalenderBlok satu kali
        $kalenderBlokMingguIni = KalenderBlok::where(function ($query) use ($tanggalMulai, $tanggalSelesai) {
            $query->where('tanggal_mulai', '<=', $tanggalSelesai)
                  ->where('tanggal_selesai', '>=', $tanggalMulai);
        })->get();

        $hariKerjaEfektif = [];
        foreach($semuaGuru as $guru) {
            // 3. Ambil jadwal lengkap (di-grup per hari)
            $jadwalPerHari = $guru->jadwalPelajaran->groupBy('hari');
            $hariKerjaList = collect(); 

            // 4. Looping setiap hari di rentang
            foreach ($tanggalRange as $tanggal) {
                $tanggal = $tanggal->startOfDay();
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                // 5. Pengecekan baru (Hari Aktif)
                if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }

                // 6. Pengecekan lama (Libur Nasional / Tiada Jadwal)
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalPerHari->has($namaHari)) {
                    continue;
                }
                
                // 7. Cari Tipe Minggu (Minggu 1 / Minggu 2)
                $kalenderBlokHariIni = $kalenderBlokMingguIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu));

                // 8. Filter jadwal berdasarkan Tipe Minggu
                $jadwalMentahHariIni = $jadwalPerHari->get($namaHari);
                
                $jadwalValid = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                });

                // 9. Jika ada jadwal valid, baru hitung sebagai hari kerja
                if ($jadwalValid->isNotEmpty()) {
                    $hariKerjaList->push($tanggal->toDateString());
                }
            }
            $hariKerjaEfektif[$guru->id] = $hariKerjaList;
        }

        $laporanHarianTeringkas = collect();
        $summaryTotal = [];

        foreach ($semuaGuru as $guru) {
            $laporanGuru = $guru->laporanHarian;
            $dataHarian = [];
            $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            foreach ($tanggalRange as $tanggal) {
                $tanggalCek = $tanggal->toDateString();
                $statusFinal = '-';
                $isHariKerja = isset($hariKerjaEfektif[$guru->id]) && $hariKerjaEfektif[$guru->id]->contains($tanggalCek);

                if ($isHariKerja) {
                    $laporanPerHari = $laporanGuru->filter(function ($laporan) use ($tanggalCek) { return $laporan->tanggal->toDateString() === $tanggalCek; });
                    if ($laporanPerHari->isNotEmpty()) {
                        if ($laporanPerHari->contains('status', 'Hadir')) { $statusFinal = 'H'; $totalHadir++; }
                        elseif ($laporanPerHari->contains('status', 'DL')) { $statusFinal = 'DL'; $totalDL++; }
                        elseif ($laporanPerHari->contains('status', 'Sakit')) { $statusFinal = 'S'; $totalSakit++; }
                        elseif ($laporanPerHari->contains('status', 'Izin')) { $statusFinal = 'I'; $totalIzin++; }
                        else { $statusFinal = 'A'; $totalAlpa++; }
                    } else {
                        if ($tanggal->isBefore($today)) { $statusFinal = 'A'; $totalAlpa++; }
                        else { $statusFinal = '-'; }
                    }
                }
                $dataHarian[$tanggalCek] = $statusFinal;
            }
            $laporanHarianTeringkas->push(['name' => $guru->name, 'dataHarian' => $dataHarian]);
            $summaryTotal[$guru->id] = compact('totalHadir', 'totalSakit', 'totalIzin', 'totalAlpa', 'totalDL');
        }
        // --- Akhir dari logika data ---

        // 3. Buat nama file
        $namaFile = "laporan_mingguan_{$tanggalMulai}_sd_{$tanggalSelesai}.xlsx";

        // 4. Panggil Export Class dan kirimkan data yang sudah matang
        return Excel::download(new LaporanMingguanExport(
            $laporanHarianTeringkas,
            $summaryTotal,
            $semuaGuru,
            $tanggalRange,
            $tanggalMulai,
            $tanggalSelesai,
            $hariKerjaEfektif
        ), $namaFile);
    }

    public function exportArsip()
    {
        return Excel::download(new ArsipLogbookExport, 'arsip-logbook-piket.xlsx');
    }

    public function exportIndividu(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $guruId = $request->user_id;
        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;

        $guru = User::findOrFail($guruId);
        $namaGuruClean = \Illuminate\Support\Str::slug($guru->name, '_');
        $namaFile = "laporan_individu_{$namaGuruClean}_{$tanggalMulai}_sd_{$tanggalSelesai}.xlsx";

        return Excel::download(new LaporanIndividuExport($guruId, $tanggalMulai, $tanggalSelesai), $namaFile);
    }

    
public function laporanTerlambatHarian()
    {
        // 1. Ambil semua data mentah
        $laporanTerlambatHariIni = LaporanHarian::where('tanggal', now('Asia/Jakarta')->toDateString())
            ->where('status_keterlambatan', 'Terlambat')
            ->with(['user', 'jadwalPelajaran'])
            ->orderBy('user_id')
            ->orderBy('jam_absen', 'asc') // Urutkan berdasarkan jam absen untuk grouping
            ->get();

        // 2. Kelompokkan berdasarkan guru, lalu kelompokkan lagi berdasarkan blok absensi
        //    Kita gunakan 'jam_absen' sebagai kunci unik untuk satu blok absensi
        $laporanPerBlok = $laporanTerlambatHariIni->groupBy('user_id')->map(function ($laporanGuru) {
            return $laporanGuru->groupBy('jam_absen');
        })->flatten(1); // Gabungkan semua grup blok menjadi satu level

        // 3. Proses data yang sudah di-grup
        $laporanBlokTerlambat = collect();
        foreach ($laporanPerBlok as $blok) {
            $laporanPertama = $blok->first(); // Ambil data pertama sebagai perwakilan
            $jamTerakhir = $blok->max('jadwalPelajaran.jam_ke'); // Cari jam terakhir di blok ini
            
            $laporanFinal = new \stdClass();
            $laporanFinal->laporan = $laporanPertama; // Data utama (foto, jam absen, user)
            $laporanFinal->jam_pertama = $laporanPertama->jadwalPelajaran->jam_ke;
            $laporanFinal->jam_terakhir = $jamTerakhir;
            $laporanFinal->kelas = $laporanPertama->jadwalPelajaran->kelas;

            $laporanBlokTerlambat->push($laporanFinal);
        }

        return view('admin.laporan.terlambat_harian', [
            'laporanTerlambat' => $laporanBlokTerlambat // Kirim data yang sudah digrup
        ]);
    }
}


