<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanPresensiExport;
use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Sekolah;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    /**
     * Menampilkan halaman laporan dengan filter dan paginasi.
     */
    public function index(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::today()->toDateString());
        $tanggalSelesai = $request->input('tanggal_selesai', Carbon::today()->toDateString());
        $sekolahId = $request->input('sekolah_id');
        $search = $request->input('search');

        $presensis = Presensi::with(['siswa.sekolah'])
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->when($sekolahId, function ($query, $sekolahId) {
                return $query->whereHas('siswa', function ($q) use ($sekolahId) {
                    $q->where('sekolah_id', $sekolahId);
                });
            })
            ->when($search, function ($query, $search) {
                return $query->whereHas('siswa', function ($q) use ($search) {
                    $q->where('nama_siswa', 'like', "%{$search}%");
                });
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')
            ->paginate(15);

        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        $semuaSiswa = Siswa::with('sekolah')->orderBy('nama_siswa', 'asc')->get();

        return view('admin.laporan.index', compact('presensis', 'sekolahs', 'semuaSiswa', 'tanggalMulai', 'tanggalSelesai', 'sekolahId', 'search'));
    }

    /**
     * Mencatat izin untuk banyak siswa sekaligus.
     */
    public function catatIzin(Request $request)
    {
        $request->validate([
            'siswa_ids'   => 'required|array|min:1',
            'siswa_ids.*' => 'exists:siswas,id',
            'keterangan'  => 'required|string|max:255',
            'tanggal'     => 'required|date',
        ]);

        foreach ($request->siswa_ids as $siswaId) {
            // Hapus data lama jika ada (mencegah duplikat)
             Presensi::where('siswa_id', $siswaId)
                    ->whereDate('tanggal', $request->tanggal)
                    ->delete();

            // Buat data presensi baru dengan status Izin
            Presensi::create([
                'siswa_id'   => $siswaId,
                'tanggal'    => $request->tanggal,
                'status'     => 'Izin',
                'keterangan' => $request->keterangan,
            ]);
        }

        return redirect()->route('admin.laporan.index')->with('success', 'Status izin untuk siswa terpilih berhasil dicatat.');
    }
    
    /**
     * Mengambil daftar siswa yang belum presensi pada tanggal tertentu.
     */
    public function getSiswaTanpaPresensi(Request $request)
    {
        $tanggal = Carbon::parse($request->input('tanggal', today()));
        $siswaSudahPresensiIds = Presensi::whereDate('tanggal', $tanggal)->pluck('siswa_id');

        $siswaTersedia = Siswa::with('sekolah')
            ->whereNotIn('id', $siswaSudahPresensiIds)
            ->whereDate('mulai_pkl', '<=', $tanggal)
            ->whereDate('selesai_pkl', '>=', $tanggal)
            ->orderBy('nama_siswa', 'asc')
            ->get();
            
        return response()->json($siswaTersedia);
    }
    
    /**
     * Menyimpan data presensi manual untuk banyak siswa sekaligus.
     */
    public function storeManualPresence(Request $request)
    {
        $request->validate([
            'siswa_ids'   => 'required|array|min:1',
            'siswa_ids.*' => 'exists:siswas,id',
            'tanggal'     => 'required|date',
            'jam_masuk'   => 'nullable|date_format:H:i',
            'jam_pulang'  => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
        ]);

        $status = 'Hadir';
        if ($request->jam_masuk && $request->jam_pulang) {
            $jamMasuk = Carbon::parse($request->jam_masuk);
            $jamPulang = Carbon::parse($request->jam_pulang);
            $durasiMenit = $jamPulang->diffInMinutes($jamMasuk);
            if ($durasiMenit < (5 * 60)) {
                $status = 'Kurang';
            }
        }

        foreach ($request->siswa_ids as $siswaId) {
            Presensi::where('siswa_id', $siswaId)
                    ->whereDate('tanggal', $request->tanggal)
                    ->delete();

            Presensi::create([
                'siswa_id' => $siswaId,
                'tanggal' => $request->tanggal,
                'jam_masuk' => $request->jam_masuk,
                'jam_pulang' => $request->jam_pulang,
                'status' => $status,
            ]);
        }

        return redirect()->route('admin.laporan.index')->with('success', 'Presensi manual untuk siswa terpilih berhasil disimpan.');
    }

    /**
     * Membuat laporan presensi dalam format PDF.
     */
    public function cetakPdf(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $sekolahId = $request->input('sekolah_id');

        $query = Siswa::query();
        if ($sekolahId) {
            $query->where('sekolah_id', $sekolahId);
        }
        $semuaSiswa = $query->orderBy('nama_siswa', 'asc')->get();
        $semuaKelompokSiswa = $semuaSiswa->chunk(5);

        $sekolah = $sekolahId ? Sekolah::find($sekolahId) : null;
        
        $hariLiburSekolah = [];
        if ($sekolah && $sekolah->hari_libur) {
            $decoded = json_decode($sekolah->hari_libur, true);
            // Tambahkan pengecekan yang lebih kuat
            if (is_array($decoded)) {
                $hariLiburSekolah = $decoded;
            }
        }

        $tanggalRange = Carbon::parse($tanggalMulai)->toPeriod($tanggalSelesai);
        $dataPresensi = Presensi::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                                ->get()
                                ->groupBy('tanggal')
                                ->map(function ($items) {
                                    return $items->keyBy('siswa_id');
                                });

        $pdf = Pdf::loadView('admin.laporan.pdf', compact('semuaKelompokSiswa', 'tanggalRange', 'dataPresensi', 'sekolah', 'hariLiburSekolah'));
        return $pdf->stream('laporan-presensi.pdf');
    }

    /**
     * Mengekspor laporan presensi ke dalam format Excel.
     */
    public function cetakExcel(Request $request)
    {
        return Excel::download(new LaporanPresensiExport($request->all()), 'laporan-presensi.xlsx');
    }
}

