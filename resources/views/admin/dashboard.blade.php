@extends('adminlte::page')

@section('title', 'Dashboard Admin')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    {{-- Baris untuk Kartu Statistik --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            {{-- Kartu Total Siswa Aktif --}}
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalSiswaAktif }}</h3>
                    <p>Total Siswa PKL Aktif</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('admin.siswa.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            {{-- Kartu Jumlah Hadir --}}
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $jumlahHadir }}</h3>
                    <p>Hadir Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <a href="{{ route('admin.laporan.index') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            {{-- Kartu Jumlah Izin --}}
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $jumlahIzin }}</h3>
                    <p>Izin Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-clock"></i>
                </div>
                <a href="{{ route('admin.laporan.index') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            {{-- Kartu Jumlah Alpa --}}
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $jumlahAlpa }}</h3>
                    <p>Alpa Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-times"></i>
                </div>
                <a href="{{ route('admin.laporan.index') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Baris untuk Tabel Siswa Terlambat --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Siswa Terlambat Hari Ini ({{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y') }})</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Nama Siswa</th>
                                <th>Asal Sekolah</th>
                                <th>Jam Masuk</th>
                                <th>Keterlambatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($siswaTerlambat as $presensi)
                                @php
                                    $jamMasuk = \Carbon\Carbon::parse($presensi->jam_masuk);
                                    $batasMasuk = \Carbon\Carbon::createFromTimeString('09:00:59');
                                    $keterlambatan = $jamMasuk->diffForHumans($batasMasuk, true);
                                @endphp
                                <tr>
                                    <td>{{ $presensi->siswa->nama_siswa }}</td>
                                    <td>{{ $presensi->siswa->sekolah->nama_sekolah }}</td>
                                    <td><span class="badge badge-danger">{{ $jamMasuk->format('H:i:s') }}</span></td>
                                    <td>{{ $keterlambatan }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada siswa yang terlambat hari ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
