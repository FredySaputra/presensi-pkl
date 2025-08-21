@extends('adminlte::page')

@section('title', 'Arsip Siswa PKL')

@section('content_header')
    <h1>Arsip Siswa PKL</h1>
@stop

@section('content')
    <!-- Form Filter -->
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.siswa.arsip') }}" method="GET" class="form-inline">
                <div class="form-group mb-2">
                    <label for="sekolah_id" class="mr-2">Sekolah:</label>
                    <select name="sekolah_id" class="form-control">
                        <option value="">Semua Sekolah</option>
                        @foreach($sekolahs as $sekolah)
                            <option value="{{ $sekolah->id }}" {{ ($sekolahId ?? '') == $sekolah->id ? 'selected' : '' }}>
                                {{ $sekolah->nama_sekolah }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-2 ml-3">
                    <label for="search" class="mr-2">Cari:</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama Siswa / ID Kartu" value="{{ $search ?? '' }}">
                </div>
                <button type="submit" class="btn btn-primary mb-2 ml-2">Filter</button>
            </form>
        </div>
    </div>

    <!-- Tabel Data Arsip Siswa -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Siswa PKL Non-Aktif</h3>
            <div class="card-tools">
                <a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali ke Siswa Aktif
                </a>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Siswa</th>
                        <th>Asal Sekolah</th>
                        <th>Tanggal Selesai PKL</th>
                        <th style="width: 150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswas as $siswa)
                        <tr>
                            <td>{{ ($siswas->currentPage() - 1) * $siswas->perPage() + $loop->iteration }}</td>
                            <td>{{ $siswa->nama_siswa }}</td>
                            <td>{{ $siswa->sekolah->nama_sekolah ?? 'Sekolah Dihapus' }}</td>
                            <td>{{ \Carbon\Carbon::parse($siswa->selesai_pkl)->isoFormat('D MMMM Y') }}</td>
                            <td>
                                <a href="{{ route('admin.siswa.riwayat', $siswa->id) }}" class="btn btn-info btn-xs">Riwayat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">
                                Tidak ada data siswa di arsip.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $siswas->appends(request()->query())->links() }}
        </div>
    </div>
@stop
