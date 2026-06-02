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

        .bg-green { background-color: #28a745; color: white; font-weight: bold;}
        .bg-pink { background-color: #ffc0cb; color: black; font-weight: bold;}
        .bg-red { background-color: #dc3545; color: white; font-weight: bold;}
        .bg-yellow { background-color: #ffc107; color: black; font-weight: bold;}
        .bg-libur { background-color: #cc0000; color: white; font-weight: bold; }

        .text-left { text-align: left !important; padding-left: 5px !important; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    {{-- LOOPING TABEL PER BULAN --}}
    @foreach($datesByMonth as $monthKey => $monthDates)
        <div class="header">
            <h2>REKAPITULASI PRESENSI UMUM</h2>
            @if($sekolah)
                <h3>SEKOLAH: {{ strtoupper($sekolah->nama_sekolah) }}</h3>
            @else
                <h3>SEMUA SEKOLAH</h3>
            @endif
            <p>Bulan: <strong>{{ \Carbon\Carbon::parse($monthKey . '-01')->isoFormat('MMMM Y') }}</strong></p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 2%;">No</th>
                    <th rowspan="2" style="width: 15%;">Nama Siswa</th>
                    <th rowspan="2" style="width: 10%;">Asal Sekolah</th>
                    <th colspan="{{ count($monthDates) }}">Tanggal</th>
                    <th colspan="4">Total & Persentase</th>
                </tr>
                <tr>
                    @foreach($monthDates as $tanggal)
                        <th style="width: 1.5%;">{{ \Carbon\Carbon::parse($tanggal)->format('d') }}</th>
                    @endforeach
                    <th style="width: 3%;">H</th>
                    <th style="width: 3%;">I</th>
                    <th style="width: 3%;">A</th>
                    <th style="width: 4%; background-color: #d1ecf1;">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($siswas as $index => $siswa)
                    @php
                        $totalH = 0; $totalI = 0; $totalA = 0; $totalL = 0;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $siswa->nama_siswa }}</td>
                        <td class="text-left">{{ $siswa->sekolah->nama_sekolah ?? '-' }}</td>

                        @foreach($monthDates as $tanggal)
                            @php
                                $presensi = $pivotData[$tanggal][$siswa->id];
                                $char = ''; $class = '';

                                if ($presensi['status'] === 'LIBUR') {
                                    $char = 'L'; $class = 'bg-libur'; $totalL++;
                                } elseif ($presensi['status'] === 'Izin') {
                                    $char = 'I'; $class = 'bg-yellow'; $totalI++;
                                } elseif ($presensi['status'] === 'Alpa') {
                                    $char = 'A'; $class = 'bg-red'; $totalA++;
                                } elseif (in_array($presensi['status'], ['Telat', 'Kurang', 'Pulang Cepat'])) {
                                    $char = 'H'; $class = 'bg-pink'; $totalH++;
                                } elseif ($presensi['status'] === 'Hadir') {
                                    $char = 'H'; $class = 'bg-green'; $totalH++;
                                }
                            @endphp
                            <td class="{{ $class }}">{{ $char }}</td>
                        @endforeach

                        @php
                            $jmlHari = count($monthDates);
                            $persentase = $jmlHari > 0 ? round((($totalH + $totalI + $totalL) / $jmlHari) * 100, 1) : 0;
                        @endphp

                        <td style="font-weight: bold;">{{ $totalH }}</td>
                        <td style="font-weight: bold;">{{ $totalI }}</td>
                        <td style="font-weight: bold;">{{ $totalA }}</td>
                        <td style="font-weight: bold; background-color: #e2e3e5;">{{ $persentase }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="margin-top: 15px; font-size: 10px; border: 1px dashed #000; padding: 10px; width: 380px;">
            <tr><td colspan="2" style="padding-bottom: 5px;"><strong>Legenda Keterangan:</strong></td></tr>
            <tr><td style="width: 20px;"><div style="width: 12px; height: 12px; background-color: #28a745; border: 1px solid #000;"></div></td><td>H : Hadir (Tepat Waktu)</td></tr>
            <tr><td><div style="width: 12px; height: 12px; background-color: #ffc0cb; border: 1px solid #000;"></div></td><td>H : Hadir (Telat / Pulang Cepat / Kurang Jam)</td></tr>
            <tr><td><div style="width: 12px; height: 12px; background-color: #ffc107; border: 1px solid #000;"></div></td><td>I &nbsp;: Izin (WA / Surat)</td></tr>
            <tr><td><div style="width: 12px; height: 12px; background-color: #dc3545; border: 1px solid #000;"></div></td><td>A : Alpa (Tanpa Keterangan)</td></tr>
            <tr><td><div style="width: 12px; height: 12px; background-color: #cc0000; border: 1px solid #000;"></div></td><td>L &nbsp;: Libur Mingguan / Libur Nasional</td></tr>
        </table>

        <div class="page-break"></div>
    @endforeach

    {{-- TABEL GRAND TOTAL (KESELURUHAN BULAN) DI HALAMAN TERAKHIR --}}
    <div class="header" style="margin-top: 20px;">
        <h2>REKAPITULASI KESELURUHAN (TOTAL SEMUA BULAN)</h2>
        @if($sekolah)
            <h3>SEKOLAH: {{ strtoupper($sekolah->nama_sekolah) }}</h3>
        @else
            <h3>SEMUA SEKOLAH</h3>
        @endif
    </div>

    <table class="table" style="width: 80%; margin: 0 auto; font-size: 11px;">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Nama Siswa</th>
                <th style="width: 20%;">Asal Sekolah</th>
                <th style="width: 8%;">Total H</th>
                <th style="width: 8%;">Total I</th>
                <th style="width: 8%;">Total A</th>
                <th style="width: 10%; background-color: #d1ecf1;">% Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($siswas as $index => $siswa)
                @php
                    $grandH = 0; $grandI = 0; $grandA = 0; $grandL = 0; $jmlHariTotal = 0;

                    // Hitung total dari seluruh bulan yang dipilih
                    foreach($datesByMonth as $monthDates) {
                        $jmlHariTotal += count($monthDates);
                        foreach($monthDates as $tanggal) {
                            $presensi = $pivotData[$tanggal][$siswa->id];
                            if ($presensi['status'] === 'LIBUR') { $grandL++; }
                            elseif ($presensi['status'] === 'Izin') { $grandI++; }
                            elseif ($presensi['status'] === 'Alpa') { $grandA++; }
                            elseif (in_array($presensi['status'], ['Telat', 'Kurang', 'Pulang Cepat', 'Hadir'])) { $grandH++; }
                        }
                    }

                    $persenTotal = $jmlHariTotal > 0 ? round((($grandH + $grandI + $grandL) / $jmlHariTotal) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $siswa->nama_siswa }}</td>
                    <td class="text-left">{{ $siswa->sekolah->nama_sekolah ?? '-' }}</td>
                    <td style="font-weight: bold;">{{ $grandH }}</td>
                    <td style="font-weight: bold;">{{ $grandI }}</td>
                    <td style="font-weight: bold;">{{ $grandA }}</td>
                    <td style="font-weight: bold; background-color: #e2e3e5;">{{ $persenTotal }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
