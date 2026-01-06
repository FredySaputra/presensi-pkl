<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SekolahController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PresensiController as AdminPresensiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- HALAMAN DEPAN PRESENSI (PUBLIC) ---
// Halaman utama tempat siswa melakukan scan atau pilih nama
Route::get('/', [PresensiController::class, 'index'])->name('presensi.index');

// Proses presensi masuk/pulang via RFID
Route::post('/presensi/store', [PresensiController::class, 'store'])->name('presensi.store');

// Proses presensi masuk/pulang via Pilih Nama (Manual)
Route::post('/presensi/manual', [PresensiController::class, 'storeManual'])->name('presensi.manual');

// API untuk memperbarui daftar hadir di sebelah kanan secara real-time (AJAX)
Route::get('/presensi/data', [PresensiController::class, 'getAttendanceData'])->name('presensi.data');

// API untuk mendapatkan daftar siswa aktif berdasarkan sekolah (untuk Modal Manual)
Route::get('/presensi/siswa-by-sekolah/{sekolah}', [PresensiController::class, 'getSiswaBySekolah']);


// --- ADMIN PANEL (MANAJEMEN & LAPORAN) ---
Route::prefix('admin')->name('admin.')->group(function() {
    
    // Dashboard: Statistik Kehadiran Hari Ini
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Manajemen Master Data Sekolah
    Route::resource('sekolah', SekolahController::class);
    
    // Manajemen Data Siswa (Create, Edit, Delete)
    Route::resource('siswa', SiswaController::class);
    
    // Fitur Tambahan Siswa: Arsip (PKL Selesai) & Riwayat Per Siswa
    Route::get('/siswa-arsip', [SiswaController::class, 'arsip'])->name('siswa.arsip');
    Route::get('/siswa/{siswa}/riwayat', [SiswaController::class, 'riwayat'])->name('siswa.riwayat');

    // Laporan Presensi & Fitur Massal (Admin)
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    
    // Mencatat Izin Massal (Checkbox)
    Route::post('/laporan/izin', [LaporanController::class, 'catatIzin'])->name('laporan.izin');
    
    // Mencatat Presensi Manual Massal (Checkbox)
    Route::post('/laporan/manual', [LaporanController::class, 'storeManualPresence'])->name('laporan.manual');
    
    // API untuk mendapatkan siswa yang belum hadir pada tanggal tertentu (untuk Modal Izin)
    Route::get('/laporan/get-siswa-tanpa-presensi', [LaporanController::class, 'getSiswaTanpaPresensi'])->name('laporan.getSiswa');
    
    // Fitur Ekspor Laporan
    Route::post('/laporan/pdf', [LaporanController::class, 'cetakPdf'])->name('laporan.cetak_pdf');
    Route::post('/laporan/excel', [LaporanController::class, 'cetakExcel'])->name('laporan.cetak_excel');

    // Fitur Edit Data Presensi Tertentu (Jika ada kesalahan input)
    Route::resource('presensi', AdminPresensiController::class)->only(['edit', 'update']);
});

// Load rute autentikasi bawaan Laravel (jika Anda menggunakan Laravel Breeze/Fortify)
require __DIR__.'/auth.php';