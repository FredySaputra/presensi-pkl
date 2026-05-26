# Sistem Presensi Siswa PKL

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=for-the-badge)

Aplikasi berbasis web untuk memonitor dan mengelola presensi harian siswa Praktik Kerja Lapangan (PKL). Sistem ini dirancang agar cepat, interaktif, dan menghasilkan rekapitulasi data yang akurat untuk kebutuhan pelaporan sekolah maupun instansi.

## Fitur Utama

* **Dashboard Monitor Real-time:** Memantau status kehadiran siswa (Hadir, Telat, Pulang Cepat) secara langsung dengan fitur *auto-refresh* tanpa perlu memuat ulang halaman.
* **Presensi Masuk & Pulang Cerdas:** * Otomatis menghitung waktu keterlambatan dan kepulangan lebih awal.
    * *Smart Dropdown*: Sekolah dan siswa yang telah melakukan presensi akan otomatis disembunyikan dari pilihan.
    * Fitur *One-Click Update* untuk mengoreksi jam pulang siswa secara *real-time*.
* **Manajemen Izin Terkontrol:**
    * Mendukung perizinan via WhatsApp (maksimal 3x per bulan) dan Surat Fisik (tanpa batas).
    * Fitur "Catat Izin Massal" bagi admin.
* **Laporan & Ekspor Tingkat Lanjut (PDF & Excel):**
    * **Mode Detail:** Menampilkan data lengkap jam kedatangan dan kepulangan per hari.
    * **Mode Rekap Umum:** Mencetak matriks kalender bulanan dengan kode huruf berwarna (H, I, A, L) persis seperti buku absensi fisik konvensional.
    * Otomatis memisahkan tabel laporan per bulan untuk tampilan yang lebih rapi (Pagination cetak cerdas).
* **Manajemen Hari Libur:** Terintegrasi dengan kalender untuk mendeteksi libur akhir pekan (Sabtu/Minggu) dan libur nasional/khusus sekolah.

## Teknologi yang Digunakan

* **Framework:** Laravel 12
* **Frontend:** Bootstrap 5, AdminLTE 3
* **JavaScript:** jQuery, Axios (untuk request API asynchronous)
* **PDF Generator:** Barryvdh\DomPDF
* **Excel Export:** Maatwebsite\Laravel-Excel

## Panduan Instalasi

Ikuti langkah-langkah berikut untuk menjalankan aplikasi ini di komputer Anda:

1.  **Clone repositori ini:**
    ```bash
    git clone https://github.com/FredySaputra/presensi-pkl.git
    cd presensi-pkl
    ```

2.  **Instal dependensi PHP:**
    ```bash
    composer install
    ```

3.  **Salin file environment dan atur konfigurasi:**
    ```bash
    cp .env.example .env
    ```
    Buka file `.env` dan sesuaikan konfigurasi *database* Anda (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

4.  **Generate Application Key:**
    ```bash
    php artisan key:generate
    ```

5.  **Jalankan Migrasi & Seeder Database:**
    ```bash
    php artisan migrate --seed
    ```

6.  **Jalankan Local Development Server:**
    ```bash
    php artisan serve
    ```
    Aplikasi sekarang dapat diakses melalui `http://127.0.0.1:8000`.

## Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).

Copyright (c) 2026 Fredy Dwi Saputra

Silakan menggunakan, memodifikasi, dan mendistribusikan kode ini sesuai dengan ketentuan yang berlaku pada lisensi.
