const puppeteer = require('puppeteer');

(async () => {
    console.log("Memulai Auto Sync PresensiPKL...");
    
    // Launch Chrome. Headless: false supaya cookie AES Infinity Free dapat dilewati
    const browser = await puppeteer.launch({
        headless: false,
        defaultViewport: null,
        args: ['--start-maximized']
    });

    try {
        const page = await browser.newPage();
        
        // 1. Pergi ke halaman login
        console.log("Membuka halaman login...");
        await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle2' });

        // 2. Isi kredensial dan login
        console.log("Mengisi username dan password...");
        await page.type('input[name="username"]', 'fredyadmin1');
        await page.type('input[name="password"]', 'spv12345');
        
        console.log("Klik tombol login...");
        // Gunakan Promise.all untuk menunggu navigasi selesai setelah klik
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);
        
        console.log("Login berhasil, berada di Dashboard.");

        // 3. Menunggu proses sinkronisasi otomatis
        // Script ini nantinya di-trigger dari Task Scheduler pada pukul 09:43
        // Cronjob Javascript di dashboard akan jalan pada pukul 09:45.
        console.log("Menunggu proses sinkronisasi otomatis yang dijadwalkan pada 09:45...");
        
        // Menunggu sampai URL memiliki ?sync_success=1 (Redirect setelah popup sukses)
        // Set timeout 5 menit (300.000 ms) agar cukup waktu dari 09:43 ke 09:46
        await page.waitForFunction(
            'window.location.href.includes("sync_success=1")',
            { timeout: 300000 } 
        );
        
        console.log("Sinkronisasi terdeteksi selesai dengan status success!");

        // Beri jeda 3 detik untuk memastikan semuanya render sempurna sebelum logout
        await new Promise(r => setTimeout(r, 3000));

        // 4. Logout
        console.log("Melakukan proses logout...");
        await page.evaluate(() => {
            // Coba submit form logout bawaan AdminLTE/Laravel
            const logoutForm = document.getElementById('logout-form');
            if(logoutForm) {
                logoutForm.submit();
            } else {
                // Jika form tidak ditemukan, buat form POST logout secara manual
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/logout'; 
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = csrfToken.content;
                    form.appendChild(csrf);
                }
                
                document.body.appendChild(form);
                form.submit();
            }
        });
        
        await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 }).catch(() => {});
        console.log("Logout berhasil.");

    } catch (error) {
        console.error("Terjadi kesalahan selama proses otomatisasi:", error);
    } finally {
        console.log("Menutup browser...");
        await browser.close();
    }
})();
