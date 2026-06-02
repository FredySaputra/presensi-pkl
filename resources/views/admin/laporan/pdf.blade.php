<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi Detail</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #000; padding: 6px; text-align: center; vertical-align: middle; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .page-break { page-break-after: always; }

        .bg-libur { background-color: #cc0000; color: white; font-weight: bold; }
        .bg-izin { background-color: #ffc107; font-weight: bold; }
        .bg-alpa { background-color: #f8d7da; font-weight: bold; }
        .bg-kurang { background-color: #fffacd; }
    </style>
</head>
<body>

@foreach($semuaKelompokSiswa as $index => $kelompokSiswa)
    <div class="header">
        <h2>LAPORAN PRESENSI DETAIL</h2>
        @if($sekolah)
            <h3>SEKOLAH: {{ strtoupper($sekolah->nama_sekolah) }}</h3>
        @else
            <h3>SEMUA SEKOLAH</h3>
        @endif
        <p>Periode: {{ \Carbon\Carbon::parse($tanggalMulai)->isoFormat('D MMMM Y') }} &ndash; {{ \Carbon\Carbon::parse($tanggalSelesai)->isoFormat('D MMMM Y') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">Tanggal</th>
                @foreach ($kelompokSiswa as $siswa)
                    <th colspan="2">
                        {{ $siswa->nama_siswa }}
                        @if(!$sekolah)
                            <br><span style="font-size: 8px; font-weight: normal;">({{ $siswa->sekolah->nama_sekolah ?? '-' }})</span>
                        @endif
                    </th>
                @endforeach
            </tr>
            <tr>
                @foreach ($kelompokSiswa as $siswa)
                    <th>Datang</th>
                    <th>Pulang</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($pivotData as $tanggal => $dataPresensiPerTanggal)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($tanggal)->isoFormat('DD-MM-Y') }}</td>

                    @foreach ($kelompokSiswa as $siswa)
                        @php
                            $presensi = $dataPresensiPerTanggal->get($siswa->id);
                        @endphp

                        @if ($presensi['status'] === 'LIBUR')
                            <td colspan="2" class="bg-libur">LIBUR</td>
                        @elseif ($presensi['status'] === 'Izin')
                            <td colspan="2" class="bg-izin">IZIN</td>
                        @elseif ($presensi['status'] === 'Alpa')
                            <td colspan="2" class="bg-alpa">ALPA</td>
                        @else
                            @php
                                $isKurang = in_array($presensi['status'], ['Kurang', 'Telat', 'Pulang Cepat']);
                                $bgClass = $isKurang ? 'bg-kurang' : '';
                            @endphp
                            <td class="{{ $bgClass }}">{{ $presensi['masuk'] }}</td>
                            <td class="{{ $bgClass }}">{{ $presensi['pulang'] }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach

            {{-- BARIS PERSENTASE KEHADIRAN DI BAWAH DETAIL --}}
            <tr style="background-color: #e9ecef;">
                <td style="font-weight: bold; text-align: right;">% Kehadiran:</td>
                @foreach ($kelompokSiswa as $siswa)
                    @php
                        $totH = 0; $totI = 0; $totL = 0;
                        $jmlHari = count($dates);
                        foreach($dates as $tgl) {
                            $pres = $pivotData[$tgl][$siswa->id];
                            if($pres['status'] === 'LIBUR') $totL++;
                            elseif($pres['status'] === 'Izin') $totI++;
                            elseif(in_array($pres['status'], ['Hadir', 'Telat', 'Kurang', 'Pulang Cepat'])) $totH++;
                        }
                        $persen = $jmlHari > 0 ? round((($totH + $totI + $totL) / $jmlHari) * 100, 1) : 0;
                        $color = $persen < 75 ? 'red' : 'green'; // Merah jika < 75%
                    @endphp
                    <td colspan="2" style="font-weight: bold; text-align: center; color: {{ $color }}; font-size: 12px;">{{ $persen }}%</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
