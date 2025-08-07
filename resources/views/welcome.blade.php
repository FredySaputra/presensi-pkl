<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Presensi PKL</title>
    <!-- Ganti ke Bootstrap 5 untuk komponen yang lebih modern -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font dari Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Icon dari Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            /* Latar belakang gradien dengan pola SVG halus */
            background-color: #f5f7fa;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23c3cfe2' fill-opacity='0.4'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9z'/%3E%3Cpath d='M6 5V0h1v5h5V0h1v5h5V0h1v5h5V0h1v5h5V0h1v5h5V0h1v5h5V0h1v5h5V0h1v5h5V0h1v5h5V0h1v5h4v1h-4v5h4v1h-4v5h4v1h-4v5h4v1h-4v5h4v1h-4v5h4v1h-4v5h4v1h-4v5h4v1h-4v5h4v1h-4v5h4v1h-4v4h-1v-4h-5v4h-1v-4h-5v4h-1v-4h-5v4h-1v-4h-5v4h-1v-4h-5v4h-1v-4h-5v4h-1v-4h-5v4h-1v-4h-5v4h-1v-4H0v-1h4v-5H0v-1h4v-5H0v-1h4v-5H0v-1h4v-5H0v-1h4v-5H0v-1h4v-5H0v-1h4v-5H0v-1h4v-5H0v-1h4v-5H0v-1h4V0h1v4h5V0h1v4h5V0h1v4h5V0h1v4h5V0h1v4h5V0h1v4h5V0h1v4h5V0h1v4h5V0h1v4h5V0h1v4z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            overflow: hidden;
        }
        .container-fluid {
            display: flex;
            height: 100vh;
            padding: 2rem;
            gap: 2rem;
        }
        .main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .main-card {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            padding: 2rem;
        }
        .clock {
            font-size: 4.5rem;
            font-weight: 700;
            color: #333;
            letter-spacing: 2px;
        }
        .date {
            font-size: 1.2rem;
            font-weight: 600;
            color: #666;
        }
        .status-message {
            min-height: 120px;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .status-message i {
            animation: popIn 0.5s ease;
        }
        .student-info {
            min-height: 60px;
        }
        .focus-indicator-box {
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s, color 0.3s;
            border: 2px solid transparent;
            user-select: none;
        }
        .not-focused {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .is-focused {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .attendee-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            overflow: hidden;
        }
        .attendee-list {
            list-style: none;
            padding: 0;
            margin: 0;
            overflow-y: auto;
            flex-grow: 1;
        }
        .attendee-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            animation: fadeIn 0.5s ease;
            transition: background-color 0.2s ease-in-out;
        }
        .attendee-list li:hover {
            background-color: #f8f9fa;
        }
        .attendee-list li:last-child {
            border-bottom: none;
        }
        .attendee-list .detail-text {
            font-size: 0.8rem;
            font-style: italic;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes popIn {
            0% { transform: scale(0.5); opacity: 0; }
            80% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <!-- Panel Kiri -->
        <div class="main-panel">
            <div class="card text-center main-card">
                <h2 id="date" class="date"></h2>
                <div class="clock" id="clock"></div>
                <hr class="my-4">
                <form id="presensi-form" autocomplete="off">
                    <input type="text" id="id_kartu" class="form-control" style="opacity: 0; position: absolute;" autofocus autocomplete="new-password">
                </form>
                <div id="status" class="alert status-message mt-3">
                    <i class="fa-solid fa-id-card fa-2x mb-2"></i>
                    <h5>Silakan Tempelkan Kartu Anda</h5>
                </div>
                 <div id="student-info" class="student-info mt-2"></div>
                 <div id="focus-indicator" class="focus-indicator-box mt-3">
                    <span></span>
                 </div>
            </div>
        </div>

        <!-- Panel Kanan -->
        <div class="attendee-panel card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="schoolTabs" role="tablist">
                    <!-- Tab navigasi akan dibuat oleh JavaScript -->
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="schoolTabsContent">
                    <!-- Konten tab akan dibuat oleh JavaScript -->
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // Fungsi untuk update jam dan tanggal secara real-time
        function updateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('clock').textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('date').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Fungsi untuk membangun ulang daftar hadir
        function updateTabs(groupedAttendees, activeSchoolId = null) {
            const tabsContainer = $('#schoolTabs');
            const contentContainer = $('#schoolTabsContent');
            tabsContainer.empty();
            contentContainer.empty();

            const schoolIds = Object.keys(groupedAttendees);

            if (schoolIds.length === 0) {
                tabsContainer.html('<li class="nav-item"><a class="nav-link active" href="#">Daftar Hadir</a></li>');
                contentContainer.html('<div class="tab-pane fade show active" role="tabpanel"><ul class="list-group list-group-flush"><li class="list-group-item">Belum ada yang hadir hari ini.</li></ul></div>');
                return;
            }

            let firstSchoolId = activeSchoolId || schoolIds[0];

            // --- FUNGSI UNTUK MENGHITUNG STATUS DETAIL ---
            function getDetailedStatus(jamMasuk, jamPulang) {
                const batasMasuk = new Date();
                batasMasuk.setHours(9, 0, 0, 0);
                const batasPulang = new Date();
                batasPulang.setHours(15, 0, 0, 0);
                let detailText = '';
                let statusBadge = '';
                if (jamPulang) {
                    const waktuPulang = new Date();
                    const [h, m, s] = jamPulang.split(':');
                    waktuPulang.setHours(h, m, s, 0);
                    if (waktuPulang < batasPulang) {
                        const diff = Math.round((batasPulang - waktuPulang) / 60000);
                        detailText = `Pulang ${diff} menit lebih awal`;
                    } else {
                        detailText = 'Pulang Tepat Waktu';
                    }
                    statusBadge = `<span class="badge bg-secondary text-white">Pulang ${jamPulang.substring(0, 5)}</span>`;
                } else {
                    const waktuMasuk = new Date();
                    const [h, m, s] = jamMasuk.split(':');
                    waktuMasuk.setHours(h, m, s, 0);
                    if (waktuMasuk > batasMasuk) {
                        const diff = Math.round((waktuMasuk - batasMasuk) / 60000);
                        detailText = `Telat ${diff} menit`;
                    } else {
                        detailText = 'Tepat Waktu';
                    }
                    statusBadge = `<span class="badge bg-primary text-white">Hadir ${jamMasuk.substring(0, 5)}</span>`;
                }
                return { detailText, statusBadge };
            }

            schoolIds.forEach(schoolId => {
                const attendees = groupedAttendees[schoolId];
                if (!attendees || attendees.length === 0 || !attendees[0].siswa || !attendees[0].siswa.sekolah) return;
                
                const schoolName = attendees[0].siswa.sekolah.nama_sekolah;
                const isActive = schoolId == firstSchoolId;

                const tabLink = `<li class="nav-item"><a class="nav-link ${isActive ? 'active' : ''}" id="tab-${schoolId}" data-bs-toggle="tab" href="#pane-${schoolId}" role="tab">${schoolName}</a></li>`;
                tabsContainer.append(tabLink);

                let listItems = '';
                attendees.forEach(function(presensi) {
                    const { detailText, statusBadge } = getDetailedStatus(presensi.jam_masuk, presensi.jam_pulang);
                    listItems += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${presensi.siswa.nama_siswa}</strong><br>
                                <small class="text-muted detail-text">${detailText}</small>
                            </div>
                            ${statusBadge}
                        </li>`;
                });

                const tabPane = `<div class="tab-pane fade ${isActive ? 'show active' : ''}" id="pane-${schoolId}" role="tabpanel"><ul class="list-group list-group-flush">${listItems}</ul></div>`;
                contentContainer.append(tabPane);
            });
        }

        // --- LOGIKA INDIKATOR FOKUS ---
        const inputField = $('#id_kartu');
        const focusIndicator = $('#focus-indicator');

        function setFocus() {
            inputField.focus();
        }

        function checkFocus() {
            if (inputField.is(':focus')) {
                focusIndicator.removeClass('not-focused').addClass('is-focused');
                focusIndicator.find('span').html('<i class="fa-solid fa-wifi"></i> Scanner Ready');
            } else {
                focusIndicator.removeClass('is-focused').addClass('not-focused');
                focusIndicator.find('span').html('<i class="fa-solid fa-triangle-exclamation"></i> KLIK UNTUK AKTIFKAN SCANNER');
            }
        }

        inputField.on('focus', checkFocus);
        inputField.on('blur', checkFocus);
        focusIndicator.on('click', setFocus);

        $('#schoolTabs').on('shown.bs.tab', 'a', function (e) {
            setFocus();
        });
        // --- END LOGIKA INDIKATOR FOKUS ---


        // Inisialisasi saat halaman dimuat
        $(document).ready(function() {
            const initialData = {!! json_encode($presensiHariIni ?? []) !!};
            updateTabs(initialData);
            checkFocus();
        });

        // Logika utama untuk menangani submit form (saat RFID scan)
        $('#presensi-form').on('submit', function(e) {
            e.preventDefault();
            let id_kartu = inputField.val();
            if (id_kartu === '') return;

            const statusDiv = $('#status');
            const studentInfoDiv = $('#student-info');
            statusDiv.removeClass().addClass('alert alert-info status-message').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
            studentInfoDiv.html('');

            axios.post('{{ route("presensi.store") }}', { id_kartu: id_kartu, _token: '{{ csrf_token() }}' })
            .then(function(response) {
                const res = response.data;
                const statusClass = res.status_class === 'success' ? 'alert-success' : 'alert-warning';
                const icon = res.status_class === 'success' ? '<i class="fa-solid fa-check-circle fa-2x mb-2"></i>' : '<i class="fa-solid fa-info-circle fa-2x mb-2"></i>';
                
                statusDiv.removeClass().addClass(`alert ${statusClass} status-message`).html(`${icon}<h5>${res.message}</h5>`);
                
                if(res.student) {
                    studentInfoDiv.html(`<strong>${res.student.nama_siswa}</strong><br><small>${res.student.sekolah.nama_sekolah}</small>`);
                }
                if (res.attendees) {
                    updateTabs(res.attendees, res.active_school_id);
                }
            })
            .catch(function(error) {
                const res = error.response.data;
                statusDiv.removeClass().addClass('alert alert-danger status-message').html(`<i class="fa-solid fa-times-circle fa-2x mb-2"></i><h5>${res.message || 'Terjadi kesalahan!'}</h5>`);
            });

            inputField.val('');
            setFocus();
        });

        // Refresh daftar hadir setiap 5 detik
        setInterval(function() {
            axios.get('{{ route("presensi.data") }}')
                .then(function(response) {
                    const activeTabId = $('#schoolTabs .nav-link.active').attr('id');
                    let activeSchoolId = activeTabId ? activeTabId.replace('tab-', '') : null;
                    updateTabs(response.data, activeSchoolId);
                })
                .catch(function(error) {
                    console.error('Gagal mengambil data presensi terbaru:', error);
                });
        }, 5000);
    </script>
</body>
</html>
