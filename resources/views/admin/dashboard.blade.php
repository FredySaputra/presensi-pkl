@extends('adminlte::page')

@section('title', 'Dashboard Admin')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    @if(request('sync_success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-check"></i> Berhasil!</h5>
            Data berhasil disinkronkan ke server secara otomatis!
        </div>
    @endif

    <div class="mb-3 text-right">
        <a href="{{ route('admin.sync-live') }}" class="btn btn-primary">
            <i class="fas fa-sync"></i> Sinkronisasi Data ke Server (Infinity Free)
        </a>
    </div>

    {{-- Baris untuk Kartu Statistik --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            {{-- Kartu Total Siswa Aktif --}}
            <div class="small-box bg-info">
                <div class="inner">
                    {{-- Pastikan variabel ini ada --}}
                    <h3>{{ $totalSiswaAktif ?? 0 }}</h3>
                    <p>Total Siswa PKL Aktif</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('admin.siswa.index') }}" class="small-box-footer">
                    Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            {{-- Kartu Jumlah Hadir --}}
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $jumlahHadir ?? 0 }}</h3>
                    <p>Hadir Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <a href="{{ route('admin.laporan.index') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            {{-- Kartu Jumlah Izin --}}
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $jumlahIzin ?? 0 }}</h3>
                    <p>Izin Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-clock"></i>
                </div>
                <a href="{{ route('admin.laporan.index') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            {{-- Kartu Jumlah Alpa --}}
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $jumlahAlpa ?? 0 }}</h3>
                    <p>Alpa Hari Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-times"></i>
                </div>
                <a href="{{ route('admin.laporan.index') }}" class="small-box-footer">
                    Lihat Laporan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Baris untuk Tabel Siswa Terlambat --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Siswa Terlambat Hari Ini ({{ \Carbon\Carbon::today()->isoFormat('dddd, D MMMM Y') }})</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Nama Siswa</th>
                                <th>Asal Sekolah</th>
                                <th>Jam Masuk</th>
                                <th>Keterlambatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($siswaTerlambat ?? [] as $presensi)
                                @php
                                    $jamMasuk = \Carbon\Carbon::parse($presensi->jam_masuk);
                                    $batasMasuk = \Carbon\Carbon::createFromTimeString('09:00:59');
                                    $keterlambatan = $jamMasuk->diffForHumans($batasMasuk, true);
                                @endphp
                                <tr>
                                    <td>{{ $presensi->siswa->nama_siswa ?? '-' }}</td>
                                    <td>{{ $presensi->siswa->sekolah->nama_sekolah ?? '-' }}</td>
                                    <td><span class="badge badge-danger">{{ $jamMasuk->format('H:i:s') }}</span></td>
                                    <td>{{ $keterlambatan }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada siswa yang terlambat hari ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script>
    // JS Cronjob: Sinkronisasi Otomatis jam 09:45
    (function() {
        var isHoliday = {{ $isHoliday ? 'true' : 'false' }};
        var hasSyncedToday = localStorage.getItem('last_auto_sync_date');
        
        function checkCron() {
            var now = new Date();
            var hours = now.getHours();
            var minutes = now.getMinutes();
            var dateStr = now.toISOString().split('T')[0];
            
            // Periksa jika jam 09:45
            if (hours === 9 && minutes === 45) {
                if (hasSyncedToday !== dateStr) {
                    // Tandai sudah sync hari ini
                    localStorage.setItem('last_auto_sync_date', dateStr);
                    hasSyncedToday = dateStr;
                    
                    if (!isHoliday) {
                        console.log("Menjalankan cronjob sinkronisasi jam 09:45...");
                        // Trigger tombol sync
                        window.location.href = "{{ route('admin.sync-live') }}";
                    } else {
                        console.log("Hari ini libur, membatalkan cronjob sinkronisasi.");
                    }
                }
            }
        }
        
        // Cek setiap 30 detik
        setInterval(checkCron, 30000);
        checkCron();
    })();
</script>
@stop
