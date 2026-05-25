<?php

namespace App\Exports;

use App\Models\Presensi;
use App\Models\Sekolah;
use App\Models\Siswa;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LaporanPresensiExport implements FromView, ShouldAutoSize
{
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $sekolahId;
    protected $jenisCetak;

    public function __construct($tanggalMulai, $tanggalSelesai, $sekolahId, $jenisCetak = 'detail')
    {
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->sekolahId = $sekolahId;
        $this->jenisCetak = $jenisCetak;
    }

    public function view(): View
    {
        $siswaQuery = Siswa::query()->with('sekolah');
        if ($this->sekolahId) {
            $siswaQuery->where('sekolah_id', $this->sekolahId);
        }

        $siswas = $siswaQuery->orderBy('sekolah_id', 'asc')->orderBy('nama_siswa', 'asc')->get();
        $sekolah = $this->sekolahId ? Sekolah::find($this->sekolahId) : null;

        $start = Carbon::parse($this->tanggalMulai);
        $end = Carbon::parse($this->tanggalSelesai);

        $presensis = Presensi::whereIn('siswa_id', $siswas->pluck('id'))
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('tanggal')
            ->map(fn($items) => $items->keyBy('siswa_id'));

        $period = CarbonPeriod::create($start, $end);
        $dates = collect($period)->map(fn($date) => $date->format('Y-m-d'));

        // PERBAIKAN: Ambil data sejarah libur
        $hariLiburs = \App\Models\HariLibur::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->get();

        $pivotData = $dates->mapWithKeys(function ($tanggal) use ($siswas, $presensis, $hariLiburs) {
            $dailyData = $siswas->mapWithKeys(function ($siswa) use ($tanggal, $presensis, $hariLiburs) {
                $date = Carbon::parse($tanggal);

                $isSunday = $date->isSunday();

                // Cek Database Libur (Nasional atau Khusus Sekolah ini)
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

        if ($this->jenisCetak === 'rekap') {
            $datesByMonth = collect($dates)->groupBy(function($date) {
                return Carbon::parse($date)->format('Y-m');
            });

            return view('admin.laporan.excel_rekap', [
                'siswas' => $siswas,
                'pivotData' => $pivotData,
                'datesByMonth' => $datesByMonth,
                'sekolah' => $sekolah,
            ]);
        } else {
            return view('admin.laporan.excel', [
                'siswas' => $siswas,
                'pivotData' => $pivotData,
                'tanggalMulai' => $this->tanggalMulai,
                'tanggalSelesai' => $this->tanggalSelesai,
                'sekolah' => $sekolah,
            ]);
        }
    }
}
