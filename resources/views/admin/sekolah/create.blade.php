@extends('adminlte::page')

@section('title', 'Tambah Sekolah Baru')

@section('content_header')
    <h1>Tambah Sekolah Baru</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.sekolah.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="nama_sekolah">Nama Sekolah</label>
                    <input type="text" name="nama_sekolah" class="form-control @error('nama_sekolah') is-invalid @enderror" id="nama_sekolah" value="{{ old('nama_sekolah') }}" required>
                    @error('nama_sekolah')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('admin.sekolah.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
@stop