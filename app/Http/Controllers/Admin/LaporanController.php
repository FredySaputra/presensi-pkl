<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        // Ambil input filter dari request
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $sekolahId = $request->input('sekolah_id');

        // Ambil semua data sekolah untuk dropdown filter
        $sekolahs = Sekolah::orderBy('nama_sekolah')->get();

        // Bangun query presensi secara dinamis
        $query = Presensi::with(['siswa.sekolah'])
                         ->whereBetween('tanggal', [$startDate, $endDate]);

        // Terapkan filter sekolah jika ada yang dipilih
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
        // ... (kode tidak berubah)
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

    public function cetakPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sekolah_id' => 'nullable|exists:sekolahs,id' // Validasi filter sekolah
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sekolahId = $request->input('sekolah_id');

        // Bangun query yang sama seperti di method index
        $query = Presensi::with(['siswa.sekolah'])
                         ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($sekolahId) {
            $query->whereHas('siswa', function ($q) use ($sekolahId) {
                $q->where('sekolah_id', $sekolahId);
            });
        }

        $presensis = $query->orderBy('tanggal', 'asc')->get();
        $sekolahTerpilih = $sekolahId ? Sekolah::find($sekolahId) : null;

        $data = [
            'presensis' => $presensis,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sekolahTerpilih' => $sekolahTerpilih,
        ];

        $pdf = Pdf::loadView('admin.laporan.pdf', $data);
        return $pdf->stream('laporan-presensi.pdf');
    }
}
