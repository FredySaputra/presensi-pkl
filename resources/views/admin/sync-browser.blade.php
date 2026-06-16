<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sinkronisasi ke Live Server</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f6f9; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.08); text-align: center; max-width: 500px; width: 100%; }
        h2 { margin-top: 0; color: #333; font-size: 24px; }
        p { color: #666; margin-bottom: 25px; }
        
        .progress-container { width: 100%; margin: 20px 0; }
        .progress-bar-bg { background-color: #e9ecef; border-radius: 8px; height: 16px; width: 100%; overflow: hidden; }
        .progress-bar-fill { background-color: #3498db; height: 100%; width: 0%; transition: width 0.3s ease; }
        .progress-text { margin-top: 8px; font-weight: bold; color: #3498db; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Sedang Menyimkronkan Data...</h2>
        <p id="statusText">Melewati pengecekan keamanan server...</p>
        
        <div class="progress-container">
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="progressBar"></div>
            </div>
            <div class="progress-text" id="progressText">0%</div>
        </div>
    </div>

    <script>
        var payloadObj = {
            payload: {!! $payload !!},
            api_key: "{{ $apiKey }}",
            redirect_url: "{{ route('admin.dashboard') }}"
        };

        var targetOrigin = new URL("{{ $targetUrl }}").origin;
        var hasSentData = false;
        var syncPopup = null;
        
        function updateProgress(percent, text) {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').innerText = percent + '%';
            if (text) {
                document.getElementById('statusText').innerText = text;
            }
        }

        // Buka popup ketika halaman siap
        window.onload = function() {
            updateProgress(2, "Membuka jalur aman ke server...");
            syncPopup = window.open("{{ rtrim($targetUrl, '/all') . '/receiver' }}", "SyncServer", "width=500,height=500,left=100,top=100");
            
            if (!syncPopup || syncPopup.closed || typeof syncPopup.closed == 'undefined') {
                updateProgress(0, "Gagal: Popup diblokir oleh browser. Harap izinkan popup untuk situs ini.");
                document.getElementById('statusText').style.color = "red";
                document.getElementById('progressBar').style.backgroundColor = "red";
            }
        };

        window.addEventListener("message", function(event) {
            if (event.origin !== targetOrigin) return;

            if (event.data && typeof event.data === 'object') {
                if (event.data.type === "ready" && !hasSentData) {
                    hasSentData = true;
                    updateProgress(5, "Memulai pengunggahan data...");
                    syncPopup.postMessage(payloadObj, targetOrigin);
                } 
                else if (event.data.type === "progress") {
                    if (event.data.stage === "uploading") {
                        var visualPercent = 5 + Math.round(event.data.percent * 0.75);
                        updateProgress(visualPercent, "Mengunggah data ke server...");
                    } else if (event.data.stage === "processing_done") {
                        updateProgress(90, "Memproses data di database...");
                    }
                }
                else if (event.data.type === "done") {
                    updateProgress(100, "Selesai! Mengalihkan...");
                    if (syncPopup) syncPopup.close(); // Tutup popup otomatis
                    setTimeout(function() {
                        window.location.href = event.data.redirect + (event.data.redirect.indexOf('?') > -1 ? '&' : '?') + 'sync_success=1';
                    }, 500);
                }
                else if (event.data.type === "error") {
                    document.getElementById('statusText').innerText = "Gagal: " + event.data.message;
                    document.getElementById('statusText').style.color = "red";
                    document.getElementById('progressBar').style.backgroundColor = "red";
                }
            }
        });
    </script>
</body>
</html>
