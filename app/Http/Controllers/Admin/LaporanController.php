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

        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($bulan, $tahun) {
                $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
            }])
            ->orderBy('name', 'asc')
            ->get();
        
        $hariLibur = HariLibur::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('tanggal')
            ->map(fn($date) => $date->toDateString());

        $laporanPerSesi = collect();

        foreach ($semuaGuru as $guru) {
            $totalSesiWajib = 0;
            // Ambil jadwal hari guru ini (Senin, Selasa, dst.)
            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');

            // Loop setiap hari di bulan ini untuk menghitung total sesi wajib
            for ($i = 1; $i <= Carbon::create($tahun, $bulan)->daysInMonth; $i++) {
                $tanggal = Carbon::create($tahun, $bulan, $i);
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                // Lewati jika hari libur atau guru tidak punya jadwal di hari ini
                if ($hariLibur->contains($tanggal->toDateString()) || !$jadwalHariGuru->has($namaHari)) {
                    continue;
                }

                // Ambil semua jadwal guru untuk hari ini
                $jadwalUntukHariIni = $jadwalHariGuru->get($namaHari);
                
                // Tentukan tipe minggu untuk tanggal ini
                $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $tanggal)
                                ->whereDate('tanggal_selesai', '>=', $tanggal)
                                ->first()->tipe_minggu ?? 'Reguler';
                
                // Hitung sesi wajib berdasarkan tipe blok
                foreach ($jadwalUntukHariIni as $jadwal) {
                    if ($jadwal->tipe_blok == 'Setiap Minggu' || 
                       ($jadwal->tipe_blok == 'Hanya Minggu 1' && $tipeMinggu == 'Minggu 1') ||
                       ($jadwal->tipe_blok == 'Hanya Minggu 2' && $tipeMinggu == 'Minggu 2')) {
                        $totalSesiWajib++;
                    }
                }
            }

            // Hitung total dari laporan harian yang sudah ada
            $laporanGuru = $guru->laporanHarian;
            $totalHadir = $laporanGuru->where('status', 'Hadir')->count();
            $totalTepatWaktu = $laporanGuru->where('status', 'Hadir')->where('status_keterlambatan', 'Tepat Waktu')->count();
            $totalTerlambat = $laporanGuru->where('status', 'Hadir')->where('status_keterlambatan', 'Terlambat')->count();
            $totalSakit = $laporanGuru->where('status', 'Sakit')->count();
            $totalIzin = $laporanGuru->where('status', 'Izin')->count();
            $totalAlpa = $laporanGuru->where('status', 'Alpa')->count();
            $totalDL = $laporanGuru->where('status', 'DL')->count();
            
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
                'persentaseTepatWaktu' => round($persentaseTepatWaktu, 2), // <-- KIRIM DATA BARU
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

    public function individu(Request $request)
    {
        // Ambil daftar guru (untuk dropdown filter)
        $semuaGuru = User::where('role', 'guru')->orderBy('name', 'asc')->get();
        $laporan = null;
        $summary = null;
        $guruTerpilih = null;

        // Jika filter sudah diisi
        if ($request->filled('user_id') && $request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            ]);

            $guruTerpilih = User::findOrFail($request->user_id);
            
            // Ambil semua log laporan guru ini dalam rentang tanggal
            $laporan = LaporanHarian::where('user_id', $guruTerpilih->id)
                ->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai])
                ->with('jadwalPelajaran') // Ambil info jadwal terkait (kelas, jam ke)
                ->orderBy('tanggal', 'asc')
                ->orderBy('jam_absen', 'asc')
                ->get();

            // Hitung summary berdasarkan status
            $summary = [
                'Hadir' => $laporan->where('status', 'Hadir')->count(),
                'Terlambat' => $laporan->where('status', 'Hadir')->where('status_keterlambatan', 'Terlambat')->count(),
                'Sakit' => $laporan->where('status', 'Sakit')->count(),
                'Izin' => $laporan->where('status', 'Izin')->count(),
                'Alpa' => $laporan->where('status', 'Alpa')->count(),
                'DL' => $laporan->where('status', 'DL')->count(),
                'Total' => $laporan->count() // Total sesi yang tercatat
            ];
        }
        
        return view('admin.laporan.individu', [
            'semuaGuru' => $semuaGuru,
            'laporan' => $laporan,
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
        $request->validate(['tanggal_mulai' => 'required|date', 'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai']);
        $tanggalMulai = $request->tanggal_mulai;
        $tanggalSelesai = $request->tanggal_selesai;
        $namaFile = "laporan_mingguan_{$tanggalMulai}_sd_{$tanggalSelesai}.xlsx";
        return Excel::download(new LaporanMingguanExport($tanggalMulai, $tanggalSelesai), $namaFile);
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

