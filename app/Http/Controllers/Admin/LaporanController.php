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
use App\Exports\LaporanSesiExport;

class LaporanController extends Controller
{

   public function bulanan(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $daysInMonth = Carbon::createFromDate($tahun, $bulan)->daysInMonth;
        $today = now('Asia/Jakarta')->startOfDay(); 

        $semuaGuru = \App\Models\User::where('role', 'guru')
            ->with(['laporanHarian', 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        $hariLibur = \App\Models\HariLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('tanggal')
            ->map(fn($date) => $date->toDateString());

        $hariKerjaEfektif = [];
        foreach($semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->pluck('hari')->unique();
            $hariKerjaList = collect(); 
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = Carbon::create($tahun, $bulan, $i); // Perbaikan: $this->tahun dan $this->bulan
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                if ($jadwalHariGuru->contains($namaHari) && !$hariLibur->contains($tanggal->toDateString())) {
                    $hariKerjaList->push($tanggal->toDateString());
                }
            }
            $this->hariKerjaEfektif[$guru->id] = $hariKerjaList;
        }

        $laporanHarianTeringkas = collect();
        $summaryTotal = [];

        foreach ($semuaGuru as $index => $guru) {
            $laporanGuru = $guru->laporanHarian->where('tanggal', '>=', Carbon::create($tahun, $bulan, 1)->startOfMonth())
                                              ->where('tanggal', '<=', Carbon::create($tahun, $bulan, 1)->endOfMonth());
            $dataHarian = [];
            
            $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = Carbon::create($tahun, $bulan, $i)->startOfDay();
                $tanggalCek = $tanggal->toDateString();
                
                $statusFinal = '-'; 
                $isHariKerja = isset($this->hariKerjaEfektif[$guru->id]) && $this->hariKerjaEfektif[$guru->id]->contains($tanggalCek);
                
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
            'hariKerjaEfektif' => $this->hariKerjaEfektif // Kirim data ini
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
            ->pluck('tanggal')->map(fn($date) => $date->toDateString());

        $kalenderBlokBulanIni = KalenderBlok::where(function ($query) use ($awalBulan, $akhirBulan) {
            $query->where('tanggal_mulai', '<=', $akhirBulan)
                  ->where('tanggal_selesai', '>=', $awalBulan);
        })->get();

        $laporanPerSesi = collect();

        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0; $totalHadir = 0; $totalTepatWaktu = 0; $totalTerlambat = 0;
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
            $daysInMonth = $awalBulan->daysInMonth;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = $awalBulan->clone()->addDays($i - 1);
                
                if ($tanggal->gt($today)) break; 
                
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) continue;

                // --- INI ADALAH LOGIKA PENCARIAN BLOK ---
                $kalenderBlokHariIni = $kalenderBlokBulanIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                // $tipeMinggu akan berisi (contoh): "Minggu 1", "Minggu 2", atau "Reguler"
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                // --- AKHIR DARI LOGIKA PENCARIAN BLOK ---

                
                // ==========================================================
                // ## PERBAIKAN LOGIKA FILTER BLOK ##
                // ==========================================================
                
                // 1. Ekstrak nomornya (misal: "1" dari "Minggu 1")
                // Kita gunakan trim() untuk jaga-jaga jika ada spasi
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu)); 

                $jadwalMentahHariIni = $jadwalHariGuru->get($namaHari);

                // 2. Kita gunakan filter manual (bukan whereIn)
                $jadwalUntukHariIni = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    
                    $tipeBlokJadwal = $jadwal->tipe_blok;

                    // Kondisi 1: Selalu loloskan 'Setiap Minggu'
                    if ($tipeBlokJadwal == 'Setiap Minggu') {
                        return true;
                    }

