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
            {{-- Form tersembunyi untuk tombol cetak --}}
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
                                                $diff = $jamMasuk->diff($batasMasuk);
                                                $keterangan = 'Telat ' . ($diff->h > 0 ? $diff->h . ' jam ' : '') . $diff->i . ' menit';
                                            } else {
                                                $keterangan = 'Tepat Waktu';
                                            }
                                        }

                                        if ($presensi->jam_pulang) {
                                            $jamPulang = \Carbon\Carbon::parse($presensi->jam_pulang);
                                            $batasPulang = \Carbon\Carbon::parse($presensi->tanggal . ' 15:00:00');
                                            if ($jamPulang->lt($batasPulang)) {
                                                if($keterangan) $keterangan .= ' | ';
                                                $diff = $jamPulang->diff($batasPulang);
                                                $keterangan .= 'Pulang Awal ' . ($diff->h > 0 ? $diff->h . ' jam ' : '') . $diff->i . ' menit';
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

    <!-- Modal Catat Izin (Massal) -->
    <div class="modal fade" id="izinModal" tabindex="-1" role="dialog" aria-labelledby="izinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="izinModalLabel">Catat Izin Siswa Massal</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.laporan.izin') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="izin_tanggal">Pilih Tanggal Izin</label>
                                    <input type="date" id="izin_tanggal" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="izin_keterangan">Keterangan Izin</label>
                                    <input type="text" id="izin_keterangan" name="keterangan" class="form-control" required placeholder="Contoh: Sakit">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>Pilih Siswa (Hanya yang belum presensi)</label>
                            <input type="text" id="searchSiswaIzin" class="form-control mb-2" placeholder="Cari nama siswa...">
                            <div id="izin_loading" class="text-center my-3" style="display: none;">
                                <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
                                <p>Mencari siswa yang tersedia...</p>
                            </div>
                            <div id="izin-checkbox-list" style="height: 300px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: 5px;">
                                {{-- Daftar siswa akan dimuat oleh JavaScript --}}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Izin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    {{-- Modal Presensi Manual Massal --}}
    <div class="modal fade" id="manualModal" tabindex="-1" role="dialog" aria-labelledby="manualModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manualModalLabel">Input Presensi Manual Massal</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.laporan.manual') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal</label>
                                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="jam_masuk">Jam Masuk</label>
                                    <input type="time" name="jam_masuk" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="jam_pulang">Jam Pulang (Opsional)</label>
                                    <input type="time" name="jam_pulang" class="form-control">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>Pilih Siswa</label>
                            <input type="text" id="searchSiswa" class="form-control mb-2" placeholder="Cari nama siswa...">
                            <div id="siswa-checkbox-list" style="height: 300px; overflow-y: auto; border: 1px solid #ced4da; padding: 10px; border-radius: 5px;">
                                @foreach($semuaSiswa as $siswa)
                                    <div class="form-check siswa-item">
                                        <input class="form-check-input" type="checkbox" name="siswa_ids[]" value="{{ $siswa->id }}" id="siswa_{{ $siswa->id }}">
                                        <label class="form-check-label" for="siswa_{{ $siswa->id }}">
                                            {{ $siswa->nama_siswa }} ({{ $siswa->sekolah->nama_sekolah }})
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Presensi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // --- SCRIPT UNTUK MODAL IZIN ---
    function fetchAvailableStudentsForIzin() {
        var tanggal = $('#izin_tanggal').val();
        if (!tanggal) return;

        $('#izin-checkbox-list').html('');
        $('#izin_loading').show();

        $.ajax({
            url: '{{ route("admin.laporan.getSiswa") }}',
            type: 'GET',
            data: { tanggal: tanggal },
            success: function(data) {
                $('#izin_loading').hide();
                var listContainer = $('#izin-checkbox-list');
                listContainer.empty();

                if (data.length > 0) {
                    $.each(data, function(key, siswa) {
                        var checkboxItem = `
                            <div class="form-check siswa-item-izin">
                                <input class="form-check-input" type="checkbox" name="siswa_ids[]" value="${siswa.id}" id="izin_siswa_${siswa.id}">
                                <label class="form-check-label" for="izin_siswa_${siswa.id}">
                                    ${siswa.nama_siswa} (${siswa.sekolah.nama_sekolah})
                                </label>
                            </div>`;
                        listContainer.append(checkboxItem);
                    });
                } else {
                    listContainer.html('<p class="text-muted text-center">Semua siswa sudah melakukan presensi atau tidak aktif pada tanggal ini.</p>');
                }
            },
            error: function() {
                $('#izin_loading').hide();
                $('#izin-checkbox-list').html('<p class="text-danger text-center">Gagal memuat data siswa. Coba lagi.</p>');
            }
        });
    }

    $('#izin_tanggal').on('change', fetchAvailableStudentsForIzin);
    $('#izinModal').on('shown.bs.modal', function () {
        fetchAvailableStudentsForIzin();
    });

    $('#searchSiswaIzin').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#izin-checkbox-list .siswa-item-izin').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // --- SCRIPT UNTUK MODAL PRESENSI MANUAL ---
    $('#searchSiswa').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#siswa-checkbox-list .siswa-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
});
</script>
@stop

