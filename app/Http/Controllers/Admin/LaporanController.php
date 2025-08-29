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
        // Ambil semua input filter
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $sekolahId = $request->input('sekolah_id');
        $search = $request->input('search'); // Input pencarian baru

        $sekolahs = Sekolah::orderBy('nama_sekolah')->get();

        // Bangun query presensi
        $query = Presensi::with(['siswa.sekolah'])
                         ->whereBetween('tanggal', [$startDate, $endDate]);

        // Terapkan filter sekolah
        if ($sekolahId) {
            $query->whereHas('siswa', function ($q) use ($sekolahId) {
                $q->where('sekolah_id', $sekolahId);
            });
        }

        // Terapkan filter pencarian berdasarkan nama siswa
        if ($search) {
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama_siswa', 'like', '%' . $search . '%');
            });
        }

        // Gunakan paginasi
        $presensis = $query->orderBy('tanggal', 'desc')->paginate(15);

        // Ambil data untuk modal izin
        $siswaHadirHariIniIds = Presensi::whereDate('tanggal', Carbon::today())->pluck('siswa_id');
        $siswaBelumHadir = Siswa::with('sekolah')->whereNotIn('id', $siswaHadirHariIniIds)
                                ->orderBy('nama_siswa', 'asc')
                                ->get();
        $allSiswa = Siswa::with('sekolah')->orderBy('nama_siswa', 'asc')->get();

        return view('admin.laporan.index', compact('presensis', 'startDate', 'endDate', 'sekolahs', 'sekolahId', 'siswaBelumHadir', 'allSiswa', 'search'));
    }

    public function catatIzin(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'keterangan' => 'required|string|max:255',
        ]);
        $sudahAdaPresensi = Presensi::where('siswa_id', $request->siswa_id)
                                    ->whereDate('tanggal', Carbon::today())
                                    ->exists();
        if ($sudahAdaPresensi) {
            return redirect()->route('admin.laporan.index')->with('error', 'Siswa sudah memiliki data presensi hari ini.');
        }
        Presensi::create([
            'siswa_id' => $request->siswa_id,
            'tanggal' => Carbon::today()->toDateString(),
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
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
        ]);

        Presensi::updateOrCreate(
            [
                'siswa_id' => $request->siswa_id,
                'tanggal' => $request->tanggal,
            ],
            [
                'jam_masuk' => $request->jam_masuk,
                'jam_pulang' => $request->jam_pulang,
                'status' => 'Hadir',
                'keterangan' => 'Diinput manual oleh admin',
            ]
        );

        return redirect()->route('admin.laporan.index')->with('success', 'Presensi manual berhasil disimpan.');
    }

    public function cetakExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sekolah_id' => 'nullable|exists:sekolahs,id'
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sekolahId = $request->input('sekolah_id');
        
        $fileName = 'laporan-presensi-' . $startDate . '-sd-' . $endDate . '.xlsx';

        return Excel::download(new LaporanPresensiExport($startDate, $endDate, $sekolahId), $fileName);
    }

    public function cetakPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sekolah_id' => 'nullable|exists:sekolahs,id'
        ]);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sekolahId = $request->input('sekolah_id');
        $querySiswa = Siswa::query()->whereHas('presensis', function($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal', [$startDate, $endDate]);
        });
        if ($sekolahId) {
            $querySiswa->where('sekolah_id', $sekolahId);
        }
        $students = $querySiswa->orderBy('nama_siswa')->get();
        $presensis = Presensi::whereIn('siswa_id', $students->pluck('id'))
                             ->whereBetween('tanggal', [$startDate, $endDate])
                             ->get();
        $reportData = [];
        foreach ($presensis as $presensi) {
            $reportData[$presensi->tanggal][$presensi->siswa_id] = [
                'jam_masuk' => $presensi->jam_masuk,
                'jam_pulang' => $presensi->jam_pulang,
                'status' => $presensi->status,
            ];
        }
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        $studentChunks = $students->chunk(5);
        if ($studentChunks->isEmpty()) {
            return back()->with('error', 'Tidak ada data presensi untuk dicetak pada periode ini.');
        }
        $sekolahTerpilih = $sekolahId ? Sekolah::find($sekolahId) : null;
        $data = [
            'studentChunks' => $studentChunks,
            'dates' => $dates,
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sekolahTerpilih' => $sekolahTerpilih,
        ];
        $pdf = Pdf::loadView('admin.laporan.pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('laporan-presensi.pdf');
    }
}
