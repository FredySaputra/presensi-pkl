# Presensi PKL — Agent Guide

## Project Overview

A Laravel 12 application for managing student attendance records during vocational internship (PKL).
- **Backend:** PHP 8.2+, Laravel 12
- **Database:** MySQL (`presensipkl`), Session/Cache/Queue use `database` driver.
- **Frontend:** Laravel AdminLTE 3 (Admin Panel) + Tailwind/Alpine (Public Presensi Page).
- **Key Features:** Attendance tracking, Holiday management, PDF/Excel Exports, Data Synchronization to Live Server (via API/FTP).

## Directory Structure

```text
D:\laragon\www\PresensiPKL\
├── app/
│   ├── Console/Commands/      # Artisan commands (e.g., SyncDataToLive.php)
│   ├── Exports/               # Maatwebsite Excel exports
│   ├── Http/Controllers/      # Application controllers
│   │   ├── Admin/             # Admin panel logic (Sekolah, Siswa, Presensi, etc.)
│   │   └── Auth/              # Authentication logic (Laravel Breeze)
│   ├── Models/                # Eloquent models (Siswa, Sekolah, Presensi, HariLibur)
│   ├── Services/              # Business logic (e.g., SyncToLiveService.php)
│   └── View/Components/       # Blade components
├── config/                    # Configuration files
├── database/
│   ├── migrations/            # Database schema definitions
│   └── seeders/               # Database seeders (DatabaseSeeder, SekolahSeeder)
├── public/                    # Compiled assets and public entry point
├── resources/
│   ├── css/ & js/             # Source assets (Vite)
│   └── views/                 # Blade templates
│       ├── admin/             # Admin views
│       ├── auth/              # Auth views
│       └── presensi/          # Public attendance views
├── routes/                    # Route definitions (web.php, auth.php, console.php)
├── storage/                   # App storage (logs, uploads, framework cache)
└── tests/                     # Automated tests (Feature & Unit)
```

## Key Packages

- `barryvdh/laravel-dompdf` — PDF report generation.
- `maatwebsite/excel` — Excel file exports.
- `jeroennoten/laravel-adminlte` — Admin dashboard theme.
- `laravel/breeze` — Authentication scaffolding.
- `league/flysystem-ftp` — Required for FTP sync (must be installed if using FTP).

## Recent Updates & Work History

### 1. Front-end Date Validation (June 15, 2026)
- **Problem:** Users could input a PKL end date earlier than the start date in the browser.
- **Solution:** Added JavaScript logic to `siswa/create`, `siswa/edit`, and `harilibur/index`.
- **Implementation:**
  - "Selesai PKL" field is disabled until "Mulai PKL" is filled.
  - Set `min` attribute on the end date field based on the start date.
  - Auto-clears or adjusts the end date if the start date changes to a later value.

### 2. Synchronization System
- **Service:** `App\Services\SyncToLiveService` handles data sync to a monitoring system.
- **Methods:** Supports both **HTTP API** and **FTP** (uploading JSON files).
- **Error Note:** If "Class FtpAdapter not found" occurs, install `league/flysystem-ftp`.
- **Guides:** See `API_INTEGRATION_GUIDE.md` for API specs and `.env` for configuration.

## Entrypoints & Routes

| Area | Route | Controller |
|---|---|---|
| Public Presensi | `GET /` | `PresensiController@index` |
| Admin Dashboard | `GET /admin/dashboard` | `Admin\DashboardController@index` |
| Admin Sekolah | `/admin/sekolah` | `Admin\SekolahController` |
| Admin Siswa | `/admin/siswa` | `Admin\SiswaController` |
| Admin Hari Libur | `/admin/harilibur` | `Admin\HariLiburController` |
| Admin Laporan | `/admin/laporan` | `Admin\LaporanController` |

## Quirks & Rules

- **Naming:** Student names are automatically converted to **UPPERCASE** via Eloquent mutators.
- **Attendance Logic:** The same public endpoint handles check-in (masuk) and check-out (pulang) based on the current day's record existence.
- **Holiday Scope:** Holidays can be global or specific to a school.
- **Validation:** Always prefer adding both front-end (JS) and back-end (Laravel Request) validation for date ranges.

## Dev Commands

```bash
composer dev           # Run serve, queue, logs, and vite concurrently
composer test          # Clear config and run tests
npm run build          # Build assets for production
```
