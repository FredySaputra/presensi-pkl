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
        $today = Carbon::today()->toDateString();

        $sekolahs = Sekolah::whereHas('siswas', function ($query) use ($today) {
            $query->where('mulai_pkl', '<=', $today)
                  ->where('selesai_pkl', '>=', $today)
                  ->whereDoesntHave('presensis', function($p) use ($today) {
                      $p->whereDate('tanggal', $today);
                  });
        })->orderBy('nama_sekolah', 'asc')->get();

        return view('presensi.index', [
            'daftarHadir' => $data['daftarHadir'],
            'sekolahs'    => $sekolahs
        ]);
    }

    /**
     * Memproses presensi MASUK (Dari Form Sebelah Kiri).
     */
    public function storeManual(Request $request)
    {
        $request->validate(['siswa_id' => 'required|exists:siswas,id']);
        $siswa = Siswa::with('sekolah')->find($request->siswa_id);

        $today = Carbon::today();
        $now = Carbon::now();
        $batasMasuk = $today->copy()->setTime(9, 0, 0);

        if (!$today->between(Carbon::parse($siswa->mulai_pkl), Carbon::parse($siswa->selesai_pkl))) {
            return response()->json(['message' => 'Masa PKL belum dimulai atau sudah berakhir.', 'student' => $siswa, 'status_class' => 'warning'], 400);
        }

        $cekPresensi = Presensi::where('siswa_id', $siswa->id)->where('tanggal', $today->toDateString())->first();
        if ($cekPresensi) {
            return response()->json(['message' => 'Anda sudah melakukan presensi hari ini!', 'status_class' => 'warning'], 400);
        }

        $isTelat = $now->greaterThan($batasMasuk);
        $menitTelat = $isTelat ? $batasMasuk->diffInMinutes($now) : null;
        $statusMasuk = $isTelat ? 'Telat' : 'Hadir';

        Presensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => $today->toDateString(),
            'jam_masuk' => $now->toTimeString(),
            'status' => $statusMasuk,
            'menit_telat' => $menitTelat
        ]);

        $statusClass = $isTelat ? 'warning' : 'success';

        $teksTelat = $this->formatWaktuMenit($menitTelat);
        $message = $isTelat ? "Presensi Berhasil. Anda Telat {$teksTelat}!" : 'Presensi Masuk Berhasil. Selamat Datang!';

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
     * Memproses presensi PULANG (Dari Tombol Panel Kanan).
     */
    public function storePulang(Request $request)
    {
        $request->validate(['siswa_id' => 'required|exists:siswas,id']);
        $siswa = Siswa::with('sekolah')->find($request->siswa_id);

        $today = Carbon::today();
        $now = Carbon::now();
        $batasPulang = $today->copy()->setTime(15, 0, 0);

        $presensi = Presensi::where('siswa_id', $siswa->id)->where('tanggal', $today->toDateString())->first();

        if (!$presensi) {
            return response()->json(['message' => 'Anda belum presensi masuk!', 'status_class' => 'danger'], 400);
        }

        if ($presensi->jam_pulang) {
            return response()->json(['message' => 'Anda sudah melakukan presensi pulang!', 'status_class' => 'warning'], 400);
        }

        $presensi->jam_pulang = $now->toTimeString();

        if ($now->lessThan($batasPulang)) {
            $menitCepat = $now->diffInMinutes($batasPulang);
            $presensi->status = 'Pulang Cepat';
            $presensi->menit_pulang_cepat = $menitCepat;

            $teksCepat = $this->formatWaktuMenit($menitCepat);
            $message = "Presensi Pulang Berhasil! (Pulang Cepat {$teksCepat})";
            $statusClass = 'warning';
        } else {
            $message = 'Presensi Pulang Berhasil! (Tepat Waktu)';
            $statusClass = 'success';
        }

        $presensi->save();

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
     * Mengambil daftar siswa aktif per sekolah YANG BELUM PRESENSI untuk dropdown manual.
     */
    public function getSiswaBySekolah(Sekolah $sekolah)
    {
        $today = Carbon::today()->toDateString();
        $siswas = $sekolah->siswas()
            ->where('mulai_pkl', '<=', $today)
            ->where('selesai_pkl', '>=', $today)
            ->whereDoesntHave('presensis', function($p) use ($today) {
                $p->whereDate('tanggal', $today);
            })
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

    public function getDaftarSekolahAktif()
    {
        $today = Carbon::today()->toDateString();

        $sekolahs = Sekolah::whereHas('siswas', function ($query) use ($today) {
            $query->where('mulai_pkl', '<=', $today)
                  ->where('selesai_pkl', '>=', $today)
                  ->whereDoesntHave('presensis', function($p) use ($today) {
                      $p->whereDate('tanggal', $today);
                  });
        })->orderBy('nama_sekolah', 'asc')->get();

        return response()->json($sekolahs);
    }

    /**
     * Memperbarui data presensi pulang dengan waktu SAAT INI (Real-time).
     */
    public function updatePulangManual(Request $request)
    {
        $request->validate([
            'presensi_id' => 'required|exists:presensis,id',
        ]);

        $presensi = Presensi::with('siswa')->find($request->presensi_id);
        $today = Carbon::today();
        $now = Carbon::now();

        $batasPulang = $today->copy()->setTime(15, 0, 0);

        $presensi->jam_pulang = $now->toTimeString();

        if ($now->lessThan($batasPulang)) {
            $menitCepat = $now->diffInMinutes($batasPulang);
            $presensi->status = 'Pulang Cepat';
            $presensi->menit_pulang_cepat = $menitCepat;

            $teksCepat = $this->formatWaktuMenit($menitCepat);
            $message = "Koreksi Berhasil! (Pulang Cepat {$teksCepat})";
            $statusClass = 'warning';
        } else {
            if ($presensi->menit_telat > 0) {
                $presensi->status = 'Telat';
            } else {
                $presensi->status = 'Hadir';
            }

            $presensi->menit_pulang_cepat = null;

            $message = "Koreksi Berhasil! (Pulang Tepat Waktu)";
            $statusClass = 'success';
        }

        $presensi->save();

        $data = $this->getAttendanceDataLogic();
        return response()->json([
            'message' => $message,
            'status_class' => $statusClass,
            'daftarHadir' => $data['daftarHadir'],
            'sekolah_id' => $presensi->siswa->sekolah_id
        ]);
    }

    /**
     * Fungsi helper untuk mengubah menit menjadi format Jam & Menit
     */
    private function formatWaktuMenit($totalMenit)
    {
        if (!$totalMenit || $totalMenit <= 0) return '0 Menit';

        $jam = floor($totalMenit / 60);
        $menit = $totalMenit % 60;

        $teks = [];
        if ($jam > 0) $teks[] = "{$jam} Jam";
        if ($menit > 0) $teks[] = "{$menit} Menit";

        return implode(' ', $teks);
    }
}
