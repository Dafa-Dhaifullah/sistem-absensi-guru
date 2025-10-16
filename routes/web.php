<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Import Semua Controller
|--------------------------------------------------------------------------
*/
// Controller Admin
use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\JadwalPiketController;
use App\Http\Controllers\Admin\KalenderBlokController;
use App\Http\Controllers\Admin\JadwalPelajaranController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\OverrideLogController;
use App\Http\Controllers\Admin\QrCodeGeneratorController;

// Controller Publik & QR Code
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\QrCodeController;

// Controller Role Spesifik
use App\Http\Controllers\KepalaSekolah\DashboardController as KepalaSekolahDashboardController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\AbsenController;
use App\Http\Controllers\Guru\RiwayatController;
use App\Http\Controllers\Piket\DashboardController as PiketDashboardController;
use App\Http\Controllers\Piket\LaporanHarianController;
use App\Http\Controllers\Piket\GantiPasswordController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Rute Publik (Bisa diakses tanpa login) ---
Route::redirect('/', '/login');
Route::get('/display/jadwal', [DisplayController::class, 'jadwalRealtime'])->name('display.jadwal');
Route::get('/display/qr-kios', [QrCodeController::class, 'showKios'])->name('display.qr-kios');
Route::get('/qr-code/generate', [QrCodeController::class, 'generateToken'])->name('qrcode.generate');


// --- Rute Autentikasi Umum (Hanya untuk yang sudah login) ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Redirector Dashboard Utama
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $role = $user->role;

        if ($role == 'admin') { return redirect()->route('admin.dashboard'); }
        if ($role == 'kepala_sekolah') { return redirect()->route('kepala-sekolah.dashboard'); }
        if ($role == 'guru') {
            $isPiket = \App\Models\JadwalPiket::where('user_id', $user->id)
                ->where('hari', now('Asia/Jakarta')->locale('id_ID')->isoFormat('dddd'))
                ->where('sesi', (now('Asia/Jakarta')->hour < 12 ? 'Pagi' : 'Siang'))
                ->exists();

            return $isPiket ? redirect()->route('piket.dashboard') : redirect()->route('guru.dashboard');
        }
        
        // Default jika role aneh / fallback
        auth()->logout(); 
        return redirect('/login');

    })->middleware(['verified'])->name('dashboard');
});


// ======================================================================
// === GRUP RUTE HANYA UNTUK ADMIN ===
// ======================================================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');

    // Rute Manajemen Pengguna
    Route::get('pengguna/import', [PenggunaController::class, 'showImportForm'])->name('pengguna.import.form');
    Route::post('pengguna/import', [PenggunaController::class, 'importExcel'])->name('pengguna.import.excel');
    Route::get('pengguna/arsip', [PenggunaController::class, 'arsip'])->name('pengguna.arsip');
    Route::post('pengguna/arsip/{id}/restore', [PenggunaController::class, 'restore'])->name('pengguna.restore');
    Route::delete('pengguna/arsip/{id}/force-delete', [PenggunaController::class, 'forceDelete'])->name('pengguna.forceDelete');
    Route::resource('pengguna', PenggunaController::class);
    Route::post('pengguna/{user}/reset-password', [PenggunaController::class, 'resetPassword'])->name('pengguna.resetPassword');
    
    // Rute Manajemen Jadwal
    Route::get('jadwal-piket', [JadwalPiketController::class, 'index'])->name('jadwal-piket.index');
    Route::get('jadwal-piket/edit/{hari}/{sesi}', [JadwalPiketController::class, 'edit'])->name('jadwal-piket.edit');
    Route::put('jadwal-piket/update/{hari}/{sesi}', [JadwalPiketController::class, 'update'])->name('jadwal-piket.update');
    
    Route::get('jadwal-pelajaran/import', [JadwalPelajaranController::class, 'showImportForm'])->name('jadwal-pelajaran.import.form');
    Route::post('jadwal-pelajaran/import', [JadwalPelajaranController::class, 'importExcel'])->name('jadwal-pelajaran.import.excel');
    Route::resource('jadwal-pelajaran', JadwalPelajaranController::class);
    
    // Rute Manajemen Sistem
    Route::resource('kalender-blok', KalenderBlokController::class);
    Route::resource('hari-libur', HariLiburController::class)->except(['edit', 'update']);

    // Rute untuk halaman generator QR Code
Route::get('qrcode-generator', [QrCodeGeneratorController::class, 'index'])->name('qrcode.generator.index');
Route::get('qrcode-generator/print', [QrCodeGeneratorController::class, 'print'])->name('qrcode.generator.print');

});

// ======================================================================
// === GRUP RUTE UNTUK ADMIN & KEPALA SEKOLAH (LAPORAN) ===
// ======================================================================
Route::middleware(['auth', 'role:admin,kepala_sekolah'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('laporan/realtime', [LaporanController::class, 'realtime'])->name('laporan.realtime');
    Route::get('laporan/bulanan', [LaporanController::class, 'bulanan'])->name('laporan.bulanan');
    Route::get('laporan/mingguan', [LaporanController::class, 'mingguan'])->name('laporan.mingguan');
    Route::get('laporan/individu', [LaporanController::class, 'individu'])->name('laporan.individu');
    Route::get('laporan/arsip', [LaporanController::class, 'arsip'])->name('laporan.arsip');
    Route::get('laporan/override-log', [OverrideLogController::class, 'index'])->name('laporan.override_log');
    Route::get('laporan/terlambat-harian', [LaporanController::class, 'laporanTerlambatHarian'])->name('laporan.terlambat.harian');
    
    // Rute Export Excel
    Route::get('laporan/export/bulanan', [LaporanController::class, 'exportBulanan'])->name('laporan.export.bulanan');
    Route::get('laporan/export/mingguan', [LaporanController::class, 'exportMingguan'])->name('laporan.export.mingguan');
    Route::get('laporan/export/individu', [LaporanController::class, 'exportIndividu'])->name('laporan.export.individu');
    Route::get('laporan/export/arsip', [LaporanController::class, 'exportArsip'])->name('laporan.export.arsip');
});

// ======================================================================
// === GRUP RUTE KHUSUS (KEPALA SEKOLAH, GURU, PIKET) ===
// ======================================================================
Route::middleware(['auth', 'role:kepala_sekolah'])->prefix('kepala-sekolah')->name('kepala-sekolah.')->group(function () {
    Route::get('/dashboard', [KepalaSekolahDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'role:guru'])->prefix('piket')->name('piket.')->group(function () {
    Route::get('/dashboard', [PiketDashboardController::class, 'index'])->name('dashboard');
    Route::post('/laporan-harian', [LaporanHarianController::class, 'store'])->name('laporan-harian.store');
    Route::get('/ganti-password', [GantiPasswordController::class, 'edit'])->name('ganti-password.edit');
    Route::put('/ganti-password', [GantiPasswordController::class, 'update'])->name('ganti-password.update');
});

Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    Route::post('/absen', [AbsenController::class, 'store'])->name('absen.store');
    Route::get('/riwayat-absensi', [RiwayatController::class, 'index'])->name('riwayat.index');
});


// Rute Autentikasi Bawaan Breeze (HARUS di paling bawah)
require __DIR__.'/auth.php';

