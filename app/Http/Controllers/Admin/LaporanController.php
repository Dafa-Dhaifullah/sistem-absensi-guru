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

class LaporanController extends Controller
{
    /**
     * Menampilkan laporan rekap bulanan.
     */
    public function bulanan(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        // REVISI: Ambil pengguna dengan role 'guru', bukan 'piket'
        $semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) use ($bulan, $tahun) {
                $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
            }])
            ->orderBy('name', 'asc')->get();

        $daysInMonth = Carbon::createFromDate($tahun, $bulan)->daysInMonth;

        return view('admin.laporan.bulanan', [
            'semuaGuru' => $semuaGuru,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'daysInMonth' => $daysInMonth
        ]);
    }

    /**
     * Menampilkan laporan rekap mingguan.
     */
    public function mingguan(Request $request)
    {
        $tanggalSelesai = $request->input('tanggal_selesai', now()->toDateString());
        $tanggalMulai = $request->input('tanggal_mulai', now()->subDays(6)->toDateString());

        // REVISI: Ambil pengguna dengan role 'guru', bukan 'piket'
        $semuaGuru = User::where('role', 'guru')
            ->with(['laporanHarian' => function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
            }])
            ->orderBy('name', 'asc')->get();

        // Tambahkan ->locale('id_ID') untuk memastikan Carbon menggunakan Bahasa Indonesia
$tanggalRange = Carbon::parse($tanggalMulai)->locale('id_ID')->toPeriod(Carbon::parse($tanggalSelesai));

        return view('admin.laporan.mingguan', [
            'semuaGuru' => $semuaGuru,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'tanggalRange' => $tanggalRange
        ]);
    }

    /**
     * Menampilkan laporan detail per individu guru.
     */
    public function individu(Request $request)
    {
        // REVISI: Ambil pengguna dengan role 'guru', bukan 'piket'
        $semuaGuru = User::where('role', 'guru')->orderBy('name', 'asc')->get();
        $laporan = null;
        $summary = null;
        $guruTerpilih = null;

        if ($request->filled('user_id') && $request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            ]);

            $guruTerpilih = User::findOrFail($request->user_id);
            
            $laporan = LaporanHarian::where('user_id', $guruTerpilih->id)
                ->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai])
                ->orderBy('tanggal', 'asc')
                ->get();

            $summary = [
                'Hadir' => $laporan->where('status', 'Hadir')->count(),
                'Sakit' => $laporan->where('status', 'Sakit')->count(),
                'Izin' => $laporan->where('status', 'Izin')->count(),
                'Alpa' => $laporan->where('status', 'Alpa')->count(),
                'DL' => $laporan->where('status', 'DL')->count(),
                'Total' => $laporan->count()
            ];
        }
        
        return view('admin.laporan.individu', [
            'semuaGuru' => $semuaGuru,
            'laporan' => $laporan,
            'summary' => $summary,
            'guruTerpilih' => $guruTerpilih,
            'request' => $request
        ]);
    }
    
    /**
    * Menampilkan jadwal pelajaran yang sedang berlangsung.
    */
    public function realtime(Request $request)
    {
        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $today = now('Asia/Jakarta');
        $hariIni = $hariMap[$today->format('l')];
        $jamSekarang = $today->toTimeString();

        $tipeMinggu = KalenderBlok::where('tanggal_mulai', '<=', $today)
                                  ->where('tanggal_selesai', '>=', $today)
                                  ->first();

        $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                            ->where('jam_mulai', '<=', $jamSekarang)
                            ->where('jam_selesai', '>=', $jamSekarang)
                            ->first();

        $jadwalSekarang = collect();

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
        
        
        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
                            ->get()
                            ->keyBy('user_id');

        return view('admin.laporan.realtime', [
            'jadwalSekarang' => $jadwalSekarang,
            'hariIni' => $hariIni,
            'jamKeSekarang' => $jamKeSekarang,
            'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
            'laporanHariIni' => $laporanHariIni // <-- KIRIM DATA BARU INI
        ]);
    }

    /**
    * Menampilkan arsip logbook piket.
    */
    public function arsip(Request $request)
    {
        $logbook = LogbookPiket::latest()->paginate(15);
        return view('admin.laporan.arsip', ['logbook' => $logbook]);
    }

    // --- METODE UNTUK EXPORT EXCEL ---

    public function exportBulanan(Request $request)
    {
        $request->validate(['bulan' => 'required|integer|between:1,12', 'tahun' => 'required|integer|min:2000']);
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $namaBulan = Carbon::create()->month($bulan)->isoFormat('MMMM');
        $namaFile = "laporan_bulanan_{$namaBulan}_{$tahun}.xlsx";
        return Excel::download(new LaporanBulananExport($bulan, $tahun), $namaFile);
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
}

