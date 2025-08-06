@extends('adminlte::page')

@section('title', 'Edit Data Sekolah')

@section('content_header')
    <h1>Edit Data Sekolah</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            {{-- Gunakan route 'update' dan method 'PUT' untuk proses update --}}
            <form action="{{ route('admin.sekolah.update', $sekolah->id) }}" method="POST">
                @csrf
                @method('PUT') {{-- Method spoofing untuk request PUT --}}

                <div class="form-group">
                    <label for="nama_sekolah">Nama Sekolah</label>
                    {{-- Tampilkan data lama di dalam input --}}
                    <input type="text" name="nama_sekolah" class="form-control @error('nama_sekolah') is-invalid @enderror" id="nama_sekolah" value="{{ old('nama_sekolah', $sekolah->nama_sekolah) }}" required>
                    @error('nama_sekolah')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.sekolah.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
@stop