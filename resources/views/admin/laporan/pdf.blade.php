<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: center; }
        .table th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .page-break { page-break-after: always; }
        .libur { background-color: #ffcccc; } /* Warna merah untuk libur */
        .kurang { background-color: #fffacd; } /* Warna kuning untuk status kurang */
    </style>
</head>
<body>

@foreach($semuaKelompokSiswa as $index => $kelompokSiswa)
    <div class="header">
        <h2>LAPORAN PRESENSI</h2>
        @if($sekolah)
            <h3>SEKOLAH: {{ $sekolah->nama_sekolah }}</h3>
        @endif
        <p>Tanggal: {{ \Carbon\Carbon::parse($tanggalMulai)->isoFormat('D MMMM Y') }} &ndash; {{ \Carbon\Carbon::parse($tanggalSelesai)->isoFormat('D MMMM Y') }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th rowspan="2" style="vertical-align: middle;">Tanggal</th>
                @foreach ($kelompokSiswa as $siswa)
                    <th colspan="2">{{ $siswa->nama_siswa }}</th>
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
            @foreach ($tanggalData as $tanggal => $dataPresensiPerTanggal)
                @php
                    // Ambil data presensi siswa pertama untuk mengecek hari libur
                    $presensiPertama = $dataPresensiPerTanggal->firstWhere('siswa_id', $kelompokSiswa->first()->id);
                    $isLibur = $presensiPertama && $presensiPertama['status'] === 'LIBUR';
                    
                    // Cek apakah ada status 'Kurang' di baris ini
                    $isKurang = false;
                    foreach ($kelompokSiswa as $siswa) {
                        $presensiSiswa = $dataPresensiPerTanggal->firstWhere('siswa_id', $siswa->id);
                        if ($presensiSiswa && $presensiSiswa['status'] === 'Kurang') {
                            $isKurang = true;
                            break;
                        }
                    }

                    $rowClass = '';
                    if ($isLibur) {
                        $rowClass = 'libur';
                    } elseif ($isKurang) {
                        $rowClass = 'kurang';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>{{ \Carbon\Carbon::parse($tanggal)->isoFormat('DD-MM-Y') }}</td>
                    @foreach ($kelompokSiswa as $siswa)
                        @php
                            $presensi = $dataPresensiPerTanggal->firstWhere('siswa_id', $siswa->id);
                        @endphp
                        @if ($isLibur)
                            <td colspan="2">LIBUR</td>
                        @elseif ($presensi)
                            @if ($presensi['status'] === 'Izin')
                                <td colspan="2">IZIN</td>
                            @elseif ($presensi['status'] === 'Alpa')
                                <td colspan="2">ALPA</td>
                            @else
                                <td>{{ $presensi['jam_masuk'] ? \Carbon\Carbon::parse($presensi['jam_masuk'])->format('H:i') : '-' }}</td>
                                <td>{{ $presensi['jam_pulang'] ? \Carbon\Carbon::parse($presensi['jam_pulang'])->format('H:i') : '-' }}</td>
                            @endif
                        @else
                            <td colspan="2">ALPA</td>
                        @endif
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
