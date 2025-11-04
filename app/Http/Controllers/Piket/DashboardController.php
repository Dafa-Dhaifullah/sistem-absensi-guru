<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; // ← add this
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPiket;
use App\Models\LaporanHarian;
use App\Models\MasterJamPelajaran;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now('Asia/Jakarta');
        if ($libur = \App\Models\HariLibur::where('tanggal', $today->toDateString())->first()) {
            return view('piket.libur', ['keterangan' => $libur->keterangan]);
        }

        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $hariIni = $hariMap[$today->format('l')];
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';

        if (Auth::user()->role != 'admin' &&
            !JadwalPiket::where('hari', $hariIni)->where('sesi', $sesiSekarang)->where('user_id', Auth::id())->exists()
        ) {
            return redirect()->route('guru.dashboard')->withErrors('Anda tidak memiliki jadwal piket untuk sesi ini.');
        }

        $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)->first();

        $blokValid = ['Setiap Minggu'];
        if ($tipeMinggu) {
            if ($tipeMinggu->tipe_minggu == 'Minggu 1') $blokValid[] = 'Hanya Minggu 1';
            if ($tipeMinggu->tipe_minggu == 'Minggu 2') $blokValid[] = 'Hanya Minggu 2';
        }

        // 1) semua jadwal
        $semuaJadwalHariIni = JadwalPelajaran::where('hari', $hariIni)
            ->whereIn('tipe_blok', $blokValid)
            ->with('user')
            ->orderBy('user_id')
            ->orderBy('jam_ke', 'asc')
            ->get();

        // 2) guru unik (sebelum filter)
        $semuaGuruUnik = $semuaJadwalHariIni->pluck('user')
            ->where('role', 'guru')
            ->unique('id')
            ->values();

        $totalGuru = $semuaGuruUnik->count();

        // 2b) filter dengan query pencarian
        $q = Str::of($request->query('q', ''))->trim();
        $guruWajibHadir = $semuaGuruUnik;

        if ($q->isNotEmpty()) {
            $term = Str::lower($q->value());
            $guruWajibHadir = $guruWajibHadir->filter(function ($u) use ($term) {
                // sesuaikan field yang Anda miliki di tabel users (mis. nip, no_wa)
                return Str::contains(Str::lower($u->name), $term)
                    || Str::contains(Str::lower((string)($u->nip ?? '')), $term)
                    || Str::contains(Str::lower((string)($u->no_wa ?? '')), $term);
            })->values();
        }

        // 3) master jam & 4) laporan
        $masterJamHariIni = MasterJamPelajaran::where('hari', $hariIni)->get()->keyBy('jam_ke');

        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
            ->get()
            ->keyBy('jadwal_pelajaran_id');

        return view('piket.dashboard', [
            'guruWajibHadir'     => $guruWajibHadir,
            'semuaJadwalHariIni' => $semuaJadwalHariIni,
            'masterJamHariIni'   => $masterJamHariIni,
            'hariIni'            => $hariIni,
            'tipeMinggu'         => $tipeMinggu ? $tipeMinggu->tipe_minggu : 'Reguler',
            'laporanHariIni'     => $laporanHariIni,
            'totalGuru'          => $totalGuru,      // ← for counter
            'q'                  => $q->value(),     // ← for repopulate input
        ]);
    }
}
