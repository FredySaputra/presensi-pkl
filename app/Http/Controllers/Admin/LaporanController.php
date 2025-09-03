<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanPresensiExport;
use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Sekolah;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        $sekolahId = $request->input('sekolah_id');
        $search = $request->input('search');
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::today()->toDateString());
        $tanggalSelesai = $request->input('tanggal_selesai', Carbon::today()->toDateString());

        $query = Presensi::with(['siswa.sekolah'])
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);

        if ($sekolahId) {
            $query->whereHas('siswa', function ($q) use ($sekolahId) {
                $q->where('sekolah_id', $sekolahId);
            });
        }

        if ($search) {
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama_siswa', 'like', "%{$search}%");
            });
        }

        $presensis = $query->orderBy('tanggal', 'desc')->orderBy('jam_masuk', 'desc')->paginate(15);

        $semuaSiswa = Siswa::with('sekolah')->orderBy('nama_siswa', 'asc')->get();

        return view('admin.laporan.index', compact('presensis', 'sekolahs', 'sekolahId', 'search', 'tanggalMulai', 'tanggalSelesai', 'semuaSiswa'));
    }

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

    public function catatIzin(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'siswa_id' => 'required|exists:siswas,id',
            'keterangan' => 'required|string|max:255',
        ]);

        $sudahAdaPresensi = Presensi::where('siswa_id', $request->siswa_id)
                                    ->whereDate('tanggal', $request->tanggal)
                                    ->exists();

        if ($sudahAdaPresensi) {
            return redirect()->route('admin.laporan.index')->with('error', 'Siswa sudah memiliki data presensi pada tanggal tersebut.');
        }

        Presensi::create([
            'siswa_id' => $request->siswa_id,
            'tanggal' => $request->tanggal,
            'status' => 'Izin',
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('admin.laporan.index')->with('success', 'Status izin berhasil dicatat.');
    }

    public function storeManualPresence(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
        ]);

        Presensi::where('siswa_id', $request->siswa_id)
                ->whereDate('tanggal', $request->tanggal)
                ->delete();

        Presensi::create([
            'siswa_id' => $request->siswa_id,
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk,
            'jam_pulang' => $request->jam_pulang,
            'status' => 'Hadir',
        ]);

        return redirect()->route('admin.laporan.index')->with('success', 'Presensi manual berhasil disimpan.');
    }

    public function cetakPdf(Request $request)
    {
        $sekolahId = $request->input('sekolah_id');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $query = Siswa::query()->with('sekolah');
        if ($sekolahId) {
            $query->where('sekolah_id', $sekolahId);
        }
        $siswas = $query->orderBy('nama_siswa', 'asc')->get();
        $sekolah = $sekolahId ? Sekolah::find($sekolahId) : null;

        $presensis = Presensi::whereIn('siswa_id', $siswas->pluck('id'))
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->get()
            ->groupBy('tanggal')
            ->map(function ($dailyPresensis) {
                return $dailyPresensis->keyBy('siswa_id');
            });

        $period = Carbon::parse($tanggalMulai)->toPeriod($tanggalSelesai);
        $dates = collect($period)->map(fn ($date) => $date->format('Y-m-d'));

        // Logika untuk menandai hari libur
        $pivotData = $dates->mapWithKeys(function ($tanggal) use ($siswas, $presensis) {
            $dailyData = $siswas->mapWithKeys(function ($siswa) use ($tanggal, $presensis) {
                $date = Carbon::parse($tanggal);
                $isSunday = $date->isSunday();
                // dayOfWeekIso: Senin=1, Selasa=2, ..., Minggu=7. Hari libur umum di Indonesia adalah Minggu (7)
                $isSpecificHoliday = $siswa->sekolah->hari_libur && $date->dayOfWeekIso == $siswa->sekolah->hari_libur;

                $presensiSiswa = $presensis->get($tanggal, collect())->get($siswa->id);
                
                $data = ['masuk' => '-', 'pulang' => '-'];
                if ($isSunday || $isSpecificHoliday) {
                    $data = ['masuk' => 'LIBUR', 'pulang' => 'LIBUR'];
                } elseif ($presensiSiswa) {
                    if ($presensiSiswa->status === 'Izin') {
                         $data = ['masuk' => 'IZIN', 'pulang' => '-'];
                    } else {
                        $data = [
                            'masuk' => $presensiSiswa->jam_masuk ? Carbon::parse($presensiSiswa->jam_masuk)->format('H:i') : '-',
                            'pulang' => $presensiSiswa->jam_pulang ? Carbon::parse($presensiSiswa->jam_pulang)->format('H:i') : '-',
                        ];
                    }
                }
                return [$siswa->id => $data];
            });
            return [$tanggal => $dailyData];
        });

        $siswaChunks = $siswas->chunk(5);
        $pdf = Pdf::loadView('admin.laporan.pdf', compact('siswaChunks', 'pivotData', 'dates', 'tanggalMulai', 'tanggalSelesai', 'sekolah'));
        return $pdf->stream('laporan-presensi.pdf');
    }

    public function cetakExcel(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $sekolahId = $request->input('sekolah_id');

        return Excel::download(new LaporanPresensiExport($tanggalMulai, $tanggalSelesai, $sekolahId), 'laporan-presensi.xlsx');
    }
}