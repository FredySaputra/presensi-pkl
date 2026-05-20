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

    <div class="card shadow-sm">
        <div class="card-header border-0">
            <h3 class="card-title mt-1">Daftar Sekolah</h3>
            <div class="card-tools">
                <a href="{{ route('admin.sekolah.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tambah Sekolah Baru
                </a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped text-nowrap">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 10px" class="text-center">#</th>
                        <th>Nama Sekolah</th>
                        <th>Alamat & Lokasi</th>
                        <th style="width: 150px" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sekolahs as $sekolah)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $sekolah->nama_sekolah }}</strong><br>
                                @if($sekolah->hari_libur)
                                    <small class="text-muted">Libur Tambahan: Hari ke-{{ $sekolah->hari_libur }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="d-block text-truncate" style="max-width: 300px;" title="{{ $sekolah->alamat }}">{{ $sekolah->alamat ?? 'Alamat belum diisi' }}</span>

                                @if($sekolah->latitude && $sekolah->longitude)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $sekolah->latitude }},{{ $sekolah->longitude }}" target="_blank" class="btn btn-xs btn-outline-info mt-1">
                                        <i class="fas fa-map-marked-alt"></i> Buka Maps
                                    </a>
                                @else
                                    <span class="badge badge-secondary mt-1">Lokasi Maps Belum Diset</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.sekolah.edit', $sekolah->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.sekolah.destroy', $sekolah->id) }}" method="POST" class="d-inline form-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle mb-2 d-block"></i>
                                Belum ada data sekolah yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const deleteForms = document.querySelectorAll('.form-delete');

        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Apakah Anda Yakin?',
                    text: "Data sekolah beserta siswanya (jika ada) akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session("success") }}',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
        @endif

    });
</script>
@stop
