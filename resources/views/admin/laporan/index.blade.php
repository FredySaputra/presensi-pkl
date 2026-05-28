@extends('adminlte::page')

@section('title', 'Laporan Presensi')

@section('content_header')
    <h1>Laporan Presensi</h1>
@stop

@section('content')
    {{-- Notifikasi Sukses --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    {{-- Notifikasi Error List (Jika ada siswa yang gagal izin WA) --}}
    @if(session('error_list'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5><i class="icon fas fa-exclamation-triangle"></i> Perhatian!</h5>
            Beberapa data tidak tersimpan karena aturan batas izin WA:
            <ul>
                @foreach(session('error_list') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    {{-- Notifikasi Error Biasa --}}
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
                            <label>Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="{{ $tanggalMulai }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" value="{{ $tanggalSelesai }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Filter Sekolah</label>
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
                            <label>Cari Nama</label>
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
                    <i class="fas fa-user-check"></i> Catat Izin Massal
                </button>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#manualModal">
                    <i class="fas fa-edit"></i> Presensi Manual Massal
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
                            <td>{{ $presensi->jam_masuk ? \Carbon\Carbon::parse($presensi->jam_masuk)->format('H:i') : '-' }}</td>
                            <td>{{ $presensi->jam_pulang ? \Carbon\Carbon::parse($presensi->jam_pulang)->format('H:i') : '-' }}</td>
                            <td>
                                {{ $presensi->keterangan }}
                                @if($presensi->status == 'Izin' && $presensi->metode_izin)
                                    <br><small class="text-muted">Via: {{ $presensi->metode_izin }}</small>
                                @endif
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

    <div class="modal fade" id="izinModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Catat Izin Siswa Massal</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="{{ route('admin.laporan.izin') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tanggal Izin</label>
                                    <input type="date" id="izin_tanggal" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Sakit" required>
                                </div>
                            </div>
                        </div>

                        {{-- Pilihan Metode Izin --}}
                        <div class="form-group bg-light p-2 border rounded">
                            <label class="d-block">Metode Izin:</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="metode_izin" id="metode_wa" value="WA" checked>
                                <label class="form-check-label" for="metode_wa">WhatsApp (Maksimal 3x/Bulan)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="metode_izin" id="metode_surat" value="Surat">
                                <label class="form-check-label" for="metode_surat">Surat Fisik (Tanpa Limit)</label>
                            </div>
                        </div>

                        <hr>
                        <label>Pilih Siswa (Hanya yang belum presensi):</label>
                        <input type="text" id="searchIzin" class="form-control mb-2" placeholder="Cari nama siswa...">
                        <div id="loadingIzin" class="text-center d-none"><div class="spinner-border spinner-border-sm"></div> Memuat...</div>
                        <div id="izin-list" style="height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan Izin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Presensi Manual Massal -->
    <div class="modal fade" id="manualModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Presensi Manual (Checkbox)</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="{{ route('admin.laporan.manual') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                            <div class="col-md-4"><label>Jam Masuk</label><input type="time" name="jam_masuk" class="form-control" value="08:00"></div>
                            <div class="col-md-4"><label>Jam Pulang</label><input type="time" name="jam_pulang" class="form-control" value="16:00"></div>
                        </div>
                        <hr>
                        <label>Pilih Siswa:</label>
                        <input type="text" id="searchManual" class="form-control mb-2" placeholder="Cari nama siswa...">
                        <div id="manual-list" style="height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                            @foreach($semuaSiswa as $siswa)
                                <div class="form-check item-manual">
                                    <input class="form-check-input" type="checkbox" name="siswa_ids[]" value="{{ $siswa->id }}" id="m_{{ $siswa->id }}">
                                    <label class="form-check-label" for="m_{{ $siswa->id }}">
                                        {{ $siswa->nama_siswa }} ({{ $siswa->sekolah->nama_sekolah }})
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Simpan Presensi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {

    function fetchIzinStudents() {
        let tgl = $('#izin_tanggal').val();
        $('#loadingIzin').removeClass('d-none');
        $('#izin-list').empty();

        $.get('{{ route("admin.laporan.getSiswa") }}', { tanggal: tgl }, function(data) {
            $('#loadingIzin').addClass('d-none');
            if(data.length > 0) {
                data.forEach(s => {
                    let badge = '';
                    if (s.jumlah_izin_wa >= 3) {
                        badge = `<span class="badge badge-danger ml-2">Limit WA Habis (${s.jumlah_izin_wa}/3)</span>`;
                    } else {
                        badge = `<span class="badge badge-success ml-2">WA: ${s.jumlah_izin_wa}/3</span>`;
                    }

                    $('#izin-list').append(`
                        <div class="form-check item-izin border-bottom py-2">
                            <input class="form-check-input" type="checkbox" name="siswa_ids[]" value="${s.id}" id="i_${s.id}">
                            <label class="form-check-label w-100" for="i_${s.id}">
                                ${s.nama_siswa} <br>
                                <small class="text-muted">${s.sekolah.nama_sekolah}</small>
                                ${badge}
                            </label>
                        </div>
                    `);
                });
            } else {
                $('#izin-list').html('<p class="text-muted text-center pt-3">Tidak ada siswa yang bisa diizinkan tanggal ini.</p>');
            }
        });
    }

    $('#izin_tanggal').on('change', fetchIzinStudents);
    $('#izinModal').on('shown.bs.modal', fetchIzinStudents);

    $('#searchIzin').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.item-izin').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

    $('#searchManual').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.item-manual').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
        });
    });

    $('form[action="{{ route("admin.laporan.izin") }}"]').on('submit', function(e) {
        let metode = $('input[name="metode_izin"]:checked').val();
        if (metode === 'WA') {
            let errorFound = false;
            // Cek setiap checkbox yang dipilih
            $('#izin-list input:checked').each(function() {
                let labelText = $(this).next('label').text();
                if (labelText.includes('Limit WA Habis')) {
                    errorFound = true;
                    return false; 
                }
            });

            if (errorFound) {
                e.preventDefault();
                alert('Gagal! Ada siswa terpilih yang sudah habis limit izin WA (3x). Silakan ganti metode ke "Surat Fisik" atau hapus centang pada siswa tersebut.');
            }
        }
    });
});
</script>
@stop
