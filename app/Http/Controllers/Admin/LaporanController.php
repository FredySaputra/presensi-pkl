<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Sekolah;
use App\Models\Siswa; // Pastikan model Siswa di-import
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        // ... (kode method index tidak berubah)
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $sekolahId = $request->input('sekolah_id');
        $sekolahs = Sekolah::orderBy('nama_sekolah')->get();
        $query = Presensi::with(['siswa.sekolah'])
                         ->whereBetween('tanggal', [$startDate, $endDate]);
        if ($sekolahId) {
            $query->whereHas('siswa', function ($q) use ($sekolahId) {
                $q->where('sekolah_id', $sekolahId);
            });
        }
        $presensis = $query->orderBy('tanggal', 'desc')->get();
        return view('admin.laporan.index', compact('presensis', 'startDate', 'endDate', 'sekolahs', 'sekolahId'));
    }

    public function catatIzin(Request $request)
    {
        // ... (kode method catatIzin tidak berubah)
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

    /**
     * Fungsi cetak PDF dengan logika PIVOT.
     */
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

        // 1. Ambil semua siswa unik yang memiliki presensi dalam rentang waktu dan sekolah yang dipilih.
        $querySiswa = Siswa::query()->whereHas('presensis', function($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal', [$startDate, $endDate]);
        });

        if ($sekolahId) {
            $querySiswa->where('sekolah_id', $sekolahId);
        }
        $students = $querySiswa->orderBy('nama_siswa')->get();

        // 2. Ambil semua data presensi yang relevan dalam satu query.
        $presensis = Presensi::whereIn('siswa_id', $students->pluck('id'))
                             ->whereBetween('tanggal', [$startDate, $endDate])
                             ->get();

        // 3. Ubah (pivot) data presensi menjadi format yang mudah diakses: [tanggal][siswa_id]
        $reportData = [];
        foreach ($presensis as $presensi) {
            $tanggal = $presensi->tanggal;
            $siswaId = $presensi->siswa_id;
            $reportData[$tanggal][$siswaId] = [
                'jam_masuk' => $presensi->jam_masuk,
                'jam_pulang' => $presensi->jam_pulang,
                'status' => $presensi->status,
            ];
        }

        // 4. Hasilkan daftar semua tanggal dalam rentang yang dipilih.
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        // 5. Kelompokkan siswa menjadi per 5 orang.
        $studentChunks = $students->chunk(5);

        // Catatan: Men-generate banyak PDF dalam satu request tidak praktis.
        // Kode ini akan men-generate PDF hanya untuk kelompok 5 siswa pertama.
        // Untuk implementasi penuh, diperlukan sistem antrian (queue).
        if ($studentChunks->isEmpty()) {
            return back()->with('error', 'Tidak ada data presensi untuk dicetak pada periode ini.');
        }
        
        $firstChunk = $studentChunks->first();
        $sekolahTerpilih = $sekolahId ? Sekolah::find($sekolahId) : null;

        $data = [
            'studentsChunk' => $firstChunk,
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
