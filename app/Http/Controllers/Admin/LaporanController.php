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
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\SyncToLiveService;

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

        $semuaSiswa = Siswa::with('sekolah')
            ->where('mulai_pkl', '<=', $tanggalSelesai)
            ->where('selesai_pkl', '>=', $tanggalMulai)
            ->orderBy('nama_siswa', 'asc')
            ->get();

        return view('admin.laporan.index', compact('presensis', 'sekolahs', 'semuaSiswa', 'tanggalMulai', 'tanggalSelesai', 'sekolahId', 'search'));
    }

    /**
     * API untuk AJAX: Mengambil siswa yang belum presensi dan aktif pada tanggal tertentu.
     * Mengembalikan juga jumlah izin WA yang sudah digunakan selama masa aktif PKL.
     */
    public function getSiswaTanpaPresensi(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tanggal_akhir' => 'nullable|date'
        ]);

        $tanggalMulai = $request->input('tanggal');
        $tanggalAkhir = $request->input('tanggal_akhir') ?? $tanggalMulai;

        $bulanIni = Carbon::parse($tanggalMulai)->month;
        $tahunIni = Carbon::parse($tanggalMulai)->year;

        // Ambil ID siswa yang sudah punya presensi pada tanggal mulai
        $siswaSudahPresensiIds = Presensi::whereDate('tanggal', $tanggalMulai)
            ->pluck('siswa_id')
            ->toArray();

        // Kita ambil siswa yang aktif pada rentang tanggal tersebut dan belum presensi di tanggal mulai
        $siswaTersedia = Siswa::with('sekolah')
            ->whereNotIn('id', $siswaSudahPresensiIds)
            ->where('mulai_pkl', '<=', $tanggalAkhir)
            ->where('selesai_pkl', '>=', $tanggalMulai)
            ->orderBy('nama_siswa', 'asc')
            ->get()
            ->map(function ($siswa) use ($bulanIni, $tahunIni) {
                $jumlahIzinWA = Presensi::where('siswa_id', $siswa->id)
                    ->where('status', 'Izin')
                    ->where('metode_izin', 'WA')
                    ->count();

                $siswa->jumlah_izin_wa = $jumlahIzinWA;
                return $siswa;
            });

        return response()->json($siswaTersedia);
    }

    /**
     * Mencatat izin untuk banyak siswa sekaligus (Massal via Checkbox).
     */
    public function catatIzin(Request $request, SyncToLiveService $syncService)
    {
        $request->validate([
            'siswa_ids'     => 'required|array|min:1',
            'siswa_ids.*'   => 'exists:siswas,id',
            'keterangan'    => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_mulai',
            'metode_izin'   => 'required|in:WA,Surat',
        ]);

        $tglMulai = Carbon::parse($request->tanggal_mulai);
        $tglAkhir = Carbon::parse($request->tanggal_akhir);
        
        $errorMessages = [];
        $successCount = 0;
        $syncedPresensis = collect();

        foreach ($request->siswa_ids as $siswaId) {
            $siswa = Siswa::find($siswaId);
            $currentDate = $tglMulai->copy();

            while ($currentDate->lte($tglAkhir)) {
                $dateString = $currentDate->toDateString();
                $bulan = $currentDate->month;
                $tahun = $currentDate->year;

                // Analisis Bug: Pastikan tanggal ini masih dalam rentang PKL siswa
                if ($dateString < $siswa->mulai_pkl || $dateString > $siswa->selesai_pkl) {
                    $currentDate->addDay();
                    continue;
                }

                // Cek apakah hari ini adalah hari libur (Minggu atau Libur di Database)
                $isSunday = $currentDate->isSunday();
                $isLiburDatabase = \App\Models\HariLibur::where('tanggal', $dateString)
                    ->where(function($query) use ($siswa) {
                        $query->whereNull('sekolah_id')
                              ->orWhere('sekolah_id', $siswa->sekolah_id);
                    })->exists();

                if ($isSunday || $isLiburDatabase) {
                    $currentDate->addDay();
                    continue;
                }

                if ($request->metode_izin == 'WA') {
                    $jumlahIzinWA = Presensi::where('siswa_id', $siswaId)
                        ->where('status', 'Izin')
                        ->where('metode_izin', 'WA')
                        ->count();

                    if ($jumlahIzinWA >= 3) {
                        $errorMessages[] = "Siswa {$siswa->nama_siswa} pada tanggal {$dateString} tidak bisa dicatat (Batas 3x WA per masa aktif PKL tercapai).";
                        $currentDate->addDay();
                        continue;
                    }
                }

                $presensi = Presensi::updateOrCreate(
                    ['siswa_id' => $siswaId, 'tanggal' => $dateString],
                    [
                        'status' => 'Izin',
                        'keterangan' => $request->keterangan,
                        'jam_masuk' => null,
                        'jam_pulang' => null,
                        'metode_izin' => $request->metode_izin
                    ]
                );
                
                $syncedPresensis->push($presensi);
                $successCount++;
                $currentDate->addDay();
            }
        }

        // Trigger Sync
        if ($syncedPresensis->isNotEmpty()) {
            $syncService->syncAttendance($syncedPresensis);
        }

        if (count($errorMessages) > 0) {
            $msg = $successCount > 0 ? 'Beberapa data berhasil disimpan.' : 'Gagal menyimpan data.';
            return redirect()->route('admin.laporan.index')
                             ->with('success', $msg)
                             ->with('error_list', $errorMessages);
        }

        return redirect()->route('admin.laporan.index')->with('success', 'Status izin berhasil dicatat untuk semua siswa terpilih.');
    }

    /**
     * Mencatat presensi manual untuk banyak siswa sekaligus (Massal via Checkbox).
     */
    public function storeManualPresence(Request $request, SyncToLiveService $syncService)
    {
        $request->validate([
            'siswa_ids'   => 'required|array|min:1',
            'siswa_ids.*' => 'exists:siswas,id',
            'tanggal'     => 'required|date',
            'jam_masuk'   => 'nullable|date_format:H:i',
            'jam_pulang'  => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
        ]);

        $tanggal = $request->input('tanggal');


        $validSiswaIds = Siswa::whereIn('id', $request->siswa_ids)
            ->where('mulai_pkl', '<=', $tanggal)
            ->where('selesai_pkl', '>=', $tanggal)
            ->pluck('id')
            ->toArray();

        if (empty($validSiswaIds)) {
            return redirect()->route('admin.laporan.index')
                ->with('error', 'Semua siswa yang dipilih tidak sedang dalam masa aktif PKL pada tanggal tersebut.');
        }

        $status = 'Hadir';
        if ($request->jam_masuk && $request->jam_pulang) {
            $jamMasuk = Carbon::parse($request->jam_masuk);
            $jamPulang = Carbon::parse($request->jam_pulang);
            if ($jamPulang->diffInMinutes($jamMasuk) < 300) {
                $status = 'Kurang';
            }
        }

        $syncedPresensis = collect();
        foreach ($validSiswaIds as $siswaId) {
            $presensi = Presensi::updateOrCreate(
                ['siswa_id' => $siswaId, 'tanggal' => $request->tanggal],
                [
                    'jam_masuk' => $request->jam_masuk,
                    'jam_pulang' => $request->jam_pulang,
                    'status' => $status,
                    'keterangan' => 'Input Manual'
                ]
            );
            $syncedPresensis->push($presensi);
        }

        // Trigger Sync
        if ($syncedPresensis->isNotEmpty()) {
            $syncService->syncAttendance($syncedPresensis);
        }

        $jumlahTersimpan = count($validSiswaIds);
        return redirect()->route('admin.laporan.index')
            ->with('success', "Presensi manual berhasil disimpan untuk $jumlahTersimpan siswa yang aktif.");
    }

    /**
     * Membuat laporan presensi dalam format PDF (Bisa Detail atau Rekap Umum).
     */
    public function cetakPdf(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $sekolahId = $request->input('sekolah_id');
        $jenisCetak = $request->input('jenis_cetak', 'detail');

        $siswaQuery = Siswa::query()->with('sekolah');
        if ($sekolahId) {
            $siswaQuery->where('sekolah_id', $sekolahId);
        }

        $siswas = $siswaQuery->orderBy('sekolah_id', 'asc')->orderBy('nama_siswa', 'asc')->get();
        $sekolah = $sekolahId ? Sekolah::find($sekolahId) : null;

        if ($siswas->isEmpty()) {
            return back()->with('error', 'Tidak ada data siswa untuk dicetak.');
        }

        $start = Carbon::parse($tanggalMulai);
        $end = Carbon::parse($tanggalSelesai);

        $presensis = Presensi::whereIn('siswa_id', $siswas->pluck('id'))
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('tanggal')
            ->map(fn($items) => $items->keyBy('siswa_id'));

        $period = CarbonPeriod::create($start, $end);
        $dates = collect($period)->map(fn($date) => $date->format('Y-m-d'));

        $hariLiburs = \App\Models\HariLibur::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->get();

        $pivotData = $dates->mapWithKeys(function ($tanggal) use ($siswas, $presensis, $hariLiburs) {
            $dailyData = $siswas->mapWithKeys(function ($siswa) use ($tanggal, $presensis, $hariLiburs) {
                $date = Carbon::parse($tanggal);

                $isSunday = $date->isSunday();

                $isLiburDatabase = $hariLiburs->where('tanggal', $tanggal)
                                              ->filter(function ($libur) use ($siswa) {
                                                  return is_null($libur->sekolah_id) || $libur->sekolah_id == $siswa->sekolah_id;
                                              })->isNotEmpty();

                $presensiSiswa = $presensis->get($tanggal, collect())->get($siswa->id);
                $data = ['masuk' => '-', 'pulang' => '-', 'status' => 'Alpa'];

                if ($isSunday || $isLiburDatabase) {
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

        if ($jenisCetak === 'rekap') {
            $datesByMonth = collect($dates)->groupBy(function($date) {
                return Carbon::parse($date)->format('Y-m');
            });

            $pdf = Pdf::loadView('admin.laporan.pdf_rekap', [
                'siswas' => $siswas,
                'pivotData' => $pivotData,
                'datesByMonth' => $datesByMonth,
                'sekolah' => $sekolah,
            ])->setPaper('a4', 'landscape');

            return $pdf->stream('rekap-presensi.pdf');

        } else {
            $semuaKelompokSiswa = $siswas->chunk(5);
            $pdf = Pdf::loadView('admin.laporan.pdf', [
                'semuaKelompokSiswa' => $semuaKelompokSiswa,
                'pivotData' => $pivotData,
                'tanggalMulai' => $start->toDateString(),
                'tanggalSelesai' => $end->toDateString(),
                'dates' => $dates,
                'sekolah' => $sekolah,
            ])->setPaper('a4', 'landscape');

            return $pdf->stream('laporan-presensi-detail.pdf');
        }
    }

    public function cetakExcel(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $sekolahId = $request->input('sekolah_id');
        $jenisCetak = $request->input('jenis_cetak', 'detail');

        return Excel::download(
            new LaporanPresensiExport($tanggalMulai, $tanggalSelesai, $sekolahId, $jenisCetak),
            'laporan-presensi-' . $jenisCetak . '.xlsx'
        );
    }
}
