@extends('adminlte::page')

@section('title', 'Kalender Hari Libur')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Manajemen Hari Libur Nasional & Khusus</h1>
        <form action="{{ route('admin.harilibur.fetchAuto') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-warning" onclick="return confirm('Proses ini akan menarik data Hari Libur Nasional (tanpa cuti bersama) untuk tahun ini dan tahun depan dari Internet. Lanjutkan?')">
                <i class="fas fa-cloud-download-alt"></i> Tarik Tanggal Merah Otomatis
            </button>
        </form>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="card card-primary">
        <div class="card-body p-0">
            <div id="calendar"></div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahLibur" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.harilibur.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title">Tambah Hari Libur</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Tanggal Mulai</label>
                                <input type="date" id="input_tanggal_mulai" name="tanggal_mulai" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Tanggal Selesai</label>
                                <input type="date" id="input_tanggal_selesai" name="tanggal_selesai" class="form-control" required>
                                <small class="text-muted">Biarkan sama jika libur hanya 1 hari</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Keterangan / Nama Libur</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Cth: Cuti Bersama Idul Fitri" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <style>
        /* Modifikasi sedikit kursor agar terlihat bisa diklik */
        .fc-daygrid-day-frame { cursor: pointer; }
        .fc-event { cursor: pointer; }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            // Logic for Date Range Validation in Modal
            const mulaiLibur = $('#input_tanggal_mulai');
            const selesaiLibur = $('#input_tanggal_selesai');

            function updateSelesaiLiburMin() {
                const mulaiValue = mulaiLibur.val();
                if (mulaiValue) {
                    selesaiLibur.prop('disabled', false);
                    selesaiLibur.attr('min', mulaiValue);
                    
                    if (selesaiLibur.val() && selesaiLibur.val() < mulaiValue) {
                        selesaiLibur.val(mulaiValue);
                    }
                } else {
                    selesaiLibur.prop('disabled', true);
                    selesaiLibur.val('');
                }
            }

            mulaiLibur.on('change', updateSelesaiLiburMin);

            // Initial state (modal closed/reset)
            selesaiLibur.prop('disabled', true);

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'id', // Bahasa Indonesia
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: '{{ route("admin.harilibur.events") }}',

                // FITUR: Otomatis mewarnai Hari Minggu
                dayCellDidMount: function(info) {
                    // getDay() mengembalikan nilai 0 untuk hari Minggu
                    if (info.date.getDay() === 0) {
                        // Ubah warna background kotak menjadi merah sangat muda
                        info.el.style.backgroundColor = '#fff0f0';

                        // Ubah angka tanggalnya menjadi warna merah tebal
                        let dayNumber = info.el.querySelector('.fc-daygrid-day-number');
                        if (dayNumber) {
                            dayNumber.style.color = '#dc3545';
                            dayNumber.style.fontWeight = 'bold';
                        }
                    }
                },

                // KETIKA KOTAK TANGGAL KOSONG DIKLIK
                dateClick: function(info) {
                    // Cegah user menambah libur di hari Minggu
                    if (info.date.getDay() === 0) {
                        Swal.fire('Info', 'Hari Minggu sudah otomatis menjadi hari libur.', 'info');
                        return; // Hentikan fungsi agar form tidak terbuka
                    }

                    // Set otomatis input tanggal mulai & selesai sesuai kotak yang diklik
                    mulaiLibur.val(info.dateStr);
                    selesaiLibur.val(info.dateStr);
                    
                    // Trigger validation logic
                    updateSelesaiLiburMin();

                    $('#modalTambahLibur').modal('show');
                },

                // KETIKA KOTAK EVENT (HARI LIBUR) DIKLIK
                eventClick: function(info) {
                    let eventId = info.event.id; // String dari controller (cth: "global_1" atau "sekolah_2")
                    let eventTitle = info.event.title;
                    let eventDate = info.event.start.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

                    // CEK JIKA INI ADALAH LIBUR SEKOLAH OTOMATIS
                    if (eventId.toString().startsWith('sekolah_')) {
                        Swal.fire({
                            title: 'Libur Mingguan Sekolah',
                            html: `<b>${eventTitle}</b><br><br>Ini adalah jadwal libur otomatis dari Master Data Sekolah. Untuk mengubahnya, silakan ke menu Manajemen Sekolah.`,
                            icon: 'info',
                            confirmButtonText: 'Tutup'
                        });
                        return; // Hentikan fungsi agar tidak memunculkan popup hapus
                    }

                    // JIKA INI LIBUR NASIONAL/MANUAL (ID berawalan "global_")
                    let realDatabaseId = eventId.split('_')[1]; // Ambil angka ID aslinya

                    Swal.fire({
                        title: 'Info Hari Libur',
                        html: `<b>Keterangan:</b> ${eventTitle} <br><br> <b>Tanggal:</b> ${eventDate}`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-trash"></i> Hapus Libur Ini',
                        cancelButtonText: 'Tutup'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Proses Hapus Data via AJAX
                            $.ajax({
                                url: `/admin/harilibur/${realDatabaseId}`,
                                type: 'DELETE',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    info.event.remove(); // Hapus kotak dari layar
                                    Swal.fire('Terhapus!', 'Hari libur berhasil dihapus.', 'success');
                                },
                                error: function() {
                                    Swal.fire('Gagal!', 'Terjadi kesalahan sistem saat menghapus data.', 'error');
                                }
                            });
                        }
                    });
                }
            });

            calendar.render();
        });
    </script>
@stop
