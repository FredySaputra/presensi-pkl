<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Presensi PKL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link rel="icon" href="{{asset('logo/lab.png')}}">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f5f7fa; height: 100vh; overflow: hidden; }
        .container-fluid { display: flex; height: 100vh; padding: 2rem; gap: 2rem; }
        .main-panel, .attendee-panel { flex: 1; display: flex; flex-direction: column; }
        .card { border-radius: 20px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); border: none; background: #ffffff; }
        .clock { font-size: 5rem; font-weight: 700; color: #1a1a1a; margin: 0.5rem 0; letter-spacing: -2px; }
        .date-text { font-size: 1.2rem; color: #6c757d; font-weight: 600; }
        .status-message { min-height: 160px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-radius: 20px; transition: all 0.4s ease; }
        .attendee-list { overflow-y: auto; flex-grow: 1; padding: 1rem; }
        .nav-link { font-weight: 600; color: #495057; border: none !important; }
        .nav-link.active { color: #0d6efd !important; border-bottom: 3px solid #0d6efd !important; background: transparent !important; }
        @media (max-width: 992px) { .container-fluid { flex-direction: column; height: auto; overflow-y: auto; } body { overflow-y: auto; } }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="main-panel">
            <div class="card p-5 text-center h-100 justify-content-center">
                <div class="date-text" id="date">Memuat Tanggal...</div>
                <div class="clock" id="clock">00:00:00</div>

                <div id="status" class="alert alert-light border status-message shadow-sm mt-3 mb-4">
                    <i class="fa-solid fa-hand-pointer fa-3x mb-2 text-primary"></i>
                    <h4 class="fw-bold mb-0">Pilih Nama Untuk Kehadiran</h4>
                </div>

                <div class="text-start bg-light p-4 rounded-4 border">
                    @if($sekolahs->isEmpty())
                        <div class="alert alert-success text-center mb-0 border-0 shadow-sm rounded-4 py-4">
                            <i class="fa-solid fa-check-circle fa-3x mb-3 text-success"></i>
                            <h4 class="fw-bold mb-0">Semua siswa telah presensi!</h4>
                            <p class="text-muted mt-2">Tidak ada siswa tersisa untuk mencatat jam masuk.</p>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">1. Asal Sekolah</label>
                            <select id="sel_sekolah" class="form-select form-select-lg shadow-sm">
                                <option value="">-- Pilih Sekolah --</option>
                                @foreach($sekolahs as $s)
                                    <option value="{{ $s->id }}">{{ $s->nama_sekolah }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted">2. Nama Siswa</label>
                            <select id="sel_siswa" class="form-select form-select-lg shadow-sm" disabled>
                                <option value="">Pilih sekolah terlebih dahulu</option>
                            </select>
                        </div>
                        <button id="btn_submit_manual" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm" disabled style="border-radius: 12px;">
                            <i class="fas fa-sign-in-alt me-2"></i> KONFIRMASI HADIR (MASUK)
                        </button>
                    @endif
                </div>

                <div id="student-info" class="mt-3" style="min-height: 80px;"></div>
            </div>
        </div>

        <div class="attendee-panel card">
            <div class="card-header bg-white pt-4 px-4 border-0">
                <h5 class="fw-bold text-dark mb-3">Monitoring Kehadiran Hari Ini</h5>
                <ul class="nav nav-tabs card-header-tabs" id="schoolTabs" role="tablist"></ul>
            </div>
            <div class="card-body p-0 attendee-list">
                <div class="tab-content" id="schoolTabsContent"></div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        function updateTime() {
            const now = new Date();
            $('#clock').text(now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'}));
            $('#date').text(now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));
        }
        setInterval(updateTime, 1000); updateTime();

        function formatWaktuMenit(totalMenit) {
            if (!totalMenit || totalMenit <= 0) return '0 Menit';
            let jam = Math.floor(totalMenit / 60);
            let menit = totalMenit % 60;
            let teks = [];
            if (jam > 0) teks.push(jam + ' Jam');
            if (menit > 0) teks.push(menit + ' Menit');
            return teks.join(' ');
        }

        function renderTabs(data, targetActiveId = null) {
            const tabs = $('#schoolTabs').empty();
            const content = $('#schoolTabsContent').empty();

            if (!data || Object.keys(data).length === 0) {
                tabs.append('<li class="nav-item"><a class="nav-link active">Daftar Hadir</a></li>');
                content.append('<div class="p-5 text-center text-muted"><p>Belum ada siswa yang hadir hari ini.</p></div>');
                return;
            }

            const schoolIds = Object.keys(data);
            let currentActiveTab = $('#schoolTabs .nav-link.active').attr('href')?.replace('#pane-', '');
            let activeId = targetActiveId || (data[currentActiveTab] ? currentActiveTab : schoolIds[0]);

            schoolIds.forEach(id => {
                let isActive = (id == activeId);
                tabs.append(`<li class="nav-item">
                    <a class="nav-link ${isActive ? 'active' : ''}" data-bs-toggle="tab" href="#pane-${id}">${data[id].nama_sekolah}</a>
                </li>`);

                let rows = data[id].siswa.map(p => {
                    let badgeClass = (p.status == 'Kurang' || p.status == 'Pulang Cepat' || p.status == 'Telat') ? 'bg-warning text-dark' : (p.jam_pulang ? 'bg-success' : 'bg-primary');
                    let timeText = p.jam_pulang ? 'Pulang ' + p.jam_pulang.substring(0, 5) : 'Hadir ' + p.jam_masuk.substring(0, 5);

                    if(p.status === 'Telat') {
                        timeText = `Telat ${formatWaktuMenit(p.menit_telat)} | ${timeText}`;
                    } else if (p.status === 'Pulang Cepat') {
                        timeText = `Cepat ${formatWaktuMenit(p.menit_pulang_cepat)} | ${timeText}`;
                    } else if(p.status === 'Kurang') {
                        timeText = `Kurang | ${timeText}`;
                    }

                    let actionBtn = '';
                    if (!p.jam_pulang) {
                        actionBtn = `<button class="btn btn-sm btn-outline-danger me-2 fw-bold btn-pulang" data-id="${p.siswa_id}"><i class="fas fa-sign-out-alt"></i> Pulang</button>`;
                    } else {
                        actionBtn = `<button class="btn btn-sm btn-outline-warning me-2 fw-bold btn-edit-pulang" data-id="${p.id}"><i class="fas fa-sync-alt"></i> Update Pulang</button>`;
                    }

                    return `
                    <li class="list-group-item d-flex justify-content-between align-items-center mb-2 border-0 bg-light rounded-3 shadow-sm py-3">
                        <div class="fw-bold text-secondary">${p.siswa.nama_siswa}</div>
                        <div class="d-flex align-items-center">
                            ${actionBtn}
                            <span class="badge ${badgeClass} rounded-pill px-3 py-2">${timeText}</span>
                        </div>
                    </li>`;
                }).join('');

                content.append(`<div class="tab-pane fade ${isActive ? 'show active' : ''}" id="pane-${id}" role="tabpanel">
                    <ul class="list-group list-group-flush p-3">${rows}</ul>
                </div>`);
            });
        }

        $(document).ready(() => {
            renderTabs({!! json_encode($daftarHadir ?? []) !!});
        });

        const handlePresence = (payload, url) => {
            const statusBox = $('#status');
            const btnSubmit = $('#btn_submit_manual');

            btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Memproses...');
            statusBox.removeClass().addClass('alert alert-info status-message border-0 shadow-sm').html('<div class="spinner-border text-primary"></div>');

            axios.post(url, {...payload, _token: '{{ csrf_token() }}'})
            .then(res => {
                const color = res.data.status_class;
                const icon = (color === 'success') ? 'check-circle' : 'exclamation-circle';

                statusBox.removeClass().addClass(`alert alert-${color} status-message border-0 shadow-sm`)
                         .html(`<i class="fa-solid fa-${icon} fa-3x mb-3"></i><h4 class="fw-bold">${res.data.message}</h4>`);

                if(res.data.student) {
                    $('#student-info').html(`
                        <div class="p-3 bg-white border rounded-4 w-100 shadow-sm">
                            <h5 class="mb-1 fw-bold">${res.data.student.nama_siswa}</h5>
                            <span class="text-muted small"><i class="fas fa-school me-1"></i>${res.data.student.sekolah.nama_sekolah}</span>
                        </div>`);
                }

                if (res.data.daftarHadir && res.data.sekolah_id) {
                    renderTabs(res.data.daftarHadir, res.data.sekolah_id);
                }

                $('#sel_sekolah').val('');
                $('#sel_siswa').prop('disabled', true).val('').html('<option>Pilih sekolah dulu</option>');

                axios.get('{{ route("presensi.sekolahAktif") }}').then(r => {
                    let options = '<option value="">-- Pilih Sekolah --</option>';
                    if (r.data.length > 0) {
                        options += r.data.map(s => `<option value="${s.id}">${s.nama_sekolah}</option>`).join('');
                    } else {
                        options = '<option value="">Semua sekolah selesai presensi</option>';
                    }
                    $('#sel_sekolah').html(options);
                });
            })
            .catch(err => {
                let msg = err.response ? err.response.data.message : 'Koneksi Gagal!';
                statusBox.removeClass().addClass('alert alert-danger status-message border-0 shadow-sm')
                         .html(`<i class="fa-solid fa-circle-xmark fa-3x mb-3"></i><h4 class="fw-bold">${msg}</h4>`);
            })
            .finally(() => {
                btnSubmit.html('<i class="fas fa-sign-in-alt me-2"></i> KONFIRMASI HADIR (MASUK)');
                setTimeout(() => {
                    statusBox.removeClass().addClass('alert alert-light border status-message shadow-sm')
                         .html('<i class="fa-solid fa-hand-pointer fa-3x mb-2 text-primary"></i><h4 class="fw-bold mb-0">Pilih Nama Untuk Kehadiran</h4>');
                    $('#student-info').empty();
                }, 5000);
            });
        };

        $(document).on('click', '.btn-pulang', function() {
            const siswaId = $(this).data('id');
            $(this).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            handlePresence({siswa_id: siswaId}, '{{ route("presensi.pulang") }}');
        });

        $('#sel_sekolah').on('change', function() {
            const sid = $(this).val();
            const sStudent = $('#sel_siswa');
            if(!sid) {
                $('#btn_submit_manual').prop('disabled', true);
                return sStudent.prop('disabled', true).html('<option>Pilih sekolah dulu</option>');
            }

            sStudent.html('<option>Memuat...</option>');
            axios.get(`/presensi/siswa-by-sekolah/${sid}`).then(r => {
                if (r.data.length === 0) {
                    sStudent.prop('disabled', true).html('<option value="">Semua siswa sekolah ini telah presensi</option>');
                } else {
                    sStudent.prop('disabled', false).html('<option value="">-- Pilih Nama Anda --</option>' + r.data.map(s => `<option value="${s.id}">${s.nama_siswa}</option>`).join(''));
                }
            });
        });

        $('#sel_siswa').on('change', function() { $('#btn_submit_manual').prop('disabled', !$(this).val()); });

        $('#btn_submit_manual').on('click', () => {
            handlePresence({siswa_id: $('#sel_siswa').val()}, '{{ route("presensi.manual") }}');
        });

        $(document).on('click', '.btn-edit-pulang', function() {
            $(this).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            let id = $(this).data('id');
            const statusBox = $('#status');

            axios.post('{{ route("presensi.updatePulang") }}', {
                presensi_id: id,
                _token: '{{ csrf_token() }}'
            }).then(res => {
                const color = res.data.status_class;
                const icon = (color === 'success') ? 'check-circle' : 'exclamation-circle';

                statusBox.removeClass().addClass(`alert alert-${color} status-message border-0 shadow-sm`)
                         .html(`<i class="fa-solid fa-${icon} fa-3x mb-3"></i><h4 class="fw-bold">${res.data.message}</h4>`);

                if (res.data.daftarHadir && res.data.sekolah_id) {
                    renderTabs(res.data.daftarHadir, res.data.sekolah_id);
                }

                setTimeout(() => {
                    statusBox.removeClass().addClass('alert alert-light border status-message shadow-sm')
                             .html('<i class="fa-solid fa-hand-pointer fa-3x mb-2 text-primary"></i><h4 class="fw-bold mb-0">Pilih Nama Untuk Kehadiran</h4>');
                }, 4000);

            }).catch(e => {
                alert('Terjadi kesalahan sistem saat memperbarui jam pulang.');
                $(this).html('<i class="fas fa-sync-alt"></i> Update Pulang').prop('disabled', false);
            });
        });

        setInterval(() => {
            axios.get('{{ route("presensi.data") }}').then(res => {
                if (res.data && res.data.daftarHadir) { renderTabs(res.data.daftarHadir); }
                else if (res.data) { renderTabs(res.data); }
            }).catch(e => console.error("Gagal refresh:", e));
        }, 360000);
    </script>
</body>
</html>
