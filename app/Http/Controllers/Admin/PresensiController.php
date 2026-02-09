<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Carbon;

class PresensiController extends Controller
{
    /**
     * Menampilkan halaman utama presensi siswa.
     */
    public function index()
    {
        $data = $this->getAttendanceDataLogic();
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        
        return view('welcome', [
            'daftarHadir' => $data['daftarHadir'],
            'sekolahs' => $sekolahs
        ]);
    }

    /**
     * Memproses presensi via RFID Scanner.
     */
    public function store(Request $request)
    {
        $request->validate(['id_kartu' => 'required|string']);
        
        // Bersihkan input RFID dari spasi atau karakter newline
        $idKartu = preg_replace('/\s+/', '', $request->id_kartu);
        $siswa = Siswa::with('sekolah')->where('id_kartu', $idKartu)->first();

        if (!$siswa) {
            return response()->json(['message' => 'ID Kartu Tidak Terdaftar!', 'status_class' => 'danger'], 404);
        }

        return $this->processPresenceLogic($siswa);
    }

    /**
     * Memproses presensi manual (Pilih Nama dari Modal).
     */
    public function storeManual(Request $request)
    {
        $request->validate(['siswa_id' => 'required|exists:siswas,id']);
        $siswa = Siswa::with('sekolah')->find($request->siswa_id);

        return $this->processPresenceLogic($siswa);
    }

    /**
     * Logika inti pencatatan presensi (Masuk dan Pulang).
     */
    private function processPresenceLogic($siswa)
    {
        $today = Carbon::today();
        
        // Cek masa PKL siswa (Gunakan format Y-m-d untuk perbandingan)
        $mulai = Carbon::parse($siswa->mulai_pkl)->startOfDay();
        $selesai = Carbon::parse($siswa->selesai_pkl)->endOfDay();

        if (!$today->between($mulai, $selesai)) {
            return response()->json(['message' => 'Masa PKL belum dimulai atau sudah berakhir.', 'student' => $siswa, 'status_class' => 'warning'], 400);
        }

        $presensi = Presensi::where('siswa_id', $siswa->id)
                            ->whereDate('tanggal', $today)
                            ->first();
        $now = Carbon::now();

        if ($presensi) {
            // Jika sudah ada data masuk hari ini, update JAM PULANG
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
            // Jika belum ada data hari ini, catat sebagai JAM MASUK
            Presensi::create([
                'siswa_id' => $siswa->id,
                'tanggal' => $today->toDateString(),
                'jam_masuk' => $now->toTimeString(),
                'status' => 'Hadir',
            ]);
            $message = 'Presensi Masuk Berhasil. Selamat Datang!';
            $statusClass = 'success';
        }

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
     * API untuk memuat ulang daftar hadir secara real-time.
     */
    public function getAttendanceData()
    {
        $data = $this->getAttendanceDataLogic();
        return response()->json($data['daftarHadir']);
    }

    /**
     * Fungsi helper untuk mengelompokkan data presensi.
     */
    private function getAttendanceDataLogic()
    {
        $today = Carbon::today();
        $presensisData = Presensi::with(['siswa.sekolah'])
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->orderBy('updated_at', 'desc')
            ->get();

        $daftarHadir = [];
        foreach ($presensisData as $p) {
            if ($p->siswa && $p->siswa->sekolah) {
                $sid = $p->siswa->sekolah_id;
                if (!isset($daftarHadir[$sid])) {
                    $daftarHadir[$sid] = [
                        'nama_sekolah' => $p->siswa->sekolah->nama_sekolah,
                        'siswa' => []
                    ];
                }
                $daftarHadir[$sid]['siswa'][] = $p;
            }
        }

        return ['daftarHadir' => $daftarHadir];
    }
}