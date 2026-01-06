<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\Sekolah;
use Carbon\Carbon as CarbonCarbon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Carbon;

class PresensiController extends Controller
{
    public function index()
    {
        $today = today();
        
        // Mengambil data presensi hari ini untuk siswa yang aktif
        $presensisData = Presensi::with(['siswa.sekolah'])
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereHas('siswa', function ($query) use ($today) {
                $query->whereDate('mulai_pkl', '<=', $today)->whereDate('selesai_pkl', '>=', $today);
            })
            ->orderBy('jam_masuk', 'desc')
            ->get();

        // Mengelompokkan data berdasarkan sekolah untuk tampilan tab
        $daftarHadir = [];
        foreach ($presensisData as $presensi) {
            $sekolahId = $presensi->siswa->sekolah->id;
            $namaSekolah = $presensi->siswa->sekolah->nama_sekolah;

            if (!isset($daftarHadir[$sekolahId])) {
                $daftarHadir[$sekolahId] = [
                    'nama_sekolah' => $namaSekolah,
                    'siswa' => []
                ];
            }
            $daftarHadir[$sekolahId]['siswa'][] = $presensi;
        }

        return view('welcome', compact('daftarHadir'));
    }

    public function store(Request $request)
    {
        $request->validate(['id_kartu' => 'required|string']);

        $siswa = Siswa::with('sekolah')->where('id_kartu', $request->id_kartu)->first();

        if (!$siswa) {
            return response()->json(['message' => 'ID Kartu Tidak Terdaftar!', 'status_class' => 'danger'], 404);
        }

        $today = today();
        if (!$today->between(CarbonCarbon::parse($siswa->mulai_pkl), CarbonCarbon::parse($siswa->selesai_pkl))) {
            return response()->json(['message' => 'Masa PKL siswa ini sudah berakhir atau belum dimulai.', 'student' => $siswa, 'status_class' => 'warning'], 400);
        }

        $presensiHariIni = Presensi::where('siswa_id', $siswa->id)
                                   ->whereDate('tanggal', $today)
                                   ->first();

        $now = now();

        if ($presensiHariIni) {
            // --- LOGIKA PRESENSI PULANG ---
            $presensiHariIni->jam_pulang = $now->toTimeString();

            // Hitung durasi dalam menit
            $jamMasuk = CarbonCarbon::parse($presensiHariIni->jam_masuk);
            $jamPulang = CarbonCarbon::parse($presensiHariIni->jam_pulang);
            $durasiMenit = $jamPulang->diffInMinutes($jamMasuk);
            $limaJamDalamMenit = 5 * 60;

            // Tentukan status berdasarkan durasi
            if ($durasiMenit < $limaJamDalamMenit) {
                $presensiHariIni->status = 'Kurang'; // Status "Kurang" yang benar
            } else {
                $presensiHariIni->status = 'Hadir'; // Kembali ke "Hadir" jika >= 5 jam
            }
            
            $presensiHariIni->save();

            $presensisData = $this->getAttendanceDataLogic();
            return response()->json([
                'message' => 'Jam Pulang Diperbarui!',
                'student' => $siswa,
                'status_class' => 'success',
                'daftarHadir' => $presensisData['daftarHadir'],
                'sekolah_id' => $siswa->sekolah_id
            ]);

        } else {
            // --- LOGIKA PRESENSI MASUK ---
            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $today->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => 'Hadir', // Status awal saat masuk adalah "Hadir"
            ]);
            
            $presensisData = $this->getAttendanceDataLogic();
            return response()->json([
                'message' => 'Presensi MasUK Berhasil. Selamat Datang!',
                'student' => $siswa,
                'status_class' => 'success',
                'daftarHadir' => $presensisData['daftarHadir'],
                'sekolah_id' => $siswa->sekolah_id
            ]);
        }
    }

    public function getAttendanceData()
    {
        $data = $this->getAttendanceDataLogic();
        return response()->json($data);
    }
    
    // Fungsi helper untuk mengambil data daftar hadir
    private function getAttendanceDataLogic()
    {
        $today = today();
        $presensisData = Presensi::with(['siswa.sekolah'])
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereHas('siswa', function ($query) use ($today) {
                $query->whereDate('mulai_pkl', '<=', $today)->whereDate('selesai_pkl', '>=', $today);
            })
            ->orderBy('jam_masuk', 'desc')
            ->get();

        $daftarHadir = [];
        foreach ($presensisData as $presensi) {
            $sekolahId = $presensi->siswa->sekolah->id;
            $namaSekolah = $presensi->siswa->sekolah->nama_sekolah;

            if (!isset($daftarHadir[$sekolahId])) {
                $daftarHadir[$sekolahId] = [
                    'nama_sekolah' => $namaSekolah,
                    'siswa' => []
                ];
            }
            $daftarHadir[$sekolahId]['siswa'][] = $presensi;
        }

        return ['daftarHadir' => $daftarHadir];
    }
}
