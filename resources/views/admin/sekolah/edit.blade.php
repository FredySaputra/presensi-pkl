@extends('adminlte::page')
@section('title', 'Edit Data Sekolah')
@section('content_header')<h1>Edit Data Sekolah</h1>@stop
@section('content')
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.sekolah.update', $sekolah->id) }}" method="POST">
            @method('PUT')
            @include('admin.sekolah._form', ['submitButtonText' => 'Update'])
        </form>
    </div></div>
@stop