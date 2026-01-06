<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PresensiController extends Controller
{
    /**
     * Menampilkan halaman utama presensi.
     */
    public function index()
    {
        // 1. Ambil data hadir hari ini
        $data = $this->getAttendanceDataLogic();
        
        // 2. AMBIL DATA SEKOLAH (Penting untuk menghilangkan error $sekolahs)
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        
        return view('welcome', [
            'daftarHadir' => $data['daftarHadir'],
            'sekolahs'    => $sekolahs // Kirim ke view
        ]);
    }

    /**
     * Memproses presensi dari RFID scanner.
     */
    public function store(Request $request)
    {
        $request->validate(['id_kartu' => 'required|string']);
        
        $idKartu = preg_replace('/\s+/', '', $request->id_kartu);
        $siswa = Siswa::with('sekolah')->where('id_kartu', $idKartu)->first();

        if (!$siswa) {
            return response()->json(['message' => 'ID Kartu Tidak Terdaftar!', 'status_class' => 'danger'], 404);
        }

        return $this->processAttendanceLogic($siswa);
    }

    /**
     * Memproses presensi manual (Pilih Nama dari Modal).
     */
    public function storeManual(Request $request)
    {
        $request->validate(['siswa_id' => 'required|exists:siswas,id']);
        $siswa = Siswa::with('sekolah')->find($request->siswa_id);

        return $this->processAttendanceLogic($siswa);
    }

    /**
     * Logika inti pencatatan (Masuk/Pulang) & Perhitungan Durasi.
     */
    private function processAttendanceLogic($siswa)
    {
        $today = today();
        
        // Cek masa aktif PKL
        if (!$today->between($siswa->mulai_pkl, $siswa->selesai_pkl)) {
            return response()->json(['message' => 'Masa PKL sudah berakhir atau belum dimulai.', 'student' => $siswa, 'status_class' => 'warning'], 400);
        }

        $presensiHariIni = Presensi::where('siswa_id', $siswa->id)
                                   ->whereDate('tanggal', $today)
                                   ->first();
        $now = now();

        if ($presensiHariIni) {
            // Update jam pulang
            $presensiHariIni->jam_pulang = $now->toTimeString();

            // Cek durasi (Hadir jika >= 5 jam)
            $jamMasuk = Carbon::parse($presensiHariIni->jam_masuk);
            $durasiMenit = $now->diffInMinutes($jamMasuk);
            
            if ($durasiMenit < 300) { // 5 jam = 300 menit
                $presensiHariIni->status = 'Kurang';
                $message = 'Jam Kerja Kurang dari 5 Jam!';
                $statusClass = 'warning';
            } else {
                $presensiHariIni->status = 'Hadir';
                $message = 'Jam Pulang Diperbarui!';
                $statusClass = 'success';
            }
            
            $presensiHariIni->save();
        } else {
            // Presensi Masuk
            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $today->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => 'Hadir',
            ]);
            $message = 'Presensi Masuk Berhasil!';
            $statusClass = 'success';
        }

        $presensisData = $this->getAttendanceDataLogic();
        return response()->json([
            'message' => $message,
            'student' => $siswa,
            'status_class' => $statusClass,
            'daftarHadir' => $presensisData['daftarHadir'],
            'sekolah_id' => $siswa->sekolah_id
        ]);
    }

    /**
     * API untuk mengambil siswa berdasarkan sekolah (Modal Manual).
     */
    public function getSiswaBySekolah(Sekolah $sekolah)
    {
        $today = today()->toDateString();
        $siswas = $sekolah->siswas()
            ->where('mulai_pkl', '<=', $today)
            ->where('selesai_pkl', '>=', $today)
            ->orderBy('nama_siswa', 'asc')
            ->get(['id', 'nama_siswa']);
            
        return response()->json($siswas);
    }

    /**
     * API untuk refresh otomatis.
     */
    public function getAttendanceData()
    {
        return response()->json($this->getAttendanceDataLogic());
    }
    
    private function getAttendanceDataLogic()
    {
        $today = today();
        $presensisData = Presensi::with(['siswa.sekolah'])
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->filter(function ($p) use ($today) {
                return $p->siswa && $today->between($p->siswa->mulai_pkl, $p->siswa->selesai_pkl);
            });

        $daftarHadir = [];
        foreach ($presensisData as $p) {
            $sid = $p->siswa->sekolah_id;
            if (!isset($daftarHadir[$sid])) {
                $daftarHadir[$sid] = [
                    'nama_sekolah' => $p->siswa->sekolah->nama_sekolah,
                    'siswa' => []
                ];
            }
            $daftarHadir[$sid]['siswa'][] = $p;
        }

        return ['daftarHadir' => $daftarHadir];
    }
}