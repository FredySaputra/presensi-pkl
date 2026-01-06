<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanPresensiExport;
use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Sekolah;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Carbon;
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

        $query = Presensi::query()->with(['siswa.sekolah'])
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);

        if ($sekolahId) {
            $query->whereHas('siswa', function ($q) use ($sekolahId) {
                $q->where('sekolah_id', $sekolahId);
            });
        }

        if ($search) {
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama_siswa', 'like', '%' . $search . '%');
            });
        }

        $presensis = $query->orderBy('tanggal', 'desc')->orderBy('jam_masuk', 'desc')->paginate(15);
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        
        // Diperlukan untuk daftar checkbox di modal
        $semuaSiswa = Siswa::with('sekolah')->orderBy('nama_siswa', 'asc')->get();

        return view('admin.laporan.index', compact('presensis', 'sekolahs', 'semuaSiswa', 'tanggalMulai', 'tanggalSelesai', 'sekolahId', 'search'));
    }

    /**
     * API untuk AJAX: Mengambil siswa yang belum presensi dan aktif pada tanggal tertentu.
     */
    public function getSiswaTanpaPresensi(Request $request)
    {
        $request->validate(['tanggal' => 'required|date']);
        $tanggal = $request->input('tanggal');

        $siswaSudahPresensiIds = Presensi::whereDate('tanggal', $tanggal)
            ->pluck('siswa_id')
            ->toArray();

        $siswaTersedia = Siswa::with('sekolah')
            ->whereNotIn('id', $siswaSudahPresensiIds)
            ->where('mulai_pkl', '<=', $tanggal)
            ->where('selesai_pkl', '>=', $tanggal)
            ->orderBy('nama_siswa', 'asc')
            ->get();
            
        return response()->json($siswaTersedia);
    }

    /**
     * Mencatat izin untuk banyak siswa sekaligus (Massal via Checkbox).
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
            Presensi::updateOrCreate(
                ['siswa_id' => $siswaId, 'tanggal' => $request->tanggal],
                [
                    'status' => 'Izin', 
                    'keterangan' => $request->keterangan, 
                    'jam_masuk' => null, 
                    'jam_pulang' => null
                ]
            );
        }

        return redirect()->route('admin.laporan.index')->with('success', 'Status izin berhasil dicatat untuk siswa terpilih.');
    }
    
    /**
     * Mencatat presensi manual untuk banyak siswa sekaligus (Massal via Checkbox).
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
            // Hadir jika durasi >= 5 jam (300 menit)
            if ($jamPulang->diffInMinutes($jamMasuk) < 300) {
                $status = 'Kurang';
            }
        }

        foreach ($request->siswa_ids as $siswaId) {
            Presensi::updateOrCreate(
                ['siswa_id' => $siswaId, 'tanggal' => $request->tanggal],
                [
                    'jam_masuk' => $request->jam_masuk, 
                    'jam_pulang' => $request->jam_pulang, 
                    'status' => $status,
                    'keterangan' => 'Input Manual'
                ]
            );
        }

        return redirect()->route('admin.laporan.index')->with('success', 'Presensi manual berhasil disimpan.');
    }

    /**
     * Membuat laporan presensi dalam format PDF (Versi Pivot/Matriks).
     */
    public function cetakPdf(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $sekolahId = $request->input('sekolah_id');

        $siswaQuery = Siswa::query()->with('sekolah');
        if ($sekolahId) {
            $siswaQuery->where('sekolah_id', $sekolahId);
        }

        $siswas = $siswaQuery->orderBy('nama_siswa', 'asc')->get();
        $sekolah = $sekolahId ? Sekolah::find($sekolahId) : null;

        if ($siswas->isEmpty()) {
            return back()->with('error', 'Tidak ada data siswa untuk dicetak.');
        }

        $presensis = Presensi::whereIn('siswa_id', $siswas->pluck('id'))
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->get()
            ->groupBy('tanggal')
            ->map(fn($items) => $items->keyBy('siswa_id'));

        $period = CarbonPeriod::create($tanggalMulai, $tanggalSelesai);
        $dates = collect($period)->map(fn($date) => $date->format('Y-m-d'));

        // Membangun data pivot untuk ditampilkan di tabel PDF
        $pivotData = $dates->mapWithKeys(function ($tanggal) use ($siswas, $presensis) {
            $dailyData = $siswas->mapWithKeys(function ($siswa) use ($tanggal, $presensis) {
                $date = Carbon::parse($tanggal);
                $isSunday = $date->isSunday();
                
                // Cek Hari Libur Sekolah (Menangani tipe data integer atau array secara aman)
                $hariLiburSekolah = $siswa->sekolah->hari_libur;
                $isSpecificHoliday = false;
                if ($hariLiburSekolah) {
                    $isSpecificHoliday = $date->dayOfWeekIso == $hariLiburSekolah;
                }

                $presensiSiswa = $presensis->get($tanggal, collect())->get($siswa->id);
                
                $data = ['masuk' => '-', 'pulang' => '-', 'status' => 'Alpa'];
                
                if ($isSunday || $isSpecificHoliday) {
                    $data = ['masuk' => 'LIBUR', 'pulang' => 'LIBUR', 'status' => 'LIBUR'];
                } elseif ($presensiSiswa) {
                    $data = [
                        'masuk' => $presensiSiswa->jam_masuk ? Carbon::parse($presensiSiswa->jam_masuk)->format('H:i') : ($presensiSiswa->status == 'Izin' ? 'IZIN' : '-'),
                        'pulang' => $presensiSiswa->jam_pulang ? Carbon::parse($presensiSiswa->jam_pulang)->format('H:i') : '-',
                        'status' => $presensiSiswa->status
                    ];
                }
                
                return [$siswa->id => $data];
            });
            return [$tanggal => $dailyData];
        });

        $semuaKelompokSiswa = $siswas->chunk(5);

        $pdf = Pdf::loadView('admin.laporan.pdf', [
            'semuaKelompokSiswa' => $semuaKelompokSiswa,
            'pivotData' => $pivotData,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'dates' => $dates,
            'sekolah' => $sekolah,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-presensi.pdf');
    }

    public function cetakExcel(Request $request)
    {
        return Excel::download(new LaporanPresensiExport($request->input('tanggal_mulai'), $request->input('tanggal_selesai'), $request->input('sekolah_id')), 'laporan-presensi.xlsx');
    }
}