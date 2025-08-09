<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\SekolahController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\PresensiController;

Route::get('/', [PresensiController::class, 'index'])->name('presensi.index');
Route::post('/presensi/store', [PresensiController::class, 'store'])->name('presensi.store');
Route::get('/presensi/data', [PresensiController::class, 'getAttendanceData'])->name('presensi.data');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

   
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    
    Route::prefix('admin')->name('admin.')->group(function() {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        
        Route::resource('sekolah', SekolahController::class);

        Route::resource('siswa', SiswaController::class);
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::post('/laporan/izin', [LaporanController::class, 'catatIzin'])->name('laporan.izin');
        Route::post('/laporan/pdf', [LaporanController::class, 'cetakPdf'])->name('laporan.cetak_pdf');

    });
});

require __DIR__.'/auth.php';