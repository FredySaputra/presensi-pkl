@extends('adminlte::page')

@section('title', 'Riwayat Presensi Siswa')

@section('content_header')
    <h1>Riwayat Presensi Siswa</h1>
@stop

@section('content')
    {{-- Kartu Informasi Siswa --}}
    <div class="card">
        <div class="card-body">
            <h4><strong>{{ $siswa->nama_siswa }}</strong></h4>
            <p class="mb-0"><strong>Asal Sekolah:</strong> {{ $siswa->sekolah->nama_sekolah }}</p>
            <p><strong>Periode PKL:</strong> {{ \Carbon\Carbon::parse($siswa->mulai_pkl)->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse($siswa->selesai_pkl)->isoFormat('D MMM Y') }}</p>
            <a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Siswa
            </a>
        </div>
    </div>

    {{-- Tabel Riwayat Presensi --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detail Kehadiran</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tanggal</th>
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
                            <td>{{ $presensi->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada riwayat presensi untuk siswa ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
