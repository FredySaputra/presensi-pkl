    <!DOCTYPE html>
    <html>
    <head>
        <title>Laporan Presensi</title>
        <style>
            body { font-family: sans-serif; font-size: 10px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid black; padding: 5px; text-align: center; }
            .header { text-align: center; margin-bottom: 20px; }
            .header h2, .header h4 { margin: 0; }
            .page-break { page-break-after: always; }
            .holiday { background-color: #ffcccc; } /* <-- STYLE BARU */
        </style>
    </head>
    <body>
        @foreach ($siswaChunks as $chunkIndex => $siswaChunk)
            <div class="header">
                <h2>LAPORAN PRESENSI</h2>
                @if ($sekolah)
                    <h4>{{ $sekolah->nama_sekolah }}</h4>
                @endif
                <h4>Tanggal: {{ \Carbon\Carbon::parse($tanggalMulai)->isoFormat('D MMMM Y') }} - {{ \Carbon\Carbon::parse($tanggalSelesai)->isoFormat('D MMMM Y') }}</h4>
            </div>

            <table>
                <thead>
                    <tr>
                        <th rowspan="2">Tanggal</th>
                        @foreach ($siswaChunk as $siswa)
                            <th colspan="2">{{ $siswa->nama_siswa }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($siswaChunk as $siswa)
                            <th>Datang</th>
                            <th>Pulang</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dates as $tanggal)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($tanggal)->isoFormat('DD-MM-YYYY') }}</td>
                            @foreach ($siswaChunk as $siswa)
                                @php
                                    $data = $pivotData[$tanggal][$siswa->id];
                                    $isHoliday = $data['masuk'] === 'LIBUR'; // <-- CEK LIBUR
                                @endphp
                                <td class="{{ $isHoliday ? 'holiday' : '' }}">{{ $data['masuk'] }}</td>
                                <td class="{{ $isHoliday ? 'holiday' : '' }}">{{ $data['pulang'] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            @if (!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    </body>
    </html>
    