                    // Kondisi 2: Cek jika $tipeMinggu adalah "Reguler", jadwal harus "Reguler"
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') {
                        return true;
                    }

                    // Kondisi 3: Cek jika nomor minggu (misal "1")
                    // terkandung di dalam string jadwal (misal "Hanya Minggu 1,2")
                    // Kita juga cek jika $nomorMinggu bukan "Reguler"
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) {
                        return true;
                    }

                    // Kondisi 4: Cek kecocokan penuh (jika kebetulan namanya sama)
                    if ($tipeBlokJadwal == $tipeMinggu) {
                        return true;
                    }

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
                        $totalAlpa++;
                    }
                }
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
            ->map(fn($date) => $date->toDateString());

        $hariKerjaEfektif = [];
        foreach($semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->pluck('hari')->unique();
            $hariKerjaList = collect(); 
            foreach ($tanggalRange as $tanggal) {
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                if ($jadwalHariGuru->contains($namaHari) && !$hariLibur->contains($tanggal->toDateString())) {
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
                    // ==========================================================
                    // ## PERBAIKAN DI SINI: Bandingkan Teks dengan Teks ##
                    // ==========================================================
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
            ->map(fn($date) => $date->toDateString());

        // --- OPTIMASI (N+1): Ambil data KalenderBlok 1x ---
        $kalenderBlokMingguIni = KalenderBlok::where(function ($query) use ($awalMinggu, $akhirMinggu) {
            $query->where('tanggal_mulai', '<=', $akhirMinggu)
                  ->where('tanggal_selesai', '>=', $awalMinggu);
        })->get();
        // --- Akhir Optimasi ---

        $laporanPerSesi = collect();

        // --- 2. LOOPING PER GURU ---
        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0; $totalHadir = 0; $totalTepatWaktu = 0; $totalTerlambat = 0;
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');

            // --- 3. LOOPING PER HARI ---
            foreach ($tanggalRange as $tanggal) {
                $tanggal = $tanggal->startOfDay(); // Pastikan start of day
                
                if ($tanggal->gt($today)) break; // Berhenti jika hari di masa depan
                
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
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
                
                // ==========================================================
                // ## PERBAIKAN LOGIKA FILTER BLOK (str_contains) ##
                // ==========================================================
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

                    // ==========================================================
                    // ## PERBAIKAN BUG KRITIS ADA DI SINI ##
                    // Tambahkan ->where('tanggal', $tanggal)
                    // ==========================================================
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
                ->pluck('tanggal')->map(fn($date) => $date->toDateString());
            
            // ==========================================================
            // ## PERBAIKAN N+1 QUERY ##
            // ==========================================================
            $kalenderBlokPeriodeIni = \App\Models\KalenderBlok::where(function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->where('tanggal_mulai', '<=', $tanggalSelesai)
                      ->where('tanggal_selesai', '>=', $tanggalMulai);
            })->get();
            // ==========================================================
            
            $laporanFinal = collect(); // Ini akan menjadi collection akhir (per blok)

            // 2. Loop setiap hari dalam rentang yang dipilih
            foreach (\Illuminate\Support\Carbon::parse($tanggalMulai)->toPeriod($tanggalSelesai) as $tanggal) {
                
                if ($tanggal->isFuture()) break; 

                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalGuru->has($namaHari)) {
                    continue;
                }

                // ==========================================================
                // ## PERBAIKAN LOGIKA PENCARIAN BLOK (dari N+1) ##
                // ==========================================================
                $kalenderBlokHariIni = $kalenderBlokPeriodeIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = \Illuminate\Support\Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = \Illuminate\Support\Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
                });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                // ==========================================================
                
                // ==========================================================
                // ## PERBAIKAN LOGIKA FILTER BLOK (str_contains) ##
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
            $tipeMinggu = 'Hari Libur';
        } else {
            // 2. Cari Jam Ke- berapa sekarang
            $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                                ->where('jam_mulai', '<=', $jamSekarang)
                                ->where('jam_selesai', '>=', $jamSekarang)
                                ->first();
            
            $jadwalSekarang = collect();
            $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)
                                      ->whereDate('tanggal_selesai', '>=', $today)
                                      ->first();
            
            // 3. Jika sedang jam pelajaran, cari semua jadwal
            if ($jamKeSekarang) {
                $blokValid = ['Setiap Minggu'];
                if ($tipeMinggu) {
                    if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
                    if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
                }

                $jadwalSekarang = JadwalPelajaran::where('hari', $hariIni)
                                    ->where('jam_ke', $jamKeSekarang->jam_ke)
                                    ->whereIn('tipe_blok', $blokValid)
                                    ->with('user')
                                    ->orderBy('kelas', 'asc')
                                    ->get();
            }

            // 4. Ambil semua laporan hari ini untuk dicek statusnya
            $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                                ->get()
                                ->keyBy('jadwal_pelajaran_id'); // Gunakan jadwal_id sebagai kunci
        }

        return view('admin.laporan.realtime', [
            'jadwalSekarang' => $jadwalSekarang,
            'hariIni' => $hariIni,
            'jamKeSekarang' => $jamKeSekarang,
            'tipeMinggu' => $tipeMinggu->tipe_minggu ?? ($tipeMinggu ?? 'Reguler'),
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
            ->map(fn($date) => $date->toDateString());

        $hariKerjaEfektif = [];
        foreach($semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->pluck('hari')->unique();
            $hariKerjaList = collect(); 
            foreach ($tanggalRange as $tanggal) {
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                if ($jadwalHariGuru->contains($namaHari) && !$hariLibur->contains($tanggal->toDateString())) {
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
            $tanggalSelesai
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

