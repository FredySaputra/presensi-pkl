<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Presensi PKL</title>
    {{-- Kita akan menggunakan Bootstrap untuk styling --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card {
            width: 500px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .clock {
            font-size: 3rem;
            font-weight: bold;
        }
        .status-message {
            min-height: 100px;
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <div class="card text-center">
        <div class="card-header">
            <h3>Sistem Presensi PKL</h3>
        </div>
        <div class="card-body">
            <h2 id="date"></h2>
            <div class="clock" id="clock"></div>
            <hr>
            <form id="presensi-form">
                {{-- Input field ini tidak terlihat, tapi selalu aktif untuk menerima input RFID --}}
                <input type="text" id="id_kartu" class="form-control" style="opacity: 0; position: absolute;" autofocus>
            </form>
            <div id="status" class="alert status-message mt-3 d-flex justify-content-center align-items-center">
                <h5>Silakan Tempelkan Kartu Anda</h5>
            </div>
             <div id="student-info" class="mt-2"></div>
        </div>
    </div>

    {{-- Kita butuh jQuery dan Axios untuk AJAX --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        // Logika utama untuk menangani submit form (saat RFID scan)
        $('#presensi-form').on('submit', function(e) {
            e.preventDefault(); // Mencegah halaman refresh

            let id_kartu = $('#id_kartu').val();
            if (id_kartu === '') return; // Abaikan jika input kosong

            const statusDiv = $('#status');
            const studentInfoDiv = $('#student-info');

            statusDiv.removeClass('alert-success alert-danger alert-warning').addClass('alert-info').html('<h5>Memproses...</h5>');
            studentInfoDiv.html('');

            axios.post('{{ route("presensi.store") }}', {
                id_kartu: id_kartu,
                _token: '{{ csrf_token() }}'
            })
            .then(function(response) {
                const res = response.data;
                statusDiv.removeClass('alert-info').addClass('alert-' + res.status_class).html('<h5>' + res.message + '</h5>');
                if(res.student) {
                    studentInfoDiv.html('<strong>' + res.student.nama_siswa + '</strong><br><small>' + res.student.sekolah.nama_sekolah + '</small>');
                }
            })
            .catch(function(error) {
                const res = error.response.data;
                statusDiv.removeClass('alert-info').addClass('alert-danger').html('<h5>' + (res.message || 'Terjadi kesalahan!') + '</h5>');
            });

            // Bersihkan input dan fokuskan kembali, siap untuk scan berikutnya
            $('#id_kartu').val('').focus();
        });
    </script>
</body>
</html>