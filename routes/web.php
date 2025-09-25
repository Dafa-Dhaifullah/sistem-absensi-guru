<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Import Semua Controller Kita
|--------------------------------------------------------------------------
| Kita kumpulkan semua 'use' statement di atas agar rapi.
*/

// Controller Admin (Tahap 3, 4, 6)
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DashboardAdminController; 
use App\Http\Controllers\Admin\DataGuruController;
use App\Http\Controllers\Admin\AkunAdminController;
use App\Http\Controllers\Admin\AkunPiketController;
use App\Http\Controllers\Admin\JadwalPiketController;
use App\Http\Controllers\Admin\KalenderBlokController;
use App\Http\Controllers\Admin\JadwalPelajaranController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\DisplayController;


// Controller Piket (Tahap 5)
use App\Http\Controllers\Piket\DashboardController;
use App\Http\Controllers\Piket\LaporanHarianController;
use App\Http\Controllers\Piket\GantiPasswordController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Rute Halaman Depan (Publik)
Route::redirect('/', '/login');

// 2. Rute Dashboard (Breeze Asli) - KITA MODIFIKASI
// Rute ini akan menjadi "Gerbang Otomatis" yang mengarahkan
// user ke dashboard yang benar berdasarkan role mereka.
 // Pastikan ini ada di atas

// ... Rute lain ...

Route::get('/dashboard', function () {

$role = auth()->user()->role;

    if ($role == 'admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($role == 'piket') {
        return redirect()->route('piket.dashboard'); // <-- INI YANG TERJADI
    } else {
        return view('dashboard');
    }

})->middleware(['auth', 'verified'])->name('dashboard');


// 3. Rute Profil (Bawaan Breeze, biarkan saja)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ======================================================================
// === 4. GRUP RUTE ADMIN ===
// ======================================================================
// Semua rute di grup ini HANYA bisa diakses oleh 'admin'
// URL-nya akan berawalan /admin/...
// Nama rutenya akan berawalan admin.... (misal: admin.data-guru.index)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard Admin
   // Dashboard Admin
Route::get('/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');

    // Rute untuk menampilkan form import
Route::get('data-guru/import', [DataGuruController::class, 'showImportForm'])->name('data-guru.import.form');
// Rute untuk memproses file
Route::post('data-guru/import', [DataGuruController::class, 'importExcel'])->name('data-guru.import.excel');

    // Tahap 3: CRUD Sederhana
    Route::resource('data-guru', DataGuruController::class);
    Route::resource('akun-admin', AkunAdminController::class);
    Route::resource('akun-piket', AkunPiketController::class);
    Route::post('akun-piket/{user}/reset-password', [AkunPiketController::class, 'resetPassword'])->name('akun-piket.resetPassword');
    // Tahap 4: CRUD Kompleks (Otak Sistem)
    // (Kita daftarkan rutenya sekarang, walau controllernya belum dibuat)
  // TAMBAHKAN 3 BARIS INI
Route::get('jadwal-piket', [JadwalPiketController::class, 'index'])->name('jadwal-piket.index');
Route::get('jadwal-piket/edit/{hari}/{sesi}', [JadwalPiketController::class, 'edit'])->name('jadwal-piket.edit');
Route::put('jadwal-piket/update/{hari}/{sesi}', [JadwalPiketController::class, 'update'])->name('jadwal-piket.update');
    Route::resource('kalender-blok', KalenderBlokController::class);
    Route::get('jadwal-pelajaran/import', [JadwalPelajaranController::class, 'showImportForm'])->name('jadwal-pelajaran.import.form');
Route::post('jadwal-pelajaran/import', [JadwalPelajaranController::class, 'importExcel'])->name('jadwal-pelajaran.import.excel');
    Route::resource('jadwal-pelajaran', JadwalPelajaranController::class);
    // (Nanti di sini kita tambahkan rute untuk Import Excel)

    // Tahap 6: Laporan
    // (Kita daftarkan rutenya sekarang)
    Route::get('laporan/bulanan', [LaporanController::class, 'bulanan'])->name('laporan.bulanan');
    Route::get('laporan/mingguan', [LaporanController::class, 'mingguan'])->name('laporan.mingguan');
    Route::get('laporan/individu', [LaporanController::class, 'individu'])->name('laporan.individu');
    Route::get('laporan/arsip', [LaporanController::class, 'arsip'])->name('laporan.arsip');
    
    // Rute untuk Export Excel
    Route::get('laporan/export/bulanan', [LaporanController::class, 'exportBulanan'])->name('laporan.export.bulanan');
    Route::get('laporan/export/mingguan', [LaporanController::class, 'exportMingguan'])->name('laporan.export.mingguan');
    Route::get('laporan/export/individu', [LaporanController::class, 'exportIndividu'])->name('laporan.export.individu');

    Route::get('jadwal-realtime', [LaporanController::class, 'realtime'])->name('laporan.realtime');


});

// ======================================================================
// === 5. GRUP RUTE GURU PIKET ===
// ======================================================================
// Semua rute di grup ini HANYA bisa diakses oleh 'piket'
// URL-nya akan berawalan /piket/...
Route::middleware(['auth', 'role:piket'])->prefix('piket')->name('piket.')->group(function () {
    
    // Tahap 5: Dasbor Piket (Halaman Utama)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Tahap 5: Aksi Simpan Laporan Harian
    Route::post('/laporan-harian', [LaporanHarianController::class, 'store'])->name('laporan-harian.store');

    // Fitur Tambahan: Ganti Password
    Route::get('/ganti-password', [GantiPasswordController::class, 'edit'])->name('ganti-password.edit');
    Route::put('/ganti-password', [GantiPasswordController::class, 'update'])->name('ganti-password.update');

});
Route::get('/display/jadwal', [DisplayController::class, 'jadwalRealtime'])->name('display.jadwal');

// 6. Rute Autentikasi (Bawaan Breeze, HARUS di paling bawah)
require __DIR__.'/auth.php';