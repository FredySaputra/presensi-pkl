@extends('adminlte::page')

@section('title', 'Laporan Presensi')

@section('content_header')
    <h1>Laporan Presensi Harian</h1>
@stop

@section('content')
    {{-- Pesan Sukses --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Status Kehadiran Siswa - {{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y') }}</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Siswa</th>
                        <th>Asal Sekolah</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($siswas as $siswa)
                        @php
                            // Cek apakah ada data presensi untuk siswa ini
                            $presensi = $presensis->get($siswa->id);
                            $status = 'Alpa';
                            $keterangan = '-';
                            $badge_class = 'badge-secondary';

                            if ($presensi) {
                                if ($presensi->status == 'Izin') {
                                    $status = 'Izin';
                                    $badge_class = 'badge-info';
                                    $keterangan = $presensi->keterangan;
                                } elseif ($presensi->jam_pulang) {
                                    $status = 'Pulang';
                                    $badge_class = 'badge-success';
                                    $keterangan = 'Masuk: ' . $presensi->jam_masuk . ' | Pulang: ' . $presensi->jam_pulang;
                                } else {
                                    $status = 'Hadir';
                                    $badge_class = 'badge-primary';
                                    $keterangan = 'Masuk: ' . $presensi->jam_masuk;
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $siswa->nama_siswa }}</td>
                            <td>{{ $siswa->sekolah->nama_sekolah }}</td>
                            <td><span class="badge {{ $badge_class }}">{{ $status }}</span></td>
                            <td>{{ $keterangan }}</td>
                            <td>
                                @if (!$presensi)
                                    {{-- Tampilkan form hanya jika siswa belum ada data presensi (Alpa) --}}
                                    <form action="{{ route('admin.laporan.izin') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                                        <div class="input-group">
                                            <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Alasan izin..." required>
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-info btn-sm">Izin</button>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop