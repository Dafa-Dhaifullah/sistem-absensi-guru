<?php

namespace App\Http\Controllers\Piket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\KalenderBlok;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPiket;
use App\Models\LaporanHarian;
use App\Models\MasterJamPelajaran;
use App\Models\User;
use App\Models\HariLibur; // <-- 1. TAMBAHKAN IMPORT
use App\Models\MasterHariKerja; // <-- 2. TAMBAHKAN IMPORT

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = now('Asia/Jakarta');
        
        // 1. Cek Libur Nasional
        if ($libur = HariLibur::where('tanggal', $today->toDateString())->first()) {
            return view('piket.libur', ['keterangan' => $libur->keterangan]);
        }

        $hariMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $hariIni = $hariMap[$today->format('l')];
        
        // ==========================================================
        // ## MODIFIKASI DASBOR PIKET ##
        // ==========================================================
        
        // 2. Cek Hari Kerja Aktif
        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
        if (!$hariKerjaAktif->contains($hariIni)) {
            return view('piket.libur', ['keterangan' => 'Hari ini telah disetel sebagai hari tidak aktif oleh Administrator.']);
        }
        
        // 3. Cek Otorisasi Piket (Tidak berubah)
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';
        if (Auth::user()->role != 'admin' &&
            !JadwalPiket::where('hari', $hariIni)->where('sesi', $sesiSekarang)->where('user_id', Auth::id())->exists()
        ) {
            return redirect()->route('guru.dashboard')->withErrors('Anda tidak memiliki jadwal piket untuk sesi ini.');
        }

        // 4. PERBAIKAN LOGIKA TIPE BLOK (agar konsisten)
        $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)->first();
        
        $tipeMingguString = $tipeMinggu->tipe_minggu ?? 'Reguler';
        $nomorMinggu = trim(str_replace('Minggu', '', $tipeMingguString));
        
        // 5. Ambil semua jadwal dan FILTER setelahnya (sesuai logika baru)
        $semuaJadwalHariIni = JadwalPelajaran::where('hari', $hariIni)
            ->with('user')
            ->orderBy('user_id')
            ->orderBy('jam_ke', 'asc')
            ->get()
            ->filter(function ($jadwal) use ($tipeMingguString, $nomorMinggu) {
                $tipeBlokJadwal = $jadwal->tipe_blok;
                if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                if ($tipeMingguString == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                if ($tipeBlokJadwal == $tipeMingguString) return true;
                return false;
            });
        
        // 6. Sisa logika (tidak berubah, tapi sumber datanya $semuaJadwalHariIni sudah benar)
        $semuaGuruUnik = $semuaJadwalHariIni->pluck('user')
            ->where('role', 'guru')
            ->unique('id')
            ->values();

        $totalGuru = $semuaGuruUnik->count();


        $q = Str::of($request->query('q', ''))->trim();
        $guruWajibHadir = $semuaGuruUnik;

        if ($q->isNotEmpty()) {
            $term = Str::lower($q->value());
            $guruWajibHadir = $guruWajibHadir->filter(function ($u) use ($term) {
                return Str::contains(Str::lower($u->name), $term)
                    || Str::contains(Str::lower((string)($u->nip ?? '')), $term)
                    || Str::contains(Str::lower((string)($u->no_wa ?? '')), $term);
            })->values();
        }

        $masterJamHariIni = MasterJamPelajaran::where('hari', $hariIni)->get()->keyBy('jam_ke');

        $laporanHariIni = LaporanHarian::where('tanggal', $today->toDateString())
            ->get()
            ->keyBy('jadwal_pelajaran_id');

        return view('piket.dashboard', [
            'guruWajibHadir'     => $guruWajibHadir,
            'semuaJadwalHariIni' => $semuaJadwalHariIni,
            'masterJamHariIni'   => $masterJamHariIni,
            'hariIni'            => $hariIni,
            'tipeMinggu'         => $tipeMingguString, // Kirim string yang sudah konsisten
            'laporanHariIni'     => $laporanHariIni,
            'totalGuru'          => $totalGuru,
            'q'                  => $q->value(),
        ]);
    }
}