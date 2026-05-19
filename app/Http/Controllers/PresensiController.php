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
        $data = $this->getAttendanceDataLogic();
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();

        return view('presensi.index', [
            'daftarHadir' => $data['daftarHadir'],
            'sekolahs'    => $sekolahs
        ]);
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
        $now = Carbon::now();

        // Batas waktu presensi
        $batasMasuk = $today->copy()->setTime(9, 0, 0);
        $batasPulang = $today->copy()->setTime(15, 0, 0);

        // Memastikan masa PKL siswa masih aktif
        if (!$today->between(Carbon::parse($siswa->mulai_pkl), Carbon::parse($siswa->selesai_pkl))) {
            return response()->json(['message' => 'Masa PKL belum dimulai atau sudah berakhir.', 'student' => $siswa, 'status_class' => 'warning'], 400);
        }

        // Kalkulasi Keterlambatan Masuk
        $isTelat = $now->greaterThan($batasMasuk);
        $menitTelat = $isTelat ? $batasMasuk->diffInMinutes($now) : null;
        $statusMasuk = $isTelat ? 'Telat' : 'Hadir';

        // Proses Presensi Masuk (Atau ambil data jika sudah ada)
        $presensi = Presensi::firstOrCreate(
            ['siswa_id' => $siswa->id, 'tanggal' => $today->toDateString()],
            [
                'jam_masuk' => $now->toTimeString(),
                'status' => $statusMasuk,
                'menit_telat' => $menitTelat
            ]
        );

        if ($presensi->wasRecentlyCreated) {
            // Respons untuk MASUK
            $statusClass = $isTelat ? 'warning' : 'success';
            $message = $isTelat ? "Presensi Berhasil. Anda Telat {$menitTelat} Menit!" : 'Presensi Masuk Berhasil. Selamat Datang!';
        } else {
            // Proses Presensi PULANG
            $presensi->jam_pulang = $now->toTimeString();

            // Kalkulasi Pulang Cepat
            if ($now->lessThan($batasPulang)) {
                $menitCepat = $now->diffInMinutes($batasPulang);
                $presensi->status = 'Pulang Cepat';
                $presensi->menit_pulang_cepat = $menitCepat;

                $message = "Jam Pulang Diperbarui! (Pulang Cepat {$menitCepat} Menit)";
                $statusClass = 'warning';
            } else {
                $message = 'Jam Pulang Diperbarui! (Tepat Waktu)';
                $statusClass = 'success';
            }

            $presensi->save();
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
     * Fungsi helper untuk mengelompokkan data presensi hari ini berdasarkan sekolah.
     */
    private function getAttendanceDataLogic()
    {
        $today = Carbon::today();

        // PROBLEM 4 FIX: Pindahkan filter tanggal ke SQL (whereHas) agar lebih cepat dan hemat RAM
        $presensisData = Presensi::with(['siswa.sekolah'])
            ->whereDate('tanggal', $today)
            ->whereNotNull('jam_masuk')
            ->whereHas('siswa', function($query) use ($today) {
                $query->where('mulai_pkl', '<=', $today->toDateString())
                      ->where('selesai_pkl', '>=', $today->toDateString());
            })
            ->orderBy('updated_at', 'desc')
            ->get();

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


}
