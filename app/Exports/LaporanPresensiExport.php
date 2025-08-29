<?php

namespace App\Exports;

use App\Models\Presensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class LaporanPresensiExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $sekolahId;

    public function __construct($startDate, $endDate, $sekolahId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->sekolahId = $sekolahId;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = Presensi::with(['siswa.sekolah'])
                         ->whereBetween('tanggal', [$this->startDate, $this->endDate]);

        if ($this->sekolahId) {
            $query->whereHas('siswa', function ($q) {
                $q->where('sekolah_id', $this->sekolahId);
            });
        }

        return $query->orderBy('tanggal', 'asc')->get();
    }

    /**
     * Mendefinisikan header untuk kolom Excel.
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Siswa',
            'Asal Sekolah',
            'Status',
            'Jam Masuk',
            'Jam Pulang',
            'Keterangan',
        ];
    }

    /**
     * Memetakan data dari collection ke format yang diinginkan.
     */
    public function map($presensi): array
    {
        $keterangan = $presensi->keterangan ?? '';
        if ($presensi->status == 'Hadir') {
            $jamMasuk = Carbon::parse($presensi->jam_masuk);
            $jamPulang = $presensi->jam_pulang ? Carbon::parse($presensi->jam_pulang) : null;
            $batasMasuk = Carbon::createFromTimeString('09:00:59');
            $batasPulang = Carbon::createFromTimeString('15:00:00');

            $keterangan_list = [];
            if ($jamMasuk->isAfter($batasMasuk)) {
                $keterangan_list[] = 'Telat';
            }
            if ($jamPulang && $jamPulang->isBefore($batasPulang)) {
                $keterangan_list[] = 'Pulang Cepat';
            }
            $keterangan = implode(', ', $keterangan_list);
        }

        return [
            Carbon::parse($presensi->tanggal)->format('d-m-Y'),
            $presensi->siswa->nama_siswa ?? 'Siswa Dihapus',
            $presensi->siswa->sekolah->nama_sekolah ?? 'Sekolah Dihapus',
            $presensi->status,
            $presensi->jam_masuk ? Carbon::parse($presensi->jam_masuk)->format('H:i:s') : '-',
            $presensi->jam_pulang ? Carbon::parse($presensi->jam_pulang)->format('H:i:s') : '-',
            $keterangan ?: '-',
        ];
    }
}
