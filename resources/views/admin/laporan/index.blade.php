@extends('adminlte::page')

@section('title', 'Laporan Presensi')

@section('content_header')
    <h1>Laporan Presensi</h1>
@stop

@section('content')
    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    {{-- Form Filter --}}
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.laporan.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tanggal_mulai">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="{{ $tanggalMulai }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tanggal_selesai">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" value="{{ $tanggalSelesai }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sekolah_id">Filter Sekolah</label>
                            <select name="sekolah_id" class="form-control">
                                <option value="">Semua Sekolah</option>
                                @foreach($sekolahs as $sekolah)
                                    <option value="{{ $sekolah->id }}" {{ $sekolahId == $sekolah->id ? 'selected' : '' }}>{{ $sekolah->nama_sekolah }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Cari Nama Siswa</label>
                            <input type="text" name="search" class="form-control" placeholder="Cari nama..." value="{{ $search }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <button form="cetakPdfForm" type="submit" class="btn btn-success"><i class="fas fa-print"></i> Cetak ke PDF</button>
                        <button form="cetakExcelForm" type="submit" class="btn btn-info"><i class="fas fa-file-excel"></i> Ekspor ke Excel</button>
                    </div>
                </div>
            </form>
            <form id="cetakPdfForm" action="{{ route('admin.laporan.cetak_pdf') }}" method="POST" target="_blank" class="d-none">
                @csrf
                <input type="hidden" name="tanggal_mulai" value="{{ $tanggalMulai }}">
                <input type="hidden" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
                <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
            </form>
            <form id="cetakExcelForm" action="{{ route('admin.laporan.cetak_excel') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="tanggal_mulai" value="{{ $tanggalMulai }}">
                <input type="hidden" name="tanggal_selesai" value="{{ $tanggalSelesai }}">
                <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
            </form>
        </div>
    </div>

    {{-- Tabel Laporan --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Data Presensi</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#izinModal">
                    <i class="fas fa-user-check"></i> Catat Izin
                </button>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#manualModal">
                    <i class="fas fa-edit"></i> Presensi Manual
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama Siswa</th>
                        <th>Asal Sekolah</th>
                        <th>Status</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presensis as $presensi)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($presensi->tanggal)->isoFormat('dddd, D MMMM Y') }}</td>
                            <td>{{ $presensi->siswa->nama_siswa ?? 'Siswa Dihapus' }}</td>
                            <td>{{ $presensi->siswa->sekolah->nama_sekolah ?? 'Sekolah Dihapus' }}</td>
                            <td>
                                @if($presensi->status == 'Hadir')
                                    <span class="badge badge-success">Hadir</span>
                                @elseif($presensi->status == 'Izin')
                                    <span class="badge badge-info">Izin</span>
                                @elseif($presensi->status == 'Kurang')
                                    <span class="badge badge-warning">Kurang</span>
                                @else
                                    <span class="badge badge-secondary">{{ $presensi->status }}</span>
                                @endif
                            </td>
                            <td>{{ $presensi->jam_masuk ? \Carbon\Carbon::parse($presensi->jam_masuk)->format('H:i:s') : '-' }}</td>
                            <td>{{ $presensi->jam_pulang ? \Carbon\Carbon::parse($presensi->jam_pulang)->format('H:i:s') : '-' }}</td>
                            <td>
                                @php
                                    $keterangan = $presensi->keterangan;
                                    if ($presensi->status == 'Hadir' || $presensi->status == 'Kurang') {
                                        if ($presensi->jam_masuk) {
                                            $jamMasuk = \Carbon\Carbon::parse($presensi->jam_masuk);
                                            $batasMasuk = \Carbon\Carbon::parse($presensi->tanggal . ' 09:00:00');
                                            if ($jamMasuk->gt($batasMasuk)) {
                                                $keterangan = 'Telat ' . $jamMasuk->diffForHumans($batasMasuk, ['parts' => 2, 'short' => true]);
                                            } else {
                                                $keterangan = 'Tepat Waktu';
                                            }
                                        }

                                        if ($presensi->jam_pulang) {
                                            $jamPulang = \Carbon\Carbon::parse($presensi->jam_pulang);
                                            $batasPulang = \Carbon\Carbon::parse($presensi->tanggal . ' 15:00:00');
                                            if ($jamPulang->lt($batasPulang)) {
                                                $keterangan .= ' | Pulang Awal ' . $jamPulang->diffForHumans($batasPulang, ['parts' => 2, 'short' => true]);
                                            }
                                        }
                                    }
                                @endphp
                                {{ $keterangan }}
                            </td>
                             <td>
                                <a href="{{ route('admin.presensi.edit', $presensi->id) }}" class="btn btn-xs btn-warning">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data untuk ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $presensis->appends(request()->query())->links() }}
        </div>
    </div>

    <!-- Modal Catat Izin -->
    <div class="modal fade" id="izinModal" tabindex="-1" role="dialog" aria-labelledby="izinModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="izinModalLabel">Catat Izin Siswa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.laporan.izin') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="izin_tanggal">Pilih Tanggal Izin</label>
                            <input type="date" id="izin_tanggal" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <hr>
                        <div id="izin_lanjutan" style="display: none;">
                            <div class="form-group">
                                <label for="izin_siswa_id">Pilih Siswa (Hanya yang belum presensi)</label>
                                <select id="izin_siswa_id" name="siswa_id" class="form-control" required>
                                    <option value="">-- Memuat Siswa --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="izin_keterangan">Keterangan Izin</label>
                                <textarea id="izin_keterangan" name="keterangan" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                         <div id="izin_loading" class="text-center" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p>Mencari siswa yang tersedia...</p>
                        </div>
                        <div id="izin_info" class="text-center">
                            <p class="text-muted">Pilih tanggal untuk melihat siswa yang bisa diizinkan.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" id="izin_submit_button" class="btn btn-primary" disabled>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    {{-- Modal Presensi Manual --}}
    <div class="modal fade" id="manualModal" tabindex="-1" role="dialog" aria-labelledby="manualModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualModalLabel">Input Presensi Manual</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.laporan.manual') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="siswa_id_manual">Pilih Siswa</label>
                            <select name="siswa_id" id="siswa_id_manual" class="form-control" required>
                                <option value="">-- Pilih Siswa --</option>
                                @foreach($semuaSiswa as $siswa)
                                    <option value="{{ $siswa->id }}">{{ $siswa->nama_siswa }} ({{ $siswa->sekolah->nama_sekolah }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="jam_masuk">Jam Masuk</label>
                            <input type="time" name="jam_masuk" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="jam_pulang">Jam Pulang (Opsional)</label>
                            <input type="time" name="jam_pulang" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    function fetchAvailableStudents() {
        var tanggal = $('#izin_tanggal').val();
        if (!tanggal) return;

        $('#izin_lanjutan').hide();
        $('#izin_info').hide();
        $('#izin_loading').show();
        $('#izin_submit_button').prop('disabled', true);
        $('#izin_siswa_id').html('<option value="">-- Memuat Siswa --</option>');

        $.ajax({
            url: '{{ route("admin.laporan.getSiswa") }}',
            type: 'GET',
            data: { tanggal: tanggal },
            success: function(data) {
                $('#izin_loading').hide();
                var siswaDropdown = $('#izin_siswa_id');
                siswaDropdown.empty().append('<option value="">-- Pilih Siswa --</option>');

                if (data.length > 0) {
                    $.each(data, function(key, siswa) {
                        siswaDropdown.append('<option value="' + siswa.id + '">' + siswa.nama_siswa + ' (' + siswa.sekolah.nama_sekolah + ')</option>');
                    });
                    $('#izin_lanjutan').show();
                    $('#izin_submit_button').prop('disabled', false);
                } else {
                    $('#izin_info').show().html('<p class="text-danger">Semua siswa sudah melakukan presensi atau tidak aktif pada tanggal ini.</p>');
                }
            },
            error: function() {
                $('#izin_loading').hide();
                $('#izin_info').show().html('<p class="text-danger">Gagal memuat data siswa. Coba lagi.</p>');
            }
        });
    }

    // Panggil fungsi saat tanggal berubah
    $('#izin_tanggal').on('change', fetchAvailableStudents);

    // Panggil fungsi saat modal pertama kali dibuka
    $('#izinModal').on('shown.bs.modal', function () {
        fetchAvailableStudents();
    });
});
</script>
@stop
