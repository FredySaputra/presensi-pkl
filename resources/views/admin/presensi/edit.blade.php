@extends('adminlte::page')

@section('title', 'Edit Presensi')

@section('content_header')
    <h1>Edit Presensi</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.presensi.update', $presensi->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Siswa</label>
                    <input type="text" class="form-control" value="{{ $presensi->siswa->nama_siswa }}" disabled>
                </div>

                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($presensi->tanggal)->isoFormat('dddd, D MMMM Y') }}" disabled>
                </div>

                <div class="form-group">
                    <label for="status">Status Kehadiran</label>
                    <select name="status" id="status" class="form-control">
                        <option value="Hadir" {{ $presensi->status == 'Hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="Izin" {{ $presensi->status == 'Izin' ? 'selected' : '' }}>Izin</option>
                        <option value="Alpa" {{ $presensi->status == 'Alpa' ? 'selected' : '' }}>Alpa</option>
                    </select>
                </div>

                {{-- Field ini akan muncul/hilang berdasarkan pilihan status --}}
                <div id="waktu-kehadiran-fields" style="{{ $presensi->status != 'Hadir' ? 'display:none;' : '' }}">
                    <div class="form-group">
                        <label for="jam_masuk">Jam Masuk</label>
                        <input type="time" name="jam_masuk" class="form-control" value="{{ $presensi->jam_masuk ? \Carbon\Carbon::parse($presensi->jam_masuk)->format('H:i') : '' }}">
                    </div>
                    <div class="form-group">
                        <label for="jam_pulang">Jam Pulang</label>
                        <input type="time" name="jam_pulang" class="form-control" value="{{ $presensi->jam_pulang ? \Carbon\Carbon::parse($presensi->jam_pulang)->format('H:i') : '' }}">
                    </div>
                </div>

                <div id="keterangan-fields" style="{{ $presensi->status == 'Hadir' ? 'display:none;' : '' }}">
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="{{ $presensi->keterangan }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.laporan.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        // JavaScript untuk menampilkan/menyembunyikan field berdasarkan status
        $(document).ready(function() {
            $('#status').on('change', function() {
                if ($(this).val() === 'Hadir') {
                    $('#waktu-kehadiran-fields').show();
                    $('#keterangan-fields').hide();
                } else {
                    $('#waktu-kehadiran-fields').hide();
                    $('#keterangan-fields').show();
                }
            }).trigger('change'); // Trigger saat halaman dimuat
        });
    </script>
@stop
