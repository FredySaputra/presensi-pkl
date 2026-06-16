<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sinkronisasi ke Live Server</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f6f9; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="card">
        <h2>Sedang Menyimkronkan Data...</h2>
        <p>Mohon tunggu sebentar, Anda akan dialihkan secara otomatis.</p>
        <div class="loader"></div>
        
        <form id="autoSyncForm" action="{{ $targetUrl }}" method="POST">
            <input type="hidden" name="payload" value="{{ $payload }}">
            <input type="hidden" name="api_key" value="{{ $apiKey }}">
            <input type="hidden" name="redirect_url" value="{{ route('admin.dashboard') }}">
        </form>
    </div>

    <script>
        // Otomatis submit form setelah halaman dimuat
        window.onload = function() {
            setTimeout(function() {
                document.getElementById('autoSyncForm').submit();
            }, 500); // delay setengah detik agar user melihat animasi loading
        };
    </script>
</body>
</html>
