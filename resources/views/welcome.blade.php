<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Presensi PKL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .main-card {
            width: 500px;
        }
        .attendee-card {
            width: 600px;
        }
        .clock {
            font-size: 3rem;
            font-weight: bold;
        }
        .status-message {
            min-height: 100px;
            border-radius: 10px;
        }
        #attendee-list-container {
            max-height: 40vh;
            overflow-y: auto;
        }
        /* Style untuk Indikator Fokus */
        .focus-indicator-box {
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s, color 0.3s;
            border: 2px solid transparent;
            user-select: none; /* Mencegah teks terseleksi saat diklik */
        }
        .not-focused {
            background-color: #f8d7da; /* Merah */
            color: #721c24;
            border-color: #f5c6cb;
        }
        .is-focused {
            background-color: #d4edda; /* Hijau */
            color: #155724;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>

    <!-- KARTU PRESENSI UTAMA -->
    <div class="card text-center main-card">
        <div class="card-header">
            <h3>Sistem Presensi PKL</h3>
        </div>
        <div class="card-body">
            <h2 id="date"></h2>
            <div class="clock" id="clock"></div>
            <hr>
            <form id="presensi-form">
                <input type="text" id="id_kartu" class="form-control" style="opacity: 0; position: absolute;" autofocus>
            </form>
            <div id="status" class="alert status-message mt-3 d-flex justify-content-center align-items-center">
                <h5>Silakan Tempelkan Kartu Anda</h5>
            </div>
             <div id="student-info" class="mt-2"></div>

             <!-- INDIKATOR FOKUS BARU -->
             <div id="focus-indicator" class="focus-indicator-box mt-3">
                <span></span>
             </div>
        </div>
    </div>

    <!-- KARTU DAFTAR HADIR DENGAN TABS -->
    <div class="card mt-4 attendee-card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="schoolTabs" role="tablist">
                <!-- Tab navigasi akan dibuat oleh JavaScript -->
            </ul>
        </div>
        <div class="card-body p-0" id="attendee-list-container">
            <div class="tab-content" id="schoolTabsContent">
                <!-- Konten tab akan dibuat oleh JavaScript -->
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        // Fungsi untuk update jam dan tanggal secara real-time
        function updateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('clock').textContent = now.toLocaleTimeString('id-ID');
            document.getElementById('date').textContent = now.toLocaleDateString('id-ID', options);
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Fungsi untuk membangun ulang seluruh UI tab
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

            schoolIds.forEach(schoolId => {
                const attendees = groupedAttendees[schoolId];
                if (!attendees || attendees.length === 0 || !attendees[0].siswa || !attendees[0].siswa.sekolah) {
                    return;
                }
                const schoolName = attendees[0].siswa.sekolah.nama_sekolah;
                const isActive = schoolId == firstSchoolId;

                const tabLink = `<li class="nav-item"><a class="nav-link ${isActive ? 'active' : ''}" id="tab-${schoolId}" data-toggle="tab" href="#pane-${schoolId}" role="tab">${schoolName}</a></li>`;
                tabsContainer.append(tabLink);

                let listItems = '';
                attendees.forEach(function(presensi) {
                    let jamPulang = presensi.jam_pulang ? presensi.jam_pulang.substring(0, 5) : null;
                    let jamMasuk = presensi.jam_masuk.substring(0, 5);
                    let statusBadge = jamPulang
                        ? `<span class="badge badge-success badge-pill">Pulang ${jamPulang}</span>`
                        : `<span class="badge badge-primary badge-pill">Hadir ${jamMasuk}</span>`;
                    listItems += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><strong>${presensi.siswa.nama_siswa}</strong></div>
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
                focusIndicator.find('span').text('Scanner Ready');
            } else {
                focusIndicator.removeClass('is-focused').addClass('not-focused');
                focusIndicator.find('span').text('KLIK UNTUK AKTIFKAN SCANNER');
            }
        }

        inputField.on('focus', checkFocus);
        inputField.on('blur', checkFocus);
        focusIndicator.on('click', setFocus);

        // PERUBAHAN: Event listener untuk mengembalikan fokus setelah klik tab
        $('#schoolTabs').on('shown.bs.tab', 'a', function (e) {
            setFocus();
        });
        // --- END LOGIKA INDIKATOR FOKUS ---


        // Inisialisasi saat halaman dimuat
        $(document).ready(function() {
            const initialData = {!! json_encode($presensiHariIni ?? []) !!};
            updateTabs(initialData);
            checkFocus(); // Cek status fokus saat pertama kali dimuat
        });

        // Logika utama untuk menangani submit form (saat RFID scan)
        $('#presensi-form').on('submit', function(e) {
            e.preventDefault();
            let id_kartu = inputField.val();
            if (id_kartu === '') return;

            const statusDiv = $('#status');
            const studentInfoDiv = $('#student-info');
            statusDiv.removeClass('alert-success alert-danger alert-warning').addClass('alert-info').html('<h5>Memproses...</h5>');
            studentInfoDiv.html('');

            axios.post('{{ route("presensi.store") }}', { id_kartu: id_kartu, _token: '{{ csrf_token() }}' })
            .then(function(response) {
                const res = response.data;
                statusDiv.removeClass('alert-info').addClass('alert-' + res.status_class).html('<h5>' + res.message + '</h5>');
                if(res.student) {
                    studentInfoDiv.html('<strong>' + res.student.nama_siswa + '</strong><br><small>' + res.student.sekolah.nama_sekolah + '</small>');
                }
                if (res.attendees) {
                    updateTabs(res.attendees, res.active_school_id);
                }
            })
            .catch(function(error) {
                const res = error.response.data;
                statusDiv.removeClass('alert-info').addClass('alert-danger').html('<h5>' + (res.message || 'Terjadi kesalahan!') + '</h5>');
            });

            inputField.val('');
            setFocus(); // Pastikan fokus kembali setelah submit
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
