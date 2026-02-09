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
     * Menampilkan halaman utama presensi siswa.
     */
    public function index()
    {
        // Mengambil data kehadiran hari ini
        $data = $this->getAttendanceDataLogic();
        
        // MENGAMBIL DATA SEKOLAH untuk dropdown modal manual
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        
        return view('welcome', [
            'daftarHadir' => $data['daftarHadir'],
            'sekolahs'    => $sekolahs
        ]);
    }

    /**
     * Memproses presensi melalui scan RFID (Otomatis).
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
     * Logika inti untuk pencatatan presensi (Masuk dan Pulang).
     */
    private function processAttendanceLogic($siswa)
    {
        $today = Carbon::today();
        
        // Memastikan masa PKL siswa masih aktif
        if (!$today->between(Carbon::parse($siswa->mulai_pkl), Carbon::parse($siswa->selesai_pkl))) {
            return response()->json(['message' => 'Masa PKL belum dimulai atau sudah berakhir.', 'student' => $siswa, 'status_class' => 'warning'], 400);
        }

        $presensi = Presensi::where('siswa_id', $siswa->id)
                            ->whereDate('tanggal', $today)
                            ->first();
        $now = Carbon::now();

        if ($presensi) {
            // Update JAM PULANG
            $presensi->jam_pulang = $now->toTimeString();

            // Logika Status: Hadir jika durasi >= 5 jam (300 menit)
            $jamMasuk = Carbon::parse($presensi->jam_masuk);
            $durasiMenit = $now->diffInMinutes($jamMasuk);
            
            if ($durasiMenit >= 300) { 
                $presensi->status = 'Hadir';
                $message = 'Jam Pulang Diperbarui! (Tepat Waktu)';
                $statusClass = 'success';
            } else {
                $presensi->status = 'Kurang';
                $message = 'Jam Kerja Kurang dari 5 Jam!';
                $statusClass = 'warning';
            }
            
            $presensi->save();
        } else {
            // Presensi MASUK
            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $today->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => 'Hadir', 
            ]);
            $message = 'Presensi Masuk Berhasil. Selamat Datang!';
            $statusClass = 'success';
        }

        // Ambil data terbaru untuk dikirim kembali ke UI
        $data = $this->getAttendanceDataLogic();
        return response()->json([
            'message' => $message,
            'student' => $siswa,
            'status_class' => $statusClass,
            'daftarHadir' => $data['daftarHadir'],
            'sekolah_id' => $siswa->sekolah_id
        ]);
    }

    /**
     * Mengambil daftar siswa aktif per sekolah untuk dropdown manual.
     */
    public function getSiswaBySekolah(Sekolah $sekolah)
    {
        $today = Carbon::today()->toDateString();
        $siswas = $sekolah->siswas()
            ->where('mulai_pkl', '<=', $today)
            ->where('selesai_pkl', '>=', $today)
            ->orderBy('nama_siswa', 'asc')
            ->get(['id', 'nama_siswa']);
            
        return response()->json($siswas);
    }

    /**
     * API untuk memuat ulang daftar hadir secara real-time via AJAX.
     */
    public function getAttendanceData()
    {
        $data = $this->getAttendanceDataLogic();
        return response()->json($data['daftarHadir']);
    }

    /**
     * Fungsi helper untuk mengelompokkan data presensi hari ini berdasarkan sekolah.
     */
    private function getAttendanceDataLogic()
    {
        $today = Carbon::today();
        
        $presensisData = Presensi::with(['siswa.sekolah'])
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->filter(function($p) use ($today) {
                // Hanya tampilkan siswa yang masa PKL-nya aktif hari ini
                return $p->siswa && $today->between(Carbon::parse($p->siswa->mulai_pkl), Carbon::parse($p->siswa->selesai_pkl));
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