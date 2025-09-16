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
use Carbon\CarbonPeriod;
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

        $status = 'Hadir';
            if ($request->jam_masuk && $request->jam_pulang) {
                $jamMasuk = Carbon::parse($request->jam_masuk);
                $jamPulang = Carbon::parse($request->jam_pulang);
                $durasiMenit = $jamPulang->diffInMinutes($jamMasuk);
                $limaJamDalamMenit = 5 * 60;

                if ($durasiMenit < $limaJamDalamMenit) {
                    $status = 'Kurang';
                }
            }

            Presensi::create([
                'siswa_id' => $request->siswa_id,
                'tanggal' => $request->tanggal,
                'jam_masuk' => $request->jam_masuk,
                'jam_pulang' => $request->jam_pulang,
                'status' => $status, // Gunakan status yang sudah dihitung
            ]);

            return redirect()->route('admin.laporan.index')->with('success', 'Presensi manual berhasil disimpan.');
    }

    public function cetakPdf(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $sekolahId = $request->input('sekolah_id');

        $siswaQuery = Siswa::query()->with('sekolah');
        if ($sekolahId) {
            $siswaQuery->where('sekolah_id', $sekolahId);
        }

        $semuaKelompokSiswa = $siswaQuery->orderBy('nama_siswa', 'asc')->get()->chunk(5);

        if ($semuaKelompokSiswa->isEmpty() || $semuaKelompokSiswa->first()->isEmpty()) {
            return back()->with('error', 'Tidak ada data siswa untuk dicetak pada filter yang dipilih.');
        }

        $period = CarbonPeriod::create($tanggalMulai, $tanggalSelesai);
        $siswaIds = $siswaQuery->pluck('id');

        $presensis = Presensi::whereIn('siswa_id', $siswaIds)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->get()
            ->groupBy(fn($date) => Carbon::parse($date->tanggal)->format('Y-m-d'));

        $sekolahs = Sekolah::whereIn('id', $siswaQuery->pluck('sekolah_id'))->get()->keyBy('id');
        $semuaSiswaMap = Siswa::whereIn('id', $siswaIds)->get()->keyBy('id');

        $tanggalData = collect();
        foreach ($period as $date) {
            $tanggalStr = $date->format('Y-m-d');
            $dataPresensiPerTanggal = collect();

            foreach ($siswaIds as $siswaId) {
                $presensiSiswa = optional($presensis->get($tanggalStr))->firstWhere('siswa_id', $siswaId);

                if ($presensiSiswa) {
                    $dataPresensiPerTanggal->push([
                        'siswa_id' => $siswaId,
                        'status' => $presensiSiswa->status,
                        'jam_masuk' => $presensiSiswa->jam_masuk,
                        'jam_pulang' => $presensiSiswa->jam_pulang,
                    ]);
                } else {
                    $siswa = $semuaSiswaMap->get($siswaId);
                    $sekolah = $sekolahs->get($siswa->sekolah_id);

                    // --- PERBAIKAN FINAL DI SINI ---
                    // Decode JSON, kemudian paksa hasilnya menjadi array jika gagal atau bukan array
                    $hariLiburSekolah = json_decode($sekolah->hari_libur ?? '[]', true);
                    $hariLiburSekolah = is_array($hariLiburSekolah) ? $hariLiburSekolah : [];
                    // --- AKHIR PERBAIKAN ---
                    
                    $dayOfWeek = $date->dayOfWeek;

                    if ($dayOfWeek == Carbon::SUNDAY || in_array($dayOfWeek, $hariLiburSekolah)) {
                        $dataPresensiPerTanggal->push(['siswa_id' => $siswaId, 'status' => 'LIBUR']);
                    } else {
                        $dataPresensiPerTanggal->push(['siswa_id' => $siswaId, 'status' => 'Alpa']);
                    }
                }
            }
            $tanggalData->put($tanggalStr, $dataPresensiPerTanggal);
        }

        $sekolah = $sekolahId ? Sekolah::find($sekolahId) : null;

        $pdf = PDF::loadView('admin.laporan.pdf', [
            'semuaKelompokSiswa' => $semuaKelompokSiswa,
            'tanggalData' => $tanggalData,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'sekolah' => $sekolah,
        ]);

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