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
        $today = now('Asia/Jakarta')->startOfDay(); // Ambil tanggal hari ini

        $semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) use ($bulan, $tahun) {
                $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        $hariLibur = HariLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('tanggal')
            ->map(fn($date) => $date->toDateString());

        $laporanHarianTeringkas = collect();
        $summaryTotal = [];

        foreach ($semuaGuru as $guru) {
            $laporanGuru = $guru->laporanHarian;
            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
            $dataHarian = [];
            
            $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = Carbon::create($tahun, $bulan, $i)->startOfDay();
                $tanggalCek = $tanggal->toDateString();
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                
                $statusFinal = '-'; // Default untuk non-hari kerja

                // Cek apakah hari ini hari kerja untuk guru ini
                $adaJadwal = $jadwalHariGuru->has($namaHari);
                $isLibur = $hariLibur->contains($tanggalCek);
                
                if ($adaJadwal && !$isLibur) {
                    $laporanPerHari = $laporanGuru->where('tanggal', $tanggalCek);
                    
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
                        // ==========================================================
                        // ## REVISI LOGIKA ALPA: Hanya tandai Alpa jika hari sudah lewat ##
                        // ==========================================================
                        if ($tanggal->isBefore($today)) {
                            $statusFinal = 'A'; 
                            $totalAlpa++;
                        } else {
                            $statusFinal = '-'; // Jika hari kerja tapi di masa depan (atau hari ini)
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
            'bulan' => $bulan,
            'tahun' => $tahun,
            'daysInMonth' => $daysInMonth
        ]);
    }
   public function bulananSesi(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $today = now('Asia/Jakarta')->startOfDay(); // Ambil tanggal hari ini

        $semuaGuru = \App\Models\User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($bulan, $tahun) {
                $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }])
            ->orderBy('name', 'asc')
            ->get();
        
        $hariLibur = \App\Models\HariLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('tanggal')
            ->map(fn($date) => $date->toDateString());

        $laporanPerSesi = collect();

        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0;
            $totalHadir = 0;
            $totalTepatWaktu = 0;
            $totalTerlambat = 0;
            $totalSakit = 0;
            $totalIzin = 0;
            $totalAlpa = 0;
            $totalDL = 0;

            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
            $daysInMonth = \Carbon\Carbon::create($tahun, $bulan)->daysInMonth;

            // Loop setiap hari di bulan ini
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $tanggal = \Carbon\Carbon::create($tahun, $bulan, $i)->startOfDay();
                
                // Berhenti menghitung jika tanggal di masa depan
                if ($tanggal->isFuture()) {
                    break; 
                }
                
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) {
                    continue; // Lewati jika libur atau tidak ada jadwal
                }

                $tipeMinggu = \App\Models\KalenderBlok::whereDate('tanggal_mulai', '<=', $tanggal)
                                ->whereDate('tanggal_selesai', '>=', $tanggal)
                                ->first()->tipe_minggu ?? 'Reguler';
                
                $jadwalUntukHariIni = $jadwalHariGuru->get($namaHari)
                    ->whereIn('tipe_blok', ['Setiap Minggu', $tipeMinggu])
                    ->sortBy('jam_ke');

                // --- LOGIKA PENGELOMPOKKAN BLOK ---
                $tempBlock = null;
                $jadwalBlok = collect();
                foreach ($jadwalUntukHariIni as $jadwal) {
                    if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                        $tempBlock['jadwal_ids'][] = $jadwal->id;
                        $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                    } else {
                        if ($tempBlock) $jadwalBlok->push($tempBlock);
                        $tempBlock = [
                            'jadwal_ids' => [$jadwal->id],
                            'jam_pertama' => $jadwal->jam_ke,
                            'jam_terakhir' => $jadwal->jam_ke,
                            'kelas' => $jadwal->kelas,
                        ];
                    }
                }
                if ($tempBlock) $jadwalBlok->push($tempBlock);
                // --- AKHIR LOGIKA BLOK ---

                $totalSesiWajib += $jadwalBlok->count();

                // Hitung status untuk setiap blok
                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];
                    // Cek laporan berdasarkan ID jadwal pertama dari blok itu
                    $laporan = $guru->laporanHarian->where('jadwal_pelajaran_id', $jadwalPertamaId)->first();

                    if ($laporan) {
                        if ($laporan->status == 'Hadir') {
                            $totalHadir++;
                            if ($laporan->status_keterlambatan == 'Tepat Waktu') $totalTepatWaktu++;
                            if ($laporan->status_keterlambatan == 'Terlambat') $totalTerlambat++;
                        } 
                        elseif ($laporan->status == 'Sakit') $totalSakit++;
                        elseif ($laporan->status == 'Izin') $totalIzin++;
                        elseif ($laporan->status == 'DL') $totalDL++;
                        else $totalAlpa++; // Menghitung 'Alpa' yang di-override Piket
                    } else {
                        // Jika tidak ada laporan sama sekali (dan hari sudah lewat), hitung Alpa
                        $totalAlpa++;
                    }
                }
            }
            
            $persentaseHadir = ($totalSesiWajib > 0) ? ($totalHadir / $totalSesiWajib) * 100 : 0;
            $persentaseTepatWaktu = ($totalHadir > 0) ? ($totalTepatWaktu / $totalHadir) * 100 : 0;

            $laporanPerSesi->push([
                'name' => $guru->name,
                'totalSesiWajib' => $totalSesiWajib,
                'totalHadir' => $totalHadir,
                'totalTerlambat' => $totalTerlambat,
                'totalSakit' => $totalSakit,
                'totalIzin' => $totalIzin,
                'totalAlpa' => $totalAlpa,
                'totalDL' => $totalDL,
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
        
        $semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
            }, 'jadwalPelajaran'])
            ->orderBy('name', 'asc')->get();
        
        $hariLibur = HariLibur::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->pluck('tanggal')
            ->map(fn($date) => $date->toDateString());

        // Hitung hari kerja efektif untuk setiap guru dalam rentang ini
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

        // Proses peringkasan data
        $laporanHarianTeringkas = collect();
        $summaryTotal = [];

        foreach ($semuaGuru as $index => $guru) {
            $laporanGuru = $guru->laporanHarian;
            $dataHarian = [];
            
            $totalHadir = 0; $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            foreach ($tanggalRange as $tanggal) {
                $tanggalCek = $tanggal->toDateString();
                $statusFinal = '-'; // Default

                $isHariKerja = isset($hariKerjaEfektif[$guru->id]) && $hariKerjaEfektif[$guru->id]->contains($tanggalCek);

                if ($isHariKerja) {
                    $laporanPerHari = $laporanGuru->where('tanggal', $tanggalCek);
                    
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
                        // REVISI LOGIKA ALPA: Hanya tandai Alpa jika hari sudah lewat
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
            'semuaGuru' => $semuaGuru, // Dibutuhkan untuk mapping key di view
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'tanggalRange' => $tanggalRange
        ]);
    }

    public function mingguanSesi(Request $request)
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $tanggalSelesai = $request->input('tanggal_selesai', $today->toDateString());
        $tanggalMulai = $request->input('tanggal_mulai', $today->copy()->subDays(6)->toDateString());

        $tanggalRange = Carbon::parse($tanggalMulai)->locale('id_ID')->toPeriod(Carbon::parse($tanggalSelesai));

        $semuaGuru = \App\Models\User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
            }])
            ->orderBy('name', 'asc')
            ->get();
        
        $hariLibur = \App\Models\HariLibur::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->pluck('tanggal')
            ->map(fn($date) => $date->toDateString());

        $laporanPerSesi = collect();

        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0; $totalHadir = 0; $totalTepatWaktu = 0; $totalTerlambat = 0;
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');

            foreach ($tanggalRange as $tanggal) {
                if ($tanggal->isFuture()) break; // Berhenti jika hari di masa depan
                
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) continue;

                $tipeMinggu = \App\Models\KalenderBlok::whereDate('tanggal_mulai', '<=', $tanggal)
                                ->whereDate('tanggal_selesai', '>=', $tanggal)
                                ->first()->tipe_minggu ?? 'Reguler';
                
                $jadwalUntukHariIni = $jadwalHariGuru->get($namaHari)
                    ->whereIn('tipe_blok', ['Setiap Minggu', $tipeMinggu])
                    ->sortBy('jam_ke');
                
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

                // Hitung status untuk setiap blok
                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];
                    $laporan = $guru->laporanHarian->where('jadwal_pelajaran_id', $jadwalPertamaId)->first();

                    if ($laporan) {
                        if ($laporan->status == 'Hadir') {
                            $totalHadir++;
                            if ($laporan->status_keterlambatan == 'Tepat Waktu') $totalTepatWaktu++;
                            if ($laporan->status_keterlambatan == 'Terlambat') $totalTerlambat++;
                        } 
                        elseif ($laporan->status == 'Sakit') $totalSakit++;
                        elseif ($laporan->status == 'Izin') $totalIzin++;
                        elseif ($laporan->status == 'DL') $totalDL++;
                        else $totalAlpa++;
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

        return view('admin.laporan.mingguan_sesi', [
            'laporanPerSesi' => $laporanPerSesi,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
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
                ->with('piket') // Ambil relasi piket (untuk 'diabsen oleh')
                ->get()
                ->keyBy('jadwal_pelajaran_id');

            $jadwalGuru = $guruTerpilih->jadwalPelajaran->groupBy('hari');
            $hariLibur = \App\Models\HariLibur::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                ->pluck('tanggal')->map(fn($date) => $date->toDateString());
            
            $laporanFinal = collect(); // Ini akan menjadi collection akhir

            // 2. Loop setiap hari dalam rentang yang dipilih
            foreach (\Illuminate\Support\Carbon::parse($tanggalMulai)->toPeriod($tanggalSelesai) as $tanggal) {
                
                if ($tanggal->isFuture()) break; // Lewati hari di masa depan

                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');
                
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalGuru->has($namaHari)) {
                    continue;
                }

                $tipeMinggu = \App\Models\KalenderBlok::whereDate('tanggal_mulai', '<=', $tanggal)
                                ->whereDate('tanggal_selesai', '>=', $tanggal)
                                ->first()->tipe_minggu ?? 'Reguler';
                
                $jadwalHariIni = $jadwalGuru->get($namaHari)
                    ->whereIn('tipe_blok', ['Setiap Minggu', $tipeMinggu])
                    ->sortBy('jam_ke');

                // 3. LOGIKA PENGELOMPOKKAN BLOK (SESI MENGAJAR)
                $tempBlock = null;
                $jadwalBlok = collect();
                foreach ($jadwalHariIni as $jadwal) {
                    if ($tempBlock && $jadwal->jam_ke == $tempBlock['jam_terakhir'] + 1 && $jadwal->kelas == $tempBlock['kelas']) {
                        $tempBlock['jadwal_ids'][] = $jadwal->id;
                        $tempBlock['jam_terakhir'] = $jadwal->jam_ke;
                    } else {
                        if ($tempBlock) $jadwalBlok->push($tempBlock);
                        $tempBlock = [
                            'jadwal_ids' => [$jadwal->id],
                            'jam_pertama' => $jadwal->jam_ke,
                            'jam_terakhir' => $jadwal->jam_ke,
                            'kelas' => $jadwal->kelas,
                            'mata_pelajaran' => $jadwal->mata_pelajaran,
                            // Simpan ID jadwal pertama untuk referensi
                            'jadwal_pertama_id' => $jadwal->id, 
                        ];
                    }
                }
                if ($tempBlock) $jadwalBlok->push($tempBlock);
                // --- AKHIR LOGIKA BLOK ---

                // 4. Loop setiap BLOK sesi wajib pada hari itu
                foreach ($jadwalBlok as $blok) {
                    $jadwalPertamaId = $blok['jadwal_ids'][0];
                    $laporan = $laporanTersimpan->get($jadwalPertamaId);

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
                        $logSesi->piket = $laporan->piket; // Relasi piket
                    } else {
                        // Jika TIDAK ADA, buat record Alpa "virtual"
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

            // 4. Hitung summary berdasarkan $laporanFinal yang sudah lengkap
            $summary = [
                'Hadir' => $laporanFinal->where('status', 'Hadir')->count(),
                'Terlambat' => $laporanFinal->where('status', 'Hadir')->where('status_keterlambatan', 'Terlambat')->count(),
                'Sakit' => $laporanFinal->where('status', 'Sakit')->count(),
                'Izin' => $laporanFinal->where('status', 'Izin')->count(),
                'Alpa' => $laporanFinal->where('status', 'Alpa')->count(),
                'DL' => $laporanFinal->where('status', 'DL')->count(),
                'Total' => $laporanFinal->count() // Total Sesi
            ];
        }
        
        return view('admin.laporan.individu', [
            'semuaGuru' => $semuaGuru,
            'laporan' => $laporanFinal, // Kirim data yang sudah lengkap
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
                    $laporanPerHari = $laporanGuru->where('tanggal', $tanggalCek);
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
        $laporanTerlambat = LaporanHarian::where('tanggal', now('Asia/Jakarta')->toDateString())
            ->where('status_keterlambatan', 'Terlambat')
            ->with(['user', 'jadwalPelajaran']) // <-- REVISI: Ambil juga data jadwal
            ->orderBy('jam_absen', 'asc')
            ->get();

        return view('admin.laporan.terlambat_harian', compact('laporanTerlambat'));
    }
}

