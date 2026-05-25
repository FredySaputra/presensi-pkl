<table>
    <thead>
        <tr>
            <th colspan="{{ 1 + ($siswas->count() * 2) }}" style="text-align: center; font-weight: bold; font-size: 14px;">
                LAPORAN PRESENSI DETAIL {{ $sekolah ? 'SEKOLAH: ' . strtoupper($sekolah->nama_sekolah) : 'SEMUA SEKOLAH' }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ 1 + ($siswas->count() * 2) }}" style="text-align: center; font-weight: bold;">
                Periode: {{ \Carbon\Carbon::parse($tanggalMulai)->isoFormat('D MMMM Y') }} - {{ \Carbon\Carbon::parse($tanggalSelesai)->isoFormat('D MMMM Y') }}
            </th>
        </tr>
        <tr><th colspan="{{ 1 + ($siswas->count() * 2) }}"></th></tr>

        <tr>
            <th rowspan="2" style="font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000;">Tanggal</th>
            @foreach ($siswas as $siswa)
                <th colspan="2" style="font-weight: bold; text-align: center; border: 1px solid #000000;">
                    {{-- PERBAIKAN: Nama sekolah dijadikan satu baris --}}
                    {{ strtoupper($siswa->nama_siswa) }} @if(!$sekolah) ({{ $siswa->sekolah->nama_sekolah ?? '-' }}) @endif
                </th>
            @endforeach
        </tr>
        <tr>
            @foreach ($siswas as $siswa)
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Datang</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Pulang</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($pivotData as $tanggal => $dataPresensiPerTanggal)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ \Carbon\Carbon::parse($tanggal)->isoFormat('DD-MM-Y') }}</td>

                @foreach ($siswas as $siswa)
                    @php
                        $presensi = $dataPresensiPerTanggal->get($siswa->id);
                    @endphp

                    @if ($presensi['status'] === 'LIBUR')
                        <td colspan="2" style="text-align: center; background-color: #cc0000; color: #ffffff; border: 1px solid #000000;">LIBUR</td>
                    @elseif ($presensi['status'] === 'Izin')
                        <td colspan="2" style="text-align: center; background-color: #ffc107; color: #000000; border: 1px solid #000000;">IZIN</td>
                    @elseif ($presensi['status'] === 'Alpa')
                        <td colspan="2" style="text-align: center; background-color: #f8d7da; color: #000000; border: 1px solid #000000;">ALPA</td>
                    @else
                        @php
                            $isKurang = in_array($presensi['status'], ['Kurang', 'Telat', 'Pulang Cepat']);
                            $bgStyle = $isKurang ? 'background-color: #fffacd;' : '';
                        @endphp
                        <td style="text-align: center; border: 1px solid #000000; {{ $bgStyle }}">{{ $presensi['masuk'] }}</td>
                        <td style="text-align: center; border: 1px solid #000000; {{ $bgStyle }}">{{ $presensi['pulang'] }}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
