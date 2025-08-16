<!DOCTYPE html>
<html>
<head>
    <title>Laporan Presensi</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; text-align: center; padding: 4px; font-size: 10px; }
        th { background-color: #f2f2f2; }
        h3, p { text-align: left; margin: 0; }
        .header-table { width: 100%; border: none; margin-bottom: 15px; }
        .header-table td { border: none; text-align: left; padding: 0; }
        .no-wrap { white-space: nowrap; }
        /* Class untuk memberikan page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    {{-- Loop untuk setiap kelompok (chunk) siswa --}}
    @foreach ($studentChunks as $studentsChunk)
        {{-- Tambahkan class page-break jika ini bukan halaman terakhir --}}
        <div class="{{ !$loop->last ? 'page-break' : '' }}">
            <table class="header-table">
                <tr>
                    <td>
                        <h3>LAPORAN PRESENSI</h3>
                        @if($sekolahTerpilih)
                            <p><strong>Sekolah:</strong> {{ $sekolahTerpilih->nama_sekolah }}</p>
                        @endif
                        <p><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}</p>
                    </td>
                </tr>
            </table>

            <table>
                <thead>
                    <tr>
                        <th rowspan="2" style="vertical-align: middle;">Tanggal</th>
                        @foreach ($studentsChunk as $student)
                            <th colspan="2">{{ $student->nama_siswa }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($studentsChunk as $student)
                            <th>Datang</th>
                            <th>Pulang</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dates as $date)
                        <tr>
                            <td class="no-wrap">{{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</td>
                            @foreach ($studentsChunk as $student)
                                @php
                                    $presensi = $reportData[$date][$student->id] ?? null;
                                @endphp
                                @if ($presensi)
                                    @if ($presensi['status'] == 'Hadir')
                                        <td>{{ \Carbon\Carbon::parse($presensi['jam_masuk'])->format('H:i') }}</td>
                                        <td>{{ $presensi['jam_pulang'] ? \Carbon\Carbon::parse($presensi['jam_pulang'])->format('H:i') : '-' }}</td>
                                    @else
                                        <td colspan="2">{{ $presensi['status'] }}</td>
                                    @endif
                                @else
                                    <td colspan="2">Alpa</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
