@extends('adminlte::page')
@section('title', 'Tambah Sekolah Baru')
@section('content_header')<h1>Tambah Sekolah Baru</h1>@stop
@section('content')
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.sekolah.store') }}" method="POST">
            @include('admin.sekolah._form', ['submitButtonText' => 'Simpan'])
        </form>
    </div></div>
@stop