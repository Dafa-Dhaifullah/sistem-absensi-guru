<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\User;
use App\Models\HariLibur;
use App\Models\KalenderBlok;
use App\Models\MasterHariKerja; // Import
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
        $now = now('Asia/Jakarta');
        $awalBulan = Carbon::create($tahunIni, $bulanIni, 1)->startOfMonth();
        $akhirBulan = $awalBulan->clone()->endOfMonth();

        $semuaGuru = User::where('role', 'guru')
            ->with(['jadwalPelajaran', 'laporanHarian' => function ($query) use ($bulanIni, $tahunIni) {
                $query->whereMonth('tanggal', $bulanIni)->whereYear('tanggal', $tahunIni);
            }])
            ->get();
        
        $hariLibur = HariLibur::whereMonth('tanggal', $bulanIni)
            ->whereYear('tanggal', $tahunIni)
            ->pluck('tanggal')
            ->map(fn($dateString) => \Carbon\Carbon::parse($dateString)->toDateString());
        
        $kalenderBlokBulanIni = KalenderBlok::where(function ($query) use ($awalBulan, $akhirBulan) {
            $query->where('tanggal_mulai', '<=', $akhirBulan)
                  ->where('tanggal_selesai', '>=', $awalBulan);
        })->get();

        $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

        $jamTerakhirPerHari = \App\Models\MasterJamPelajaran::select('hari', \DB::raw('MAX(jam_selesai) as jam_terakhir'))
                            ->groupBy('hari')
                            ->pluck('jam_terakhir', 'hari');
        
        $guruWarning = collect();

        // ==========================================================
        // LOGIKA GURU WARNING (PERINGATAN AKUMULASI)
        // ==========================================================
        foreach ($semuaGuru as $guru) {
            $jadwalHariGuru = $guru->jadwalPelajaran->groupBy('hari');
            $laporanGuru = $guru->laporanHarian;
            
            // 1. Inisialisasi total di SINI (per guru)
            $totalSakit = 0; $totalIzin = 0; $totalAlpa = 0; $totalDL = 0;

            // 2. Loop setiap hari HANYA sampai hari ini
            for ($i = 1; $i <= $today->day; $i++) {
                $tanggal = Carbon::create($tahunIni, $bulanIni, $i)->startOfDay();
                $tanggalCek = $tanggal->toDateString();
                $namaHari = $tanggal->locale('id_ID')->isoFormat('dddd');

                // Lewati jika bukan hari kerja aktif
                if (!$hariKerjaAktif->contains($namaHari)) {
                    continue;
                }

                // Lewati jika libur nasional atau guru tidak ada jadwal
                if ($hariLibur->contains($tanggalCek) || !$jadwalHariGuru->has($namaHari)) {
                    continue;
                }
                
                // Cek tipe blok (Minggu 1 / Minggu 2 / Reguler)
                $kalenderBlokHariIni = $kalenderBlokBulanIni->firstWhere(function ($blok) use ($tanggal) {
                    $mulai = Carbon::parse($blok->tanggal_mulai)->startOfDay();
                    $selesai = Carbon::parse($blok->tanggal_selesai)->startOfDay();
                    return $tanggal->gte($mulai) && $tanggal->lte($selesai);
               });
                $tipeMinggu = $kalenderBlokHariIni->tipe_minggu ?? 'Reguler';
                $nomorMinggu = trim(str_replace('Minggu', '', $tipeMinggu));
                
                // Filter jadwal yang valid untuk hari itu berdasarkan tipe blok
                $jadwalMentahHariIni = $jadwalHariGuru->get($namaHari);
                $jadwalValid = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMinggu, $nomorMinggu) {
                    $tipeBlokJadwal = $jadwal->tipe_blok;
                    if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                    if ($tipeMinggu == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                    if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                    if ($tipeBlokJadwal == $tipeMinggu) return true;
                    return false;
                });

                // Jika guru punya jadwal valid hari itu, cek laporannya
                if ($jadwalValid->isNotEmpty()) {
                    $laporanPerHari = $laporanGuru->where('tanggal', $tanggalCek);
                    
                    // 3. Logika Prioritas Status (Hadir > DL > S > I > A)
                    if ($laporanPerHari->isNotEmpty()) {
                        if ($laporanPerHari->contains('status', 'Hadir')) {
                            // Hadir -> Tidak dihitung
                        } elseif ($laporanPerHari->contains('status', 'DL')) {
                            $totalDL++;
                        } elseif ($laporanPerHari->contains('status', 'Sakit')) {
                            $totalSakit++;
                        } elseif ($laporanPerHari->contains('status', 'Izin')) {
                            $totalIzin++;
                        } elseif ($laporanPerHari->contains('status', 'Alpa')) {
                            $totalAlpa++;
                        }
                    } else {
                        // 4. Logika Alpa (jika tidak ada data laporan sama sekali)
                    // Hanya hitung Alpa jika tanggalnya SUDAH LEWAT (bukan hari ini)
                        if ($tanggal->isBefore($today)) {
                            $totalAlpa++;
                     }
                     elseif ($tanggal->is($today)) {
                            // Dapatkan jam_terakhir untuk hari ini
                            $jamTerakhirString = $jamTerakhirPerHari->get($namaHari);

                            if ($jamTerakhirString) {
                                // Cek apakah WAKTU SEKARANG sudah melewati jam terakhir
                                if ($now->toTimeString() > $jamTerakhirString) {
                                    $totalAlpa++;
                                 }
                                // Jika belum, jangan hitung (masih "Belum Absen")
                            }
                        }
                    }
                }
            } // <-- Akhir dari loop 'for' ($i)
            
            // 5. Kalkulasi Total dan Pengecekan Batas (DI LUAR 'for')
            $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa + $totalDL;
            
            // PASTIKAN ANDA MENGGUNAKAN OPERATOR '>=' DI SINI
         if ($totalTidakHadir >= $batasAbsen) {
                $guru->total_tidak_hadir = $totalTidakHadir;
                $guruWarning->push($guru);
            }
        } // <-- Akhir dari loop 'foreach' ($guru)
        
        return view('admin.dashboard', [
            'jumlahGuru' => $jumlahGuru,
            'jumlahAkunPiket' => $jumlahAkunPiket,
            'jumlahJadwal' => $jumlahJadwal,
            'guruWarning' => $guruWarning,
            'batasAbsen' => $batasAbsen, 
        ]);
    }
}