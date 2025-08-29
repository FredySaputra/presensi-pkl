@extends('adminlte::page')

@section('title', 'Manajemen Siswa PKL')

@section('content_header')
    <h1>Manajemen Data Siswa PKL</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Form Filter -->
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.siswa.index') }}" method="GET" class="form-inline">
                <div class="form-group mb-2">
                    <label for="sekolah_id" class="mr-2">Filter Sekolah:</label>
                    <select name="sekolah_id" class="form-control">
                        <option value="">Semua Sekolah</option>
                        @foreach($sekolahs as $sekolah)
                            <option value="{{ $sekolah->id }}" {{ ($sekolahId ?? '') == $sekolah->id ? 'selected' : '' }}>
                                {{ $sekolah->nama_sekolah }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2 ml-2">Filter</button>
            </form>
        </div>
    </div>

    <!-- Tabel Data Siswa -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Siswa PKL Aktif</h3>
            <div class="card-tools">
                <a href="{{ route('admin.siswa.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Siswa Baru
                </a>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Nama Siswa</th>
                        <th>Asal Sekolah</th>
                        <th>ID Kartu RFID</th>
                        <th>Masa PKL</th>
                        <th style="width: 200px">Aksi</th> {{-- Lebarkan kolom Aksi --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswas as $siswa)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $siswa->nama_siswa }}</td>
                            <td>{{ $siswa->sekolah->nama_sekolah ?? 'Sekolah Dihapus' }}</td>
                            <td>{{ $siswa->id_kartu }}</td>
                            <td>{{ \Carbon\Carbon::parse($siswa->mulai_pkl)->format('d M Y') }} - {{ \Carbon\Carbon::parse($siswa->selesai_pkl)->format('d M Y') }}</td>
                            <td>
                                {{-- TOMBOL BARU --}}
                                <a href="{{ route('admin.siswa.riwayat', $siswa->id) }}" class="btn btn-info btn-xs">Riwayat</a>
                                <a href="{{ route('admin.siswa.edit', $siswa->id) }}" class="btn btn-warning btn-xs">Edit</a>
                                <form action="{{ route('admin.siswa.destroy', $siswa->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                Tidak ada data siswa yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
