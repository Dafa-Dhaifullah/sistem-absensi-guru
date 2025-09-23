<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataGuru;
use App\Models\LaporanHarian;
use App\Models\LogbookPiket;
use Illuminate\Support\Carbon;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
// use Maatwebsite\Excel\Facades\Excel; // (Nanti untuk export)

class LaporanController extends Controller
{
    /**
     * Menampilkan laporan rekap bulanan (Tampilan grid seperti Foto 2)
     */
    public function bulanan(Request $request)
    {
        // Tentukan bulan & tahun. Defaultnya bulan ini.
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        // Ambil semua guru, beserta data laporan harian HANYA di bulan & tahun tsb.
        $semuaGuru = DataGuru::with(['laporanHarian' => function($query) use ($bulan, $tahun) {
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }])->orderBy('nama_guru', 'asc')->get();

        // Ambil jumlah hari di bulan itu
        $daysInMonth = Carbon::createFromDate($tahun, $bulan)->daysInMonth;

        // Kirim data ke view
        // View-nya akan kita buat nanti (ini kompleks)
        return view('admin.laporan.bulanan', [
            'semuaGuru' => $semuaGuru,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'daysInMonth' => $daysInMonth
        ]);
    }

    /**
     * Menampilkan laporan rekap mingguan
     */
    public function mingguan(Request $request)
    {
        // Tentukan rentang tanggal. Defaultnya 7 hari terakhir.
        $tanggalSelesai = $request->input('tanggal_selesai', now()->toDateString());
        $tanggalMulai = $request->input('tanggal_mulai', now()->subDays(6)->toDateString());

        $semuaGuru = DataGuru::with(['laporanHarian' => function($query) use ($tanggalMulai, $tanggalSelesai) {
            $query->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
        }])->orderBy('nama_guru', 'asc')->get();

        // Buat daftar tanggal untuk header tabel
        $tanggalRange = Carbon::parse($tanggalMulai)->toPeriod(Carbon::parse($tanggalSelesai));

        // View-nya akan kita buat nanti
        return view('admin.laporan.mingguan', [
            'semuaGuru' => $semuaGuru,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'tanggalRange' => $tanggalRange
        ]);
    }

    /**
     * Menampilkan laporan detail per individu guru
     */
    public function individu(Request $request)
    {
        $semuaGuru = DataGuru::orderBy('nama_guru', 'asc')->get();
        $laporan = null;
        $summary = null;
        $guruTerpilih = null;

        // Jika user sudah memilih guru dan rentang tanggal
        if ($request->filled('data_guru_id') && $request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            
            $request->validate([
                'data_guru_id' => 'required|exists:data_guru,id',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            ]);

            $guruTerpilih = DataGuru::findOrFail($request->data_guru_id);
            
            // Ambil log laporan
            $laporan = LaporanHarian::where('data_guru_id', $guruTerpilih->id)
                ->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai])
                ->orderBy('tanggal', 'asc')
                ->get();

            // Hitung summary
            $summary = [
                'Hadir' => $laporan->where('status', 'Hadir')->count(),
                'Sakit' => $laporan->where('status', 'Sakit')->count(),
                'Izin' => $laporan->where('status', 'Izin')->count(),
                'Alpa' => $laporan->where('status', 'Alpa')->count(),
                'DL' => $laporan->where('status', 'DL')->count(),
                'Total' => $laporan->count()
            ];
        }
        

        // View-nya akan kita buat nanti
        return view('admin.laporan.individu', [
            'semuaGuru' => $semuaGuru,
            'laporan' => $laporan,
            'summary' => $summary,
            'guruTerpilih' => $guruTerpilih,
            'request' => $request // Untuk mengisi ulang form filter
        ]);
    }
    /**
 * Menampilkan jadwal pelajaran yang sedang berlangsung SAAT INI.
 */
public function realtime(Request $request)
{
    // 1. Peta Hari & Waktu
    $hariMap = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];
    $today = now();
    $hariIni = $hariMap[$today->format('l')]; // "Senin"
    $jamSekarang = $today->toTimeString(); // "18:50:00"

    // 2. Cari Tipe Minggu (Blok)
    $tipeMinggu = KalenderBlok::where('tanggal_mulai', '<=', $today)
                              ->where('tanggal_selesai', '>=', $today)
                              ->first();

    // 3. Cari "Jam Ke-" berapa SEKARANG
    $jamKeSekarang = MasterJamPelajaran::where('hari', $hariIni)
                        ->where('jam_mulai', '<=', $jamSekarang)
                        ->where('jam_selesai', '>=', $jamSekarang)
                        ->first();

    // 4. Query "Daftar Guru Wajib Hadir" (FINAL)
    $jadwalSekarang = collect(); // Buat koleksi kosong

    if ($jamKeSekarang) {
        // JIKA SEKARANG MASIH JAM PELAJARAN...
        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }

        $jadwalSekarang = JadwalPelajaran::where('hari', $hariIni)
                            ->where('jam_ke', $jamKeSekarang->jam_ke)
                            ->whereIn('tipe_blok', $blokValid)
                            ->with('dataGuru') // Ambil data guru
                            ->orderBy('kelas', 'asc') // Urutkan berdasarkan kelas
                            ->get();
    }
    

    // 5. Tampilkan View
    return view('admin.laporan.realtime', [
        'jadwalSekarang' => $jadwalSekarang,
        'hariIni' => $hariIni,
        'jamKeSekarang' => $jamKeSekarang,
        'tipeMinggu' => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
    ]);
    
}

    /**
     * Menampilkan arsip logbook piket
     */
    public function arsip(Request $request)
    {
        // Ambil data logbook terbaru, 15 per halaman
        $logbook = LogbookPiket::latest()->paginate(15);
        
        return view('admin.laporan.arsip', ['logbook' => $logbook]);
    }

    // --- METODE UNTUK EXPORT EXCEL (KITA BUAT NANTI) ---

    public function exportBulanan(Request $request)
    {
        // TODO: Panggil Export Class untuk Laporan Bulanan
        return 'Fitur export bulanan sedang dibuat.';
    }

    public function exportMingguan(Request $request)
    {
        // TODO: Panggil Export Class untuk Laporan Mingguan
        return 'Fitur export mingguan sedang dibuat.';
    }

    public function exportIndividu(Request $request)
    {
        // TODO: Panggil Export Class untuk Laporan Individu
        return 'Fitur export individu sedang dibuat.';
    }
    
}