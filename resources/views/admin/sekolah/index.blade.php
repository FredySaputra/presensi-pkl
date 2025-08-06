@extends('adminlte::page')

@section('title', 'Manajemen Sekolah')

@section('content_header')
    <h1>Manajemen Data Sekolah</h1>
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
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Sekolah</h3>
            <div class="card-tools">
                <a href="{{ route('admin.sekolah.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Sekolah Baru
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Nama Sekolah</th>
                        <th style="width: 150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sekolahs as $sekolah)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $sekolah->nama_sekolah }}</td>
                            <td>
                                <a href="{{ route('admin.sekolah.edit', $sekolah->id) }}" class="btn btn-warning btn-xs">Edit</a>
                                <form action="{{ route('admin.sekolah.destroy', $sekolah->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">
                                Belum ada data sekolah.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop