<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
     public function index()
    {
        $siswas = Siswa::with('sekolah')->get();
        $presensis = Presensi::whereDate('tanggal', Carbon::today())->get()
                             ->keyBy('siswa_id'); 

        return view('admin.laporan.index', compact('siswas', 'presensis'));
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
}
