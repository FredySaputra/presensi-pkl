@extends('adminlte::page')

@section('title', 'Laporan Presensi')

@section('content_header')
    <h1>Laporan Presensi</h1>
@stop

@section('content')
    {{-- Pesan Sukses/Error --}}
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

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.laporan.index') }}" method="GET" class="form-inline">
                <div class="form-group mb-2">
                    <label for="start_date" class="mr-2">Dari:</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="form-group mx-sm-3 mb-2">
                    <label for="end_date" class="mr-2">Sampai:</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="form-group mx-sm-3 mb-2">
                    <label for="sekolah_id" class="mr-2">Sekolah:</label>
                    <select name="sekolah_id" class="form-control">
                        <option value="">Semua Sekolah</option>
                        @foreach($sekolahs as $sekolah)
                            <option value="{{ $sekolah->id }}" {{ $sekolahId == $sekolah->id ? 'selected' : '' }}>
                                {{ $sekolah->nama_sekolah }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Input Pencarian --}}
                <div class="form-group mx-sm-3 mb-2">
                    <label for="search" class="mr-2">Cari Siswa:</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama Siswa" value="{{ $search ?? '' }}">
                </div>
                <button type="submit" class="btn btn-primary mb-2">Filter</button>
            </form>

            <div class="mt-2">
                <form action="{{ route('admin.laporan.cetak_pdf') }}" method="POST" class="d-inline" target="_blank">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
                    <input type="hidden" name="search" value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-success"><i class="fas fa-print"></i> Cetak ke PDF</button>
                </form>
                <form action="{{ route('admin.laporan.cetak_excel') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                    <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
                    <input type="hidden" name="search" value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-excel"></i> Ekspor ke Excel</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Data Presensi</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#manualModal">
                    <i class="fas fa-edit"></i> Presensi Manual
                </button>
                <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#izinModal">
                    <i class="fas fa-plus"></i> Catat Izin Hari Ini
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
                    @forelse ($presensis as $presensi)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($presensi->tanggal)->isoFormat('dddd, D MMM Y') }}</td>
                            <td>{{ $presensi->siswa->nama_siswa ?? 'Siswa Dihapus' }}</td>
                            <td>{{ $presensi->siswa->sekolah->nama_sekolah ?? 'Sekolah Dihapus' }}</td>
                            <td>
                                @if($presensi->status == 'Hadir')
                                    <span class="badge badge-success">Hadir</span>
                                @elseif($presensi->status == 'Izin')
                                    <span class="badge badge-info">Izin</span>
                                @else
                                    <span class="badge badge-secondary">Alpa</span>
                                @endif
                            </td>
                            <td>{{ $presensi->jam_masuk ? \Carbon\Carbon::parse($presensi->jam_masuk)->format('H:i:s') : '-' }}</td>
                            <td>{{ $presensi->jam_pulang ? \Carbon\Carbon::parse($presensi->jam_pulang)->format('H:i:s') : '-' }}</td>
                            <td>
                                @php
                                    $keterangan = $presensi->keterangan ?? '';
                                    if ($presensi->status == 'Hadir') {
                                        $jamMasuk = \Carbon\Carbon::parse($presensi->jam_masuk);
                                        $jamPulang = $presensi->jam_pulang ? \Carbon\Carbon::parse($presensi->jam_pulang) : null;
                                        $batasMasuk = \Carbon\Carbon::createFromTimeString('09:00:59');
                                        $batasPulang = \Carbon\Carbon::createFromTimeString('15:00:00');
                                        $keterangan_list = [];
                                        if ($jamMasuk->isAfter($batasMasuk)) {
                                            $totalMenitTelat = $jamMasuk->diffInMinutes($batasMasuk);
                                            $jamTelat = floor($totalMenitTelat / 60);
                                            $menitSisa = $totalMenitTelat % 60;
                                            $keteranganTelat = 'Telat ';
                                            if ($jamTelat > 0) {
                                                $keteranganTelat .= $jamTelat . ' jam ';
                                            }
                                            if ($menitSisa > 0) {
                                                $keteranganTelat .= $menitSisa . ' menit';
                                            }
                                            $keterangan_list[] = trim($keteranganTelat);
                                        }
                                        if ($jamPulang && $jamPulang->isBefore($batasPulang)) {
                                            $totalMenitPulangCepat = $batasPulang->diffInMinutes($jamPulang);
                                            $jamPulangCepat = floor($totalMenitPulangCepat / 60);
                                            $menitSisaPulang = $totalMenitPulangCepat % 60;
                                            $keteranganPulang = 'Pulang cepat ';
                                            if ($jamPulangCepat > 0) {
                                                $keteranganPulang .= $jamPulangCepat . ' jam ';
                                            }
                                            if ($menitSisaPulang > 0) {
                                                $keteranganPulang .= $menitSisaPulang . ' menit';
                                            }
                                            $keterangan_list[] = trim($keteranganPulang);
                                        }
                                        $keterangan = implode(', ', $keterangan_list);
                                    }
                                @endphp
                                {{ $keterangan ?: '-' }}
                            </td>
                            <td>
                                <a href="{{ route('admin.presensi.edit', $presensi->id) }}" class="btn btn-warning btn-xs">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data presensi yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $presensis->appends(request()->query())->links() }}
        </div>
    </div>

    {{-- Modal Izin --}}
    <div class="modal fade" id="izinModal" tabindex="-1" role="dialog" aria-labelledby="izinModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="izinModalLabel">Catat Izin Siswa (Hari Ini)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.laporan.izin') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="siswa_id">Pilih Siswa</label>
                            <select name="siswa_id" id="siswa_id" class="form-control" required>
                                <option value="">-- Pilih Siswa yang Belum Hadir --</option>
                                @forelse($siswaBelumHadir as $siswa)
                                    <option value="{{ $siswa->id }}">{{ $siswa->nama_siswa }} ({{ $siswa->sekolah->nama_sekolah }})</option>
                                @empty
                                    <option value="" disabled>Semua siswa sudah tercatat hadir/izin hari ini.</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="keterangan">Keterangan Izin</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Sakit, Acara Keluarga" required>
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
                                @foreach($allSiswa as $siswa)
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
                            <input type="time" name="jam_masuk" class="form-control" required>
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
