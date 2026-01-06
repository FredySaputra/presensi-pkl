<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Presensi PKL</title>
    <!-- Dependencies: Bootstrap 5, Poppins Font, FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f0f2f5; 
            height: 100vh; 
            overflow: hidden; 
        }
        .container-fluid { 
            display: flex; 
            height: 100vh; 
            padding: 2rem; 
            gap: 2rem; 
        }
        .main-panel, .attendee-panel { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
        }
        .card { 
            border-radius: 25px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); 
            border: none; 
            background: #ffffff; 
        }
        .clock { 
            font-size: 5rem; 
            font-weight: 700; 
            color: #1a1a1a; 
            margin: 0.5rem 0; 
            letter-spacing: -2px;
        }
        .date-text {
            font-size: 1.2rem;
            color: #6c757d;
            font-weight: 600;
        }
        .status-message { 
            min-height: 160px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            border-radius: 20px; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .attendee-list { 
            overflow-y: auto; 
            flex-grow: 1; 
            padding: 1rem;
        }
        .focus-indicator-box { 
            padding: 15px; 
            border-radius: 12px; 
            cursor: pointer; 
            text-align: center; 
            font-weight: bold; 
            margin-top: 1.5rem; 
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .not-focused { background-color: #fff3cd; color: #856404; border: 2px dashed #ffeeba; }
        .is-focused { background-color: #d1e7dd; color: #0f5132; border: 2px solid #badbcc; }
        
        .nav-tabs { border-bottom: 2px solid #dee2e6; }
        .nav-link { font-weight: 600; color: #495057; border: none !important; padding: 1rem 1.5rem; }
        .nav-link.active { color: #0d6efd !important; border-bottom: 3px solid #0d6efd !important; background: transparent !important; }
        
        .list-group-item {
            border: none;
            margin-bottom: 0.5rem;
            border-radius: 12px !important;
            background: #f8f9fa;
            transition: transform 0.2s;
        }
        .list-group-item:hover { transform: translateX(5px); background: #f1f3f5; }

        @media (max-width: 992px) { 
            .container-fluid { flex-direction: column; height: auto; overflow-y: auto; }
            body { overflow-y: auto; }
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <!-- Area Kiri: Interaksi Presensi -->
        <div class="main-panel">
            <div class="card p-5 text-center h-100 justify-content-center">
                <div class="date-text" id="date">Memuat Tanggal...</div>
                <div class="clock" id="clock">00:00:00</div>
                
                {{-- Form RFID (Input Tersembunyi) --}}
                <form id="presensi-form">
                    <input type="text" id="id_kartu" style="opacity: 0; position: absolute; left: -9999px;" autofocus autocomplete="off">
                </form>

                <div id="status" class="alert alert-light border status-message shadow-sm mt-4">
                    <i class="fa-solid fa-id-card fa-4x mb-3 text-primary"></i>
                    <h4 class="fw-bold mb-0">Silakan Tempelkan Kartu RFID</h4>
                </div>

                <div id="student-info" class="mt-4" style="min-height: 80px;">
                    {{-- Diisi info siswa saat sukses scan --}}
                </div>

                <div class="d-grid gap-3">
                    <button class="btn btn-outline-primary btn-lg py-3 fw-bold" data-bs-toggle="modal" data-bs-target="#manualModal">
                        <i class="fas fa-keyboard me-2"></i> Presensi Manual (Pilih Nama)
                    </button>
                </div>

                <div id="focus-indicator" class="focus-indicator-box">
                    <span>Mengecek Scanner...</span>
                </div>
            </div>
        </div>

        <!-- Area Kanan: Monitoring Kehadiran SMK -->
        <div class="attendee-panel card">
            <div class="card-header bg-white pt-4 px-4 border-0">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="fas fa-chart-line me-2 text-primary"></i> Monitoring Kehadiran Hari Ini
                </h5>
                <ul class="nav nav-tabs card-header-tabs" id="schoolTabs" role="tablist"></ul>
            </div>
            <div class="card-body p-0 attendee-list">
                <div class="tab-content" id="schoolTabsContent"></div>
            </div>
        </div>
    </div>

    <!-- Modal Presensi Manual -->
    <div class="modal fade" id="manualModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="modal-title fw-bold">Pilih Nama Presensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-muted">Asal Sekolah</label>
                        <select id="sel_sekolah" class="form-select form-select-lg">
                            <option value="">-- Pilih Sekolah --</option>
                            @foreach($sekolahs as $s)
                                <option value="{{ $s->id }}">{{ $s->nama_sekolah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Nama Siswa</label>
                        <select id="sel_siswa" class="form-select form-select-lg" disabled>
                            <option value="">Pilih sekolah terlebih dahulu</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button id="btn_submit_manual" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm" disabled style="border-radius: 15px;">
                        KONFIRMASI KEHADIRAN
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts: JQuery, Bootstrap, Axios -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // Update Jam dan Tanggal Real-time
        function updateTime() {
            const now = new Date();
            $('#clock').text(now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'}));
            $('#date').text(now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));
        }
        setInterval(updateTime, 1000); 
        updateTime();

        // Fungsi Render Tabs Monitoring
        function renderTabs(data, activeId = null) {
            const tabs = $('#schoolTabs').empty();
            const content = $('#schoolTabsContent').empty();
            const schoolIds = Object.keys(data);

            if (schoolIds.length === 0) {
                tabs.append('<li class="nav-item"><a class="nav-link active">Daftar Hadir</a></li>');
                content.append('<div class="p-5 text-center text-muted"><i class="fas fa-clipboard-question fa-3x mb-3"></i><p>Belum ada data hadir hari ini.</p></div>');
                return;
            }

            let firstTabId = activeId || schoolIds[0];

            schoolIds.forEach(id => {
                let isActive = (id == firstTabId);
                tabs.append(`<li class="nav-item">
                    <a class="nav-link ${isActive ? 'active' : ''}" data-bs-toggle="tab" href="#p-${id}" role="tab">${data[id].nama_sekolah}</a>
                </li>`);
                
                let studentList = data[id].siswa.map(p => {
                    let badgeColor = 'primary';
                    let statusText = 'Hadir ' + p.jam_masuk.substring(0, 5);
                    
                    if (p.jam_pulang) {
                        badgeColor = (p.status === 'Kurang') ? 'warning text-dark' : 'success';
                        statusText = 'Pulang ' + p.jam_pulang.substring(0, 5);
                    }
                    
                    return `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="fw-bold">${p.siswa.nama_siswa}</div>
                        <span class="badge bg-${badgeColor} rounded-pill px-3 py-2">${statusText}</span>
                    </li>`;
                }).join('');

                content.append(`<div class="tab-pane fade ${isActive ? 'show active' : ''}" id="p-${id}" role="tabpanel">
                    <ul class="list-group list-group-flush">${studentList}</ul>
                </div>`);
            });
        }

        // Manajemen Fokus Scanner RFID
        const rfidInput = $('#id_kartu');
        const focusIndicator = $('#focus-indicator');
        const forceFocus = () => rfidInput.focus();
        
        const updateFocusUI = () => {
            if(rfidInput.is(':focus')) {
                focusIndicator.removeClass('not-focused').addClass('is-focused').find('span').html('<i class="fas fa-check-circle me-1"></i> SCANNER SIAP');
            } else {
                focusIndicator.removeClass('is-focused').addClass('not-focused').find('span').html('<i class="fas fa-exclamation-triangle me-1"></i> KLIK DI SINI UNTUK SCAN');
            }
        };

        rfidInput.on('focus blur', updateFocusUI); 
        focusIndicator.on('click', forceFocus); 

        // Initial Load
        $(document).ready(() => { 
            renderTabs({!! json_encode($daftarHadir ?? []) !!}); 
            updateFocusUI(); 
            forceFocus();
        });

        // Handler Utama Pengiriman Presensi (RFID & Manual)
        const submitPresence = (payload, url) => {
            const statusBox = $('#status');
            statusBox.removeClass().addClass('alert alert-info status-message border-0 shadow-sm').html('<div class="spinner-grow text-primary" role="status"></div><h5 class="mt-3">Memproses...</h5>');
            
            axios.post(url, {...payload, _token: '{{ csrf_token() }}'})
            .then(res => {
                const color = res.data.status_class;
                const icon = (color === 'success') ? 'check-circle' : 'exclamation-circle';
                
                statusBox.removeClass().addClass(`alert alert-${color} status-message border-0 shadow-sm`)
                         .html(`<i class="fa-solid fa-${icon} fa-3x mb-3"></i><h4 class="fw-bold">${res.data.message}</h4>`);
                
                if(res.data.student) {
                    $('#student-info').html(`
                        <div class="p-3 bg-light border rounded-4 w-100 shadow-sm animate__animated animate__fadeInUp">
                            <h5 class="mb-1 fw-bold">${res.data.student.nama_siswa}</h5>
                            <span class="text-muted small"><i class="fas fa-school me-1"></i>${res.data.student.sekolah.nama_sekolah}</span>
                        </div>`);
                }
                renderTabs(res.data.daftarHadir, res.data.sekolah_id);
            })
            .catch(err => {
                let errorMsg = err.response ? err.response.data.message : 'Koneksi Gagal!';
                statusBox.removeClass().addClass('alert alert-danger status-message border-0 shadow-sm')
                         .html(`<i class="fa-solid fa-circle-xmark fa-3x mb-3"></i><h4 class="fw-bold">${errorMsg}</h4>`);
            })
            .finally(forceFocus);
        };

        // Event RFID Submit
        $('#presensi-form').on('submit', e => { 
            e.preventDefault(); 
            const val = rfidInput.val().trim();
            if(val) submitPresence({id_kartu: val}, '{{ route("presensi.store") }}'); 
            rfidInput.val(''); 
        });

        // Event Manual Selection
        $('#sel_sekolah').on('change', function() {
            const sid = $(this).val();
            const sSiswa = $('#sel_siswa');
            if(!sid) return sSiswa.prop('disabled', true).html('<option>Pilih sekolah dulu</option>');
            
            sSiswa.html('<option>Memuat daftar siswa...</option>');
            axios.get(`/presensi/siswa-by-sekolah/${sid}`).then(r => {
                sSiswa.prop('disabled', false).html('<option value="">-- Cari Nama Anda --</option>' + r.data.map(s => `<option value="${s.id}">${s.nama_siswa}</option>`).join(''));
            });
        });

        $('#sel_siswa').on('change', function() { $('#btn_submit_manual').prop('disabled', !$(this).val()); });

        $('#btn_submit_manual').on('click', () => {
            submitPresence({siswa_id: $('#sel_siswa').val()}, '{{ route("presensi.manual") }}');
            bootstrap.Modal.getInstance($('#manualModal')[0]).hide();
            // Reset modal state
            $('#sel_sekolah').val('').trigger('change');
        });

        // Sinkronisasi Daftar Hadir Otomatis (Interval 10 Detik)
        setInterval(() => { 
            axios.get('{{ route("presensi.data") }}').then(res => {
                const activeTabId = $('#schoolTabs .nav-link.active').attr('href').replace('#p-', '');
                renderTabs(res.data, activeTabId);
            }); 
        }, 10000);
    </script>
</body>
</html>