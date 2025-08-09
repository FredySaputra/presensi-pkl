<!DOCTYPE html>
<html>
<head>
    <title>Laporan Presensi</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dddddd; text-align: left; padding: 8px; font-size: 12px; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>Laporan Presensi Siswa PKL</h2>
    <p>Periode: {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }} - {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}</p>
    @if($sekolahTerpilih)
        <p>Sekolah: {{ $sekolahTerpilih->nama_sekolah }}</p>
    @endif
    <hr>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Nama Siswa</th>
                <th>Sekolah</th>
                <th>Status</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($presensis as $presensi)
                @php
                    $keterangan = $presensi->keterangan ?? '';
                    if ($presensi->status == 'Hadir') {
                        $jamMasuk = \Carbon\Carbon::parse($presensi->jam_masuk);
                        $jamPulang = $presensi->jam_pulang ? \Carbon\Carbon::parse($presensi->jam_pulang) : null;
                        $batasMasuk = \Carbon\Carbon::createFromTimeString('09:00:59');
                        $batasPulang = \Carbon\Carbon::createFromTimeString('15:00:00');

                        $keterangan_list = [];
                        if ($jamMasuk->isAfter($batasMasuk)) {
                            $keterangan_list[] = 'Telat ' . $jamMasuk->diffInMinutes($batasMasuk) . ' menit';
                        }
                        if ($jamPulang && $jamPulang->isBefore($batasPulang)) {
                            $keterangan_list[] = 'Pulang cepat ' . $batasPulang->diffInMinutes($jamPulang) . ' menit';
                        }
                        $keterangan = implode(', ', $keterangan_list);
                    }
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($presensi->tanggal)->format('d-m-Y') }}</td>
                    <td>{{ $presensi->siswa->nama_siswa ?? 'Siswa Dihapus' }}</td>
                    <td>{{ $presensi->siswa->sekolah->nama_sekolah ?? 'Sekolah Dihapus' }}</td>
                    <td>{{ $presensi->status }}</td>
                    <td>{{ $presensi->jam_masuk ? \Carbon\Carbon::parse($presensi->jam_masuk)->format('H:i:s') : '-' }}</td>
                    <td>{{ $presensi->jam_pulang ? \Carbon\Carbon::parse($presensi->jam_pulang)->format('H:i:s') : '-' }}</td>
                    <td>{{ $keterangan ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
