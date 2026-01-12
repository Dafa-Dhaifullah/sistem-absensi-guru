<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LaporanHarian;
use App\Models\JadwalPelajaran;
use App\Models\MasterJamPelajaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AbsenController extends Controller
{
    public function store(Request $request)
    {
        // 1. === VALIDASI ANTI GALERI (Security Layer) ===
        // Jika request terdeteksi membawa file fisik (hasil dari input type="file"),
        // sistem akan menolaknya. User WAJIB pakai kamera live di aplikasi.
        if ($request->hasFile('foto_selfie')) {
             return redirect()->back()
                ->withErrors(['foto_selfie' => 'KEAMANAN: Upload dari galeri tidak diizinkan! Mohon gunakan kamera langsung.'])
                ->withInput();
        }

        // 2. === VALIDASI INPUT ===
        $validated = $request->validate([
            'qr_token' => 'required|string',
            'jadwal_ids' => 'required|array',
            'jadwal_ids.*' => 'exists:jadwal_pelajaran,id',
            // Validasi Base64 String (Bukan Image File)
            'foto_selfie_base64' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!preg_match('/^data:image\/(\w+);base64,/', $value)) {
                    $fail('Format foto tidak valid. Pastikan mengambil foto langsung dari kamera aplikasi.');
                }
            }],
        ]);

        $user = Auth::user();
        $today = now('Asia/Jakarta');
        
        $jadwals = JadwalPelajaran::find($validated['jadwal_ids']);
        if($jadwals->isEmpty()) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Jadwal tidak valid.']);
        }
        $jadwalPertama = $jadwals->first();

        // --- Logika Validasi Jadwal (Sama seperti sebelumnya) ---
        foreach ($jadwals as $jadwal) {
            if ($jadwal->user_id != $user->id) {
                return redirect()->back()->withErrors(['foto_selfie' => 'Jadwal tidak sesuai dengan akun Anda.']);
            }
            
            $laporanExist = LaporanHarian::where('jadwal_pelajaran_id', $jadwal->id)
                                         ->where('tanggal', $today->toDateString())
                                         ->first();
            
            // Cek apakah data yang ada adalah absensi mandiri (selfie)
            if ($laporanExist && $laporanExist->diabsen_oleh == $user->id && $laporanExist->status == 'Hadir') {
                return redirect()->back()->withErrors(['foto_selfie' => 'Anda sudah melakukan absensi mandiri (selfie) untuk jam pelajaran ini.']);
            }
        }
        
        // --- Validasi QR Code ---
        $qrKelas = $validated['qr_token'];
        $jadwalKelas = $jadwalPertama->kelas;
        if ($qrKelas !== $jadwalKelas) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Gagal validasi QR Code: QR Code tidak sesuai dengan kelas yang dijadwalkan.']);
        }

        // --- Logika Status Keterlambatan ---
        $masterJamPertama = MasterJamPelajaran::where('hari', $jadwalPertama->hari)->where('jam_ke', $jadwalPertama->jam_ke)->first();
        if (!$masterJamPertama) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Master jam pelajaran tidak ditemukan.']);
        }
        $batasToleransi = Carbon::parse($today->toDateString() . ' ' . $masterJamPertama->jam_mulai)->addMinutes(15);
        $statusKeterlambatan = ($today->isAfter($batasToleransi)) ? 'Terlambat' : 'Tepat Waktu';

        // 3. === PROSES DECODE GAMBAR BASE64 (Pengganti Intervention Image Upload) ===
        try {
            // Ambil string base64
            $base64String = $request->input('foto_selfie_base64');
            
            // Pisahkan header "data:image/jpeg;base64,"
            $imageParts = explode(";base64,", $base64String);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1] ?? 'jpg'; // Default jpg jika error
            $imageBase64 = base64_decode($imageParts[1]);

            // Buat nama file
            $fileName = Str::uuid() . '.' . $imageType;
            $folderPath = 'public/selfies/' . $today->format('Y-m');
            $pathFoto = $folderPath . '/' . $fileName;

            // Simpan ke Storage
            Storage::put($pathFoto, $imageBase64);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['foto_selfie' => 'Gagal memproses gambar kamera: ' . $e->getMessage()]);
        }
        
        // --- Simpan ke Database ---
        foreach ($jadwals as $jadwal) {
            LaporanHarian::updateOrCreate(
                [
                    'tanggal' => $today->toDateString(),
                    'jadwal_pelajaran_id' => $jadwal->id,
                ],
                [
                    'user_id' => $user->id,
                    'status' => 'Hadir',
                    'jam_absen' => $today->toTimeString(),
                    'foto_selfie_path' => $pathFoto, // Path hasil decode
                    'status_keterlambatan' => $statusKeterlambatan,
                    'diabsen_oleh' => $user->id,
                    'keterangan_piket' => null
                ]
            );
        }

        return redirect()->route('guru.dashboard')->with('success', 'Absensi untuk kelas ' . $jadwalPertama->kelas . ' berhasil! Status Anda telah diperbarui.');
    }
}