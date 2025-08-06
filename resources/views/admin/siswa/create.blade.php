@extends('adminlte::page')

@section('title', 'Tambah Siswa Baru')

@section('content_header')
    <h1>Tambah Siswa Baru</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.siswa.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nama_siswa">Nama Lengkap Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control @error('nama_siswa') is-invalid @enderror" value="{{ old('nama_siswa') }}" required>
                            @error('nama_siswa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                         <div class="form-group">
                            <label for="sekolah_id">Asal Sekolah</label>
                            <select name="sekolah_id" class="form-control @error('sekolah_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Sekolah --</option>
                                @foreach ($sekolahs as $sekolah)
                                    <option value="{{ $sekolah->id }}" {{ old('sekolah_id') == $sekolah->id ? 'selected' : '' }}>
                                        {{ $sekolah->nama_sekolah }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sekolah_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_kartu">ID Kartu RFID</label>
                    <input type="text" name="id_kartu" class="form-control @error('id_kartu') is-invalid @enderror" value="{{ old('id_kartu') }}" required>
                     @error('id_kartu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mulai_pkl">Tanggal Mulai PKL</label>
                            <input type="date" name="mulai_pkl" class="form-control @error('mulai_pkl') is-invalid @enderror" value="{{ old('mulai_pkl') }}" required>
                            @error('mulai_pkl')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                     <div class="col-md-6">
                        <div class="form-group">
                            <label for="selesai_pkl">Tanggal Selesai PKL</label>
                            <input type="date" name="selesai_pkl" class="form-control @error('selesai_pkl') is-invalid @enderror" value="{{ old('selesai_pkl') }}" required>
                            @error('selesai_pkl')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('admin.siswa.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
@stop