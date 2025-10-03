<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Import Semua Controller
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\JadwalPiketController;
use App\Http\Controllers\Admin\KalenderBlokController;
use App\Http\Controllers\Admin\JadwalPelajaranController;
use App\Http\Controllers\Admin\HariLiburController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\KepalaSekolah\DashboardController as KepalaSekolahDashboardController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\AbsenController;
use App\Http\Controllers\Piket\DashboardController as PiketDashboardController;
use App\Http\Controllers\Piket\LaporanHarianController;
use App\Http\Controllers\Piket\GantiPasswordController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rute Publik
Route::redirect('/', '/login');
Route::get('/display/jadwal', [DisplayController::class, 'jadwalRealtime'])->name('display.jadwal');
Route::get('/display/qr-kios', [\App\Http\Controllers\QrCodeController::class, 'showKios'])->name('display.qr-kios');
Route::get('/qr-code/generate', [\App\Http\Controllers\QrCodeController::class, 'generateToken'])->name('qrcode.generate');


// Rute Autentikasi Umum
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Redirector Dashboard Utama
    Route::get('/dashboard', function () {
        $role = auth()->user()->role;
        if ($role == 'admin') { return redirect()->route('admin.dashboard'); }
        elseif ($role == 'kepala_sekolah') { return redirect()->route('kepala-sekolah.dashboard'); }
        elseif ($role == 'piket') { return redirect()->route('piket.dashboard'); }
        elseif ($role == 'guru') { return redirect()->route('guru.dashboard'); }
        else { auth()->logout(); return redirect('/login'); } // Default
    })->middleware(['verified'])->name('dashboard');
});


// ======================================================================
// === GRUP RUTE HANYA UNTUK ADMIN ===
// ======================================================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');

    // Manajemen Data & Sistem Inti
    Route::resource('pengguna', PenggunaController::class);
    Route::post('pengguna/{user}/reset-password', [PenggunaController::class, 'resetPassword'])->name('pengguna.resetPassword');
    
    Route::get('jadwal-piket', [JadwalPiketController::class, 'index'])->name('jadwal-piket.index');
    Route::get('jadwal-piket/edit/{hari}/{sesi}', [JadwalPiketController::class, 'edit'])->name('jadwal-piket.edit');
    Route::put('jadwal-piket/update/{hari}/{sesi}', [JadwalPiketController::class, 'update'])->name('jadwal-piket.update');
    
    Route::resource('kalender-blok', KalenderBlokController::class);
    Route::resource('jadwal-pelajaran', JadwalPelajaranController::class);
    Route::resource('hari-libur', HariLiburController::class)->except(['edit', 'update']);

    // Rute Import
    Route::get('jadwal-pelajaran/import', [JadwalPelajaranController::class, 'showImportForm'])->name('jadwal-pelajaran.import.form');
    Route::post('jadwal-pelajaran/import', [JadwalPelajaranController::class, 'importExcel'])->name('jadwal-pelajaran.import.excel');
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
    
    // Rute Export Excel
    Route::get('laporan/export/bulanan', [LaporanController::class, 'exportBulanan'])->name('laporan.export.bulanan');
    Route::get('laporan/export/mingguan', [LaporanController::class, 'exportMingguan'])->name('laporan.export.mingguan');
    Route::get('laporan/export/individu', [LaporanController::class, 'exportIndividu'])->name('laporan.export.individu');
    Route::get('laporan/export/arsip', [LaporanController::class, 'exportArsip'])->name('laporan.export.arsip');
});

// ======================================================================
// === GRUP RUTE HANYA UNTUK KEPALA SEKOLAH ===
// ======================================================================
Route::middleware(['auth', 'role:kepala_sekolah'])->prefix('kepala-sekolah')->name('kepala-sekolah.')->group(function () {
    Route::get('/dashboard', [KepalaSekolahDashboardController::class, 'index'])->name('dashboard');
});

// ======================================================================
// === GRUP RUTE HANYA UNTUK GURU PIKET ===
// ======================================================================
Route::middleware(['auth', 'role:piket'])->prefix('piket')->name('piket.')->group(function () {
    Route::get('/dashboard', [PiketDashboardController::class, 'index'])->name('dashboard');
    Route::post('/laporan-harian', [LaporanHarianController::class, 'store'])->name('laporan-harian.store');
    Route::get('/ganti-password', [GantiPasswordController::class, 'edit'])->name('ganti-password.edit');
    Route::put('/ganti-password', [GantiPasswordController::class, 'update'])->name('ganti-password.update');
});

// ======================================================================
// === GRUP RUTE HANYA UNTUK GURU UMUM ===
// ======================================================================
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    Route::post('/absen', [AbsenController::class, 'store'])->name('absen.store');
});


// Rute Autentikasi Bawaan Breeze
require __DIR__.'/auth.php';
