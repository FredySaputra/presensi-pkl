<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Menghitung total siswa PKL yang masih aktif
        $totalSiswaAktif = Siswa::where('selesai_pkl', '>=', $today->toDateString())->count();

        // 2. Menghitung jumlah yang hadir hari ini
        $jumlahHadir = Presensi::whereDate('tanggal', $today)
                               ->where('status', 'Hadir')
                               ->count();

        // 3. Menghitung jumlah yang izin hari ini
        $jumlahIzin = Presensi::whereDate('tanggal', $today)
                              ->where('status', 'Izin')
                              ->count();

        // 4. Menghitung jumlah yang alpa (total siswa aktif - (hadir + izin))
        $jumlahAlpa = $totalSiswaAktif - ($jumlahHadir + $jumlahIzin);

        // 5. Mengambil daftar siswa yang terlambat hari ini
        $batasMasuk = Carbon::createFromTimeString('09:00:59');
        $siswaTerlambat = Presensi::with('siswa.sekolah')
                                  ->whereDate('tanggal', $today)
                                  ->where('status', 'Hadir')
                                  ->whereTime('jam_masuk', '>', $batasMasuk)
                                  ->orderBy('jam_masuk', 'asc')
                                  ->get();

        // Kirim semua data ke view
        return view('admin.dashboard', compact(
            'totalSiswaAktif',
            'jumlahHadir',
            'jumlahIzin',
            'jumlahAlpa',
            'siswaTerlambat'
        ));
    }
}
