<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Presensi Umum</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #000; padding: 4px 2px; text-align: center; vertical-align: middle;}
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }

        /* Pewarnaan Sel */
        .bg-green { background-color: #28a745; color: white; font-weight: bold;}
        .bg-pink { background-color: #ffc0cb; color: black; font-weight: bold;}
        .bg-red { background-color: #dc3545; color: white; font-weight: bold;}
        .bg-yellow { background-color: #ffc107; color: black; font-weight: bold;}
        .bg-gray { background-color: #e9ecef; color: black; }

        .text-left { text-align: left !important; padding-left: 5px !important; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    {{-- MELAKUKAN PERULANGAN PER BULAN (Contoh: April, lalu halaman baru untuk Mei) --}}
   @foreach($datesByMonth as $monthKey => $monthDates)
<table>
    <thead>
        <tr>
            <th colspan="{{ count($monthDates) + 6 }}" style="text-align: center; font-weight: bold; font-size: 14px;">
                REKAPITULASI PRESENSI UMUM - {{ strtoupper(\Carbon\Carbon::parse($monthKey . '-01')->isoFormat('MMMM Y')) }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ count($monthDates) + 6 }}" style="text-align: center; font-weight: bold;">
                @if($sekolah) SEKOLAH: {{ strtoupper($sekolah->nama_sekolah) }} @else SEMUA SEKOLAH @endif
            </th>
        </tr>
        <tr>
            <th rowspan="2" style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000;">No</th>
            <th rowspan="2" style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000;">Nama Siswa</th>
            <th rowspan="2" style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000;">Asal Sekolah</th>
            <th colspan="{{ count($monthDates) }}" style="font-weight: bold; text-align: center; border: 1px solid #000000;">Tanggal</th>
            <th colspan="3" style="font-weight: bold; text-align: center; border: 1px solid #000000;">Total</th>
        </tr>
        <tr>
            @foreach($monthDates as $tanggal)
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">{{ \Carbon\Carbon::parse($tanggal)->format('d') }}</th>
            @endforeach
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">H</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">I</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">A</th>
        </tr>
    </thead>
    <tbody>
        @foreach($siswas as $index => $siswa)
            @php
                $totalH = 0; $totalI = 0; $totalA = 0;
            @endphp
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ $siswa->nama_siswa }}</td>
                <td style="border: 1px solid #000000;">{{ $siswa->sekolah->nama_sekolah ?? '-' }}</td>

                @foreach($monthDates as $tanggal)
                    @php
                        $presensi = $pivotData[$tanggal][$siswa->id];
                        $char = ''; $style = 'text-align: center; border: 1px solid #000000; ';

                        if ($presensi['status'] === 'LIBUR') {
                            $char = 'L'; $style .= 'background-color: #cc0000; color: #ffffff;'; // Merah Tua
                        } elseif ($presensi['status'] === 'Izin') {
                            $char = 'I'; $style .= 'background-color: #ffc107; color: #000000;'; // Kuning
                            $totalI++;
                        } elseif ($presensi['status'] === 'Alpa') {
                            $char = 'A'; $style .= 'background-color: #dc3545; color: #ffffff;'; // Merah Biasa
                            $totalA++;
                        } elseif (in_array($presensi['status'], ['Telat', 'Kurang', 'Pulang Cepat'])) {
                            $char = 'H'; $style .= 'background-color: #ffc0cb; color: #000000;'; // Pink
                            $totalH++;
                        } elseif ($presensi['status'] === 'Hadir') {
                            $char = 'H'; $style .= 'background-color: #28a745; color: #ffffff;'; // Hijau
                            $totalH++;
                        }
                    @endphp
                    <td style="{{ $style }}">{{ $char }}</td>
                @endforeach

                <td style="text-align: center; font-weight: bold; border: 1px solid #000000;">{{ $totalH }}</td>
                <td style="text-align: center; font-weight: bold; border: 1px solid #000000;">{{ $totalI }}</td>
                <td style="text-align: center; font-weight: bold; border: 1px solid #000000;">{{ $totalA }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<table><tr><td></td></tr><tr><td></td></tr></table>
@endforeach

</body>
</html>
