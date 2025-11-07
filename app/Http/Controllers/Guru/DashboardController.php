<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LaporanHarian;
use App\Models\JadwalPiket;
use App\Models\User;
use App\Models\KalenderBlok;
use App\Models\MasterJamPelajaran;
use App\Models\HariLibur; // <-- 1. TAMBAHKAN IMPORT
use App\Models\MasterHariKerja; // <-- 2. TAMBAHKAN IMPORT

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = now('Asia/Jakarta');
        $hariMap = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
        $hariIni = $hariMap[$today->format('l')];
        
        // --- Inisialisasi variabel default ---
        $jadwalBlok = collect();
        $laporanHariIni = collect();
        $masterJamHariIni = collect();
        $guruPiketHariIni = collect();
        $isPiket = false;
        $sesiSekarang = ($today->hour < 12) ? 'Pagi' : 'Siang';
        $tipeMingguString = 'Reguler'; // Default
        
        // ==========================================================
        // LOGIKA BARU: PENGECEKAN HARI AKTIF & HARI LIBUR
        // ==========================================================

        // 1. Cek apakah hari ini libur nasional (dari tabel hari_libur)
        $isLiburNasional = HariLibur::where('tanggal', $today->toDateString())->exists();

        if ($isLiburNasional) {
            $tipeMingguString = 'Hari Libur';
        } else {
            // 2. Cek apakah hari ini hari kerja aktif (dari tabel master_hari_kerja)
            $hariKerjaAktif = MasterHariKerja::where('is_aktif', 1)->pluck('nama_hari');

            if (!$hariKerjaAktif->contains($hariIni)) {
                $tipeMingguString = 'Hari Tidak Aktif';
            } else {
                // --- HARI INI ADALAH HARI KERJA AKTIF ---

                if ($user->jadwalPelajaran) {
                    // ==========================================================
                    // PERBAIKAN BUG TIPE BLOK (agar konsisten dengan Laporan)
                    // ==========================================================
                    $tipeMinggu = KalenderBlok::whereDate('tanggal_mulai', '<=', $today)->whereDate('tanggal_selesai', '>=', $today)->first();
                    $tipeMingguString = $tipeMinggu->tipe_minggu ?? 'Reguler';
                    $nomorMinggu = trim(str_replace('Minggu', '', $tipeMingguString));

                    // Ambil semua jadwal hari ini dulu
                    $jadwalMentahHariIni = $user->jadwalPelajaran()
                        ->where('hari', $hariIni)
                        ->orderBy('jam_ke', 'asc')
                        ->get();

                    // Filter berdasarkan tipe blok (logika str_contains)
                    $jadwalHariIni = $jadwalMentahHariIni->filter(function ($jadwal) use ($tipeMingguString, $nomorMinggu) {
                        $tipeBlokJadwal = $jadwal->tipe_blok;
                        if ($tipeBlokJadwal == 'Setiap Minggu') return true;
                        if ($tipeMingguString == 'Reguler' && $tipeBlokJadwal == 'Reguler') return true;
                        if ($nomorMinggu != 'Reguler' && str_contains($tipeBlokJadwal, $nomorMinggu)) return true;
                        if ($tipeBlokJadwal == $tipeMingguString) return true;
                        return false;
                    });
                    // ==========================================================

                    // Proses pengelompokan (Logika ini sudah benar)
                    $tempBlock = null;
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
                            ];
                        }
                    }
                    if ($tempBlock) $jadwalBlok->push($tempBlock); // Simpan blok terakhir
                }

                // Cek laporan absensi hari ini
                $laporanHariIni = LaporanHarian::where('user_id', $user->id)
                    ->where('tanggal', $today->toDateString())
                    ->get()
                    ->keyBy('jadwal_pelajaran_id');
                
                $masterJamHariIni = MasterJamPelajaran::where('hari', $hariIni)->get()->keyBy('jam_ke');
                
                // --- Ambil data guru piket & Cek status piket (tidak berubah) ---
                $piketIds = JadwalPiket::where('hari', $hariIni)->where('sesi', $sesiSekarang)->pluck('user_id');
                $guruPiketHariIni = User::whereIn('id', $piketIds)->get();
                $isPiket = $piketIds->contains($user->id);
            }
        }

        return view('guru.dashboard', [
            'jadwalBlok' => $jadwalBlok, // <-- Kirim data blok
            'laporanHariIni' => $laporanHariIni,
            'guruPiketHariIni' => $guruPiketHariIni,
            'sesiSekarang' => $sesiSekarang,
            'hariIni' => $hariIni,
            'tipeMinggu' => $tipeMingguString, // Kirim string yang sudah diproses
            'masterJamHariIni' => $masterJamHariIni,
            'sedangPiket' => $isPiket,
        ]);
    }
}