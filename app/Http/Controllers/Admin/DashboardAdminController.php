<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\User;
use App\Models\HariLibur;
use App\Models\KalenderBlok;
use App\Models\MasterHariKerja; // <-- 1. TAMBAHKAN IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardAdminController extends Controller
{
    public function index()
    {
    
        $jumlahGuru = \App\Models\User::whereIn('role', ['guru', 'piket'])->count(); 
        $jumlahAkunPiket = \App\Models\JadwalPiket::select('user_id')->distinct()->count();
        $jumlahJadwal = \App\Models\JadwalPelajaran::count();

        $batasAbsen = 4;
        $bulanIni = now()->month;
        $tahunIni = now()->year;
        $today = now('Asia/Jakarta')->startOfDay();
        $awalBulan = Carbon::create($tahunIni, $bulanIni, 1)->startOfMonth();
        $akhirBulan = $awalBulan->clone()->endOfMonth();

        // 1. Ambil semua data yang relevan
        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($bulanIni, $tahunIni) {
                $query->whereMonth('tanggal', $bulanIni)->whereYear('tanggal', $tahunIni);
            }])
            ->get();
        
        $hariLibur = HariLibur::whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->pluck('tanggal')
            ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());
        
        // ==========================================================
        // ## MODIFIKASI DASBOR ADMIN ##
        // ==========================================================

        // 2. Ambil data KalenderBlok satu kali
        $kalenderBlokBulanIni = KalenderBlok::where(function ($query) use ($awalBulan, $akhirBulan) {
            $query->where('tanggal_mulai', '<=', $akhirBulan)
                  ->where('tanggal_selesai', '>=', $awalBulan);
        })->get();

        // 3. Ambil daftar hari kerja yang aktif dari DB
        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');
        
        $guruWarning = collect();

        // 4. Loop setiap guru untuk menghitung status harian
        foreach ($semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
            $laporanGuru = $guru->laporanHarian;
            
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0;

            // Loop setiap hari di bulan ini (hanya sampai hari ini)
            for ($i = 1; $i <= $today->day; $i++) {
                $tanggal = Carbon::create($tahunIni, $bulanIni, $i)->startOfDay();
                $tanggalCek = $tanggal->toDateString();
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                // 5. Tambahkan pengecekan hari aktif
                if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }

                // Cek libur nasional atau tidak ada jadwal
                if ($hariLibur->contains($tanggalCek) || !$jadwalHariGuru->has($namaHari)) {
                    continue;
                }
                
                // 6. PERBAIKAN LOGIKA TIPE BLOK (agar konsisten)
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

                // Ganti $adaJadwalBlok dengan $jadwalValid->isNotEmpty()
                if ($jadwalValid->isNotEmpty()) {
                    // Logika $laporanPerHari (tidak berubah)
                    $laporanPerHari = $laporanGuru->where('tanggal', $tanggalCek);
                    
                    if ($laporanPerHari->isNotEmpty()) {
                        if ($laporanPerHari->contains('status', 'Sakit')) {
                            $totalSakit++;
                        } elseif ($laporanPerHari->contains('status', 'Izin')) {
                            $totalIzin++;
                        }
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
        
        // Kirim semua data ke view
        return view('admin.dashboard', [
            'jumlahGuru' => $jumlahGuru,
            'jumlahAkunPiket' => $jumlahAkunPiket,
            'jumlahJadwal' => $jumlahJadwal,
            'guruWarning' => $guruWarning,
            'batasAbsen' => $batasAbsen, 
        ]);
    }
}