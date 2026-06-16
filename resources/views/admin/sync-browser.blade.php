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
        
        <iframe id="receiverFrame" src="{{ rtrim($targetUrl, '/all') . '/receiver' }}" style="display:none;"></iframe>
    </div>

    <script>
        var payloadObj = {
            payload: {!! $payload !!},
            api_key: "{{ $apiKey }}",
            redirect_url: "{{ route('admin.dashboard') }}"
        };

        var targetOrigin = new URL("{{ $targetUrl }}").origin;

        window.addEventListener("message", function(event) {
            if (event.data === "ready" && event.origin === targetOrigin) {
                document.getElementById('receiverFrame').contentWindow.postMessage(payloadObj, targetOrigin);
            }
        });

        // Fallback jika event ready tidak diterima (misal terhalang challenge Infinity Free sebentar)
        setTimeout(function() {
            document.getElementById('receiverFrame').contentWindow.postMessage(payloadObj, targetOrigin);
        }, 5000); // Tunggu 5 detik untuk memastikan Infinity Free check selesai di iframe
    </script>
</body>
</html>
