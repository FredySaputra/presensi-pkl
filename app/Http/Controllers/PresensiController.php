<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PresensiController extends Controller
{
  public function index()
    {
        return view('welcome');
    }

    /**
     * Menyimpan data presensi yang masuk dari RFID.
     */
         public function store(Request $request)
    {
        $request->validate(['id_kartu' => 'required|string']);

        // 1. Cari siswa berdasarkan ID Kartu
        $siswa = Siswa::with('sekolah')->where('id_kartu', 'like', $request->id_kartu)->first();

        // Jika siswa tidak ditemukan
        if (!$siswa) {
            return response()->json([
                'message' => 'ID Kartu Tidak Terdaftar!',
                'status_class' => 'danger'
            ], 404);
        }

        // 2. Cek apakah siswa masih dalam masa PKL
        // PERBAIKAN: Menggunakan helper today()
        $today = today();
        if (!$today->between(Carbon::parse($siswa->mulai_pkl), Carbon::parse($siswa->selesai_pkl))) {
             return response()->json([
                'message' => 'Masa PKL siswa ini sudah berakhir atau belum dimulai.',
                'student' => $siswa,
                'status_class' => 'warning'
            ], 400);
        }

        // 3. Cek apakah hari ini sudah ada catatan presensi untuk siswa ini
        $presensiHariIni = Presensi::where('siswa_id', $siswa->id)
                                   ->whereDate('tanggal', $today)
                                   ->first();

        // PERBAIKAN: Menggunakan helper now()
        $now = now();

        // 4. Logika Presensi
        if ($presensiHariIni) {
            // Jika sudah ada, berarti ini PRESENSI PULANG
            if ($presensiHariIni->jam_pulang) {
                return response()->json([
                    'message' => 'Anda sudah melakukan presensi pulang hari ini.',
                    'student' => $siswa,
                    'status_class' => 'warning'
                ], 400);
            }
            $presensiHariIni->jam_pulang = $now->toTimeString();
            $presensiHariIni->save();

            return response()->json([
                'message' => 'Presensi Pulang Berhasil. Selamat Jalan!',
                'student' => $siswa,
                'status_class' => 'success'
            ]);

        } else {
            // Jika belum ada, berarti ini PRESENSI MASUK
            $jamMasuk = Carbon::createFromTimeString('09:00:00');
            $keteranganTelat = $now->isAfter($jamMasuk) ? 'Telat' : 'Tepat Waktu';

            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $today->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => 'Hadir',
            ]);

            return response()->json([
                'message' => 'Presensi Masuk Berhasil. Selamat Datang!',
                'student' => $siswa,
                'status_class' => 'success'
            ]);
        }
    }
    }



