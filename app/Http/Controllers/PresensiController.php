<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Presensi;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PresensiController extends Controller
{
    public function index()
    {
        $presensiHariIni = $this->getTodayAttendance();
        return view('welcome', compact('presensiHariIni'));
    }

    public function store(Request $request)
    {
        $request->validate(['id_kartu' => 'required|string']);
        $siswa = Siswa::with('sekolah')->where('id_kartu', $request->id_kartu)->first();

        if (!$siswa) {
            return response()->json(['message' => 'ID Kartu Tidak Terdaftar!', 'status_class' => 'danger'], 404);
        }

        $today = Carbon::today();
        if (!$today->between(Carbon::parse($siswa->mulai_pkl), Carbon::parse($siswa->selesai_pkl))) {
             return response()->json(['message' => 'Masa PKL siswa ini sudah berakhir atau belum dimulai.', 'student' => $siswa, 'status_class' => 'warning'], 400);
        }

        $presensiHariIni = Presensi::where('siswa_id', $siswa->id)
                                   ->whereDate('tanggal', $today)
                                   ->first();
        $now = Carbon::now();
        $message = '';

        if ($presensiHariIni) {
           // PRESENSI PULANG (LOGIKA BARU)
                $presensiHariIni->jam_pulang = $now->toTimeString();

                // Hitung durasi
                $jamMasuk = Carbon::parse($presensiHariIni->jam_masuk);
                $jamPulang = Carbon::parse($presensiHariIni->jam_pulang);
                $durasiMenit = $jamPulang->diffInMinutes($jamMasuk);
                $limaJamDalamMenit = 5 * 60;

                // Tentukan status berdasarkan durasi
                if ($durasiMenit < $limaJamDalamMenit) {
                    $presensiHariIni->status = 'Kurang';
                } else {
                    $presensiHariIni->status = 'Hadir';
                }

                $presensiHariIni->save();

                return response()->json([
                    'message' => 'Presensi Pulang Berhasil. Selamat Jalan!',
                    'student' => $siswa,
                    'status_class' => 'success'
                ]);

        } else {
            // Jika belum ada presensi, buat data presensi masuk.
            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $today->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => 'Hadir',
            ]);
            $message = 'Presensi Masuk Berhasil. Selamat Datang!';
        }

        return response()->json([
            'message' => $message,
            'student' => $siswa,
            'status_class' => 'success',
            'attendees' => $this->getTodayAttendance(),
            'active_school_id' => $siswa->sekolah_id
        ]);
    }

    private function getTodayAttendance()
    {
        $presensi = Presensi::with(['siswa.sekolah'])
                       ->whereDate('tanggal', Carbon::today())
                       ->orderBy('updated_at', 'desc')
                       ->get();
        return $presensi->groupBy('siswa.sekolah_id');
    }

    public function getAttendanceData()
    {
        return response()->json($this->getTodayAttendance());
    }
}
