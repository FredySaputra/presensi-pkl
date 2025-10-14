<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PresensiController extends Controller
{
    public function index()
    {
        $data = $this->getAttendanceDataLogic();
        return view('welcome', ['daftarHadir' => $data['daftarHadir']]);
    }

    public function store(Request $request)
    {
        $request->validate(['id_kartu' => 'required|string']);
        $idKartu = preg_replace('/\s+/', '', $request->id_kartu);
        $siswa = Siswa::with('sekolah')->where('id_kartu', $idKartu)->first();

        if (!$siswa) {
            return response()->json(['message' => 'ID Kartu Tidak Terdaftar!', 'status_class' => 'danger'], 404);
        }

        $today = today();
        if (!$today->between($siswa->mulai_pkl, $siswa->selesai_pkl)) {
            return response()->json(['message' => 'Masa PKL siswa ini sudah berakhir atau belum dimulai.', 'student' => $siswa, 'status_class' => 'warning'], 400);
        }

        $presensiHariIni = Presensi::where('siswa_id', $siswa->id)
                                   ->whereDate('tanggal', $today)
                                   ->first();
        $now = now();

        if ($presensiHariIni) {
            $presensiHariIni->jam_pulang = $now->toTimeString();

            $jamMasuk = Carbon::parse($presensiHariIni->jam_masuk);
            $jamPulang = Carbon::parse($presensiHariIni->jam_pulang);
            $durasiMenit = $jamPulang->diffInMinutes($jamMasuk);
            $limaJamDalamMenit = 5 * 60;

            if ($durasiMenit < $limaJamDalamMenit) {
                $presensiHariIni->status = 'Kurang';
            } else {
                $presensiHariIni->status = 'Hadir';
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
            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $today->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => 'Hadir',
            ]);
            
            $presensisData = $this->getAttendanceDataLogic();
            return response()->json([
                'message' => 'Presensi Masuk Berhasil. Selamat Datang!',
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
    
    private function getAttendanceDataLogic()
    {
        $today = today();
        $semuaPresensiHariIni = Presensi::with(['siswa.sekolah'])
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->orderBy('jam_masuk', 'desc')
            ->get();
        
        $presensisData = $semuaPresensiHariIni->filter(function ($presensi) use ($today) {
            return $presensi->siswa && $today->between($presensi->siswa->mulai_pkl, $presensi->siswa->selesai_pkl);
        });

        $daftarHadir = [];
        foreach ($presensisData as $presensi) {
            if ($presensi->siswa && $presensi->siswa->sekolah) {
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
        }
        return ['daftarHadir' => $daftarHadir];
    }
}
