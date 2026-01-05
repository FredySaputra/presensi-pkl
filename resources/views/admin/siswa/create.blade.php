@extends('adminlte::page')

@section('title', 'Tambah Siswa Massal')

@section('content_header')
    <h1>Tambah Siswa PKL Baru</h1>
@stop

@section('content')
    <div class="card">
        <form action="{{ route('admin.siswa.store') }}" method="POST">
            @csrf
            <div class="card-body">
                {{-- Data Umum (Sekolah & Masa PKL) --}}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sekolah_id">Asal Sekolah</label>
                            <select name="sekolah_id" class="form-control select2 @error('sekolah_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Sekolah --</option>
                                @foreach($sekolahs as $sekolah)
                                    <option value="{{ $sekolah->id }}" {{ old('sekolah_id') == $sekolah->id ? 'selected' : '' }}>{{ $sekolah->nama_sekolah }}</option>
                                @endforeach
                            </select>
                            @error('sekolah_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="mulai_pkl">Mulai PKL</label>
                            <input type="date" name="mulai_pkl" class="form-control @error('mulai_pkl') is-invalid @enderror" value="{{ old('mulai_pkl') }}" required>
                            @error('mulai_pkl') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="selesai_pkl">Selesai PKL</label>
                            <input type="date" name="selesai_pkl" class="form-control @error('selesai_pkl') is-invalid @enderror" value="{{ old('selesai_pkl') }}" required>
                            @error('selesai_pkl') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <hr>
                <h5 class="mb-3">Daftar Siswa</h5>
                <p class="text-muted small">* Klik pada kolom ID Kartu lalu tempelkan kartu ke RFID Reader.</p>

                <table class="table table-bordered" id="studentTable">
                    <thead>
                        <tr class="bg-light">
                            <th>Nama Siswa</th>
                            <th style="width: 300px;">ID Kartu (RFID)</th>
                            <th style="width: 50px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="student-row">
                            <td>
                                <input type="text" name="students[0][nama_siswa]" class="form-control" placeholder="Nama Lengkap" required>
                            </td>
                            <td>
                                <input type="text" name="students[0][id_kartu]" class="form-control id-kartu-input" placeholder="Scan Kartu..." >
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row" disabled><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <button type="button" class="btn btn-info mt-2" id="addRow">
                    <i class="fas fa-plus"></i> Tambah Baris Siswa
                </button>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Simpan Semua Data</button>
                <a href="{{ route('admin.siswa.index') }}" class="btn btn-default">Kembali</a>
            </div>
        </form>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    let rowCount = 1;

    // Fungsi Tambah Baris
    $('#addRow').on('click', function() {
        let newRow = `
            <tr class="student-row">
                <td>
                    <input type="text" name="students[${rowCount}][nama_siswa]" class="form-control" placeholder="Nama Lengkap" required>
                </td>
                <td>
                    <input type="text" name="students[${rowCount}][id_kartu]" class="form-control id-kartu-input" placeholder="Scan Kartu..." >
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        $('#studentTable tbody').append(newRow);
        
        // Aktifkan tombol hapus jika baris > 1
        $('.remove-row').prop('disabled', false);
        rowCount++;
    });

    // Fungsi Hapus Baris
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        if ($('#studentTable tbody tr').length <= 1) {
            $('.remove-row').prop('disabled', true);
        }
    });

    /**
     * Logika Pindah Fokus Otomatis
     * Saat RFID mendeteksi kartu, biasanya ia mengirimkan input + ENTER.
     * Kita tangkap event ini agar setelah scan kartu, kursor otomatis pindah ke
     * Nama Siswa di baris baru (jika ada) atau menambah baris baru.
     */
    $(document).on('keypress', '.id-kartu-input', function(e) {
        if (e.which == 13) { // Tombol Enter (dari RFID Reader)
            e.preventDefault();
            // Jika ini baris terakhir, klik tambah baris otomatis
            let isLastRow = $(this).closest('tr').is(':last-child');
            if (isLastRow) {
                $('#addRow').click();
            }
            
            // Fokus ke input nama di baris berikutnya
            $(this).closest('tr').next().find('input[name*="[nama_siswa]"]').focus();
        }
    });
});
</script>
@stop