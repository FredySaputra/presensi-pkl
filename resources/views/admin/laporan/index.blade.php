@extends('adminlte::page')

@section('title', 'Laporan Presensi')

@section('content_header')
    <h1>Laporan Presensi</h1>
@stop

@section('content')
    {{-- Pesan Sukses/Error --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    {{-- Form Filter dan Tombol Cetak --}}
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.laporan.index') }}" method="GET" class="form-inline">
                <div class="form-group mb-2">
                    <label for="start_date" class="mr-2">Dari Tanggal:</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="form-group mx-sm-3 mb-2">
                    <label for="end_date" class="mr-2">Sampai Tanggal:</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="form-group mx-sm-3 mb-2">
                    <label for="sekolah_id" class="mr-2">Sekolah:</label>
                    <select name="sekolah_id" class="form-control">
                        <option value="">Semua Sekolah</option>
                        @foreach($sekolahs as $sekolah)
                            <option value="{{ $sekolah->id }}" {{ $sekolahId == $sekolah->id ? 'selected' : '' }}>
                                {{ $sekolah->nama_sekolah }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2">Filter</button>
            </form>
            <form action="{{ route('admin.laporan.cetak_pdf') }}" method="POST" class="mt-2" target="_blank">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate }}">
                <input type="hidden" name="end_date" value="{{ $endDate }}">
                <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
                <button type="submit" class="btn btn-success"><i class="fas fa-print"></i> Cetak ke PDF</button>
            </form>
        </div>
    </div>

    {{-- Tabel Laporan --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Data Presensi dari {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }} sampai {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Siswa</th>
                        <th>Asal Sekolah</th>
                        <th>Status</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($presensis as $presensi)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($presensi->tanggal)->isoFormat('dddd, D MMM Y') }}</td>
                            <td>{{ $presensi->siswa->nama_siswa ?? 'Siswa Dihapus' }}</td>
                            <td>{{ $presensi->siswa->sekolah->nama_sekolah ?? 'Sekolah Dihapus' }}</td>
                            <td>
                                @if($presensi->status == 'Hadir')
                                    <span class="badge badge-success">Hadir</span>
                                @elseif($presensi->status == 'Izin')
                                    <span class="badge badge-info">Izin</span>
                                @else
                                    <span class="badge badge-secondary">Alpa</span>
                                @endif
                            </td>
                            <td>{{ $presensi->jam_masuk ? \Carbon\Carbon::parse($presensi->jam_masuk)->format('H:i:s') : '-' }}</td>
                            <td>{{ $presensi->jam_pulang ? \Carbon\Carbon::parse($presensi->jam_pulang)->format('H:i:s') : '-' }}</td>
                            <td>
                                {{-- PERUBAHAN: Menambahkan logika perhitungan keterangan --}}
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
                                {{ $keterangan ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada data presensi pada rentang tanggal dan sekolah yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
