# Presensi PKL — Agent Guide

## Project

A Laravel 12 app for managing student attendance records during vocational internship (PKL).  
**DB:** MySQL (`presensipkl`), **Session/Cache/Queue:** database driver.  
**UI:** Laravel AdminLTE 3 (admin) + Tailwind/Alpine (public presensi page).

## Key packages

- `barryvdh/laravel-dompdf` — PDF reports
- `maatwebsite/excel` — Excel export
- `jeroennoten/laravel-adminlte` — admin panel theme
- `laravel/breeze` (dev) — auth scaffolding

## Entrypoints

| Area | Route | Controller |
|---|---|---|
| Public presensi | `GET /` | `PresensiController@index` |
| Public presensi (AJAX) | `GET /presensi/data` | `PresensiController@getAttendanceData` |
| Admin (auth+verified) | `GET /admin/dashboard` | `Admin\DashboardController@index` |
| Admin schools CRUD | `/admin/sekolah` | `Admin\SekolahController` |
| Admin students CRUD | `/admin/siswa` | `Admin\SiswaController` |
| Admin reports | `/admin/laporan` | `Admin\LaporanController` |
| Admin attendance edit | `/admin/presensi/{id}/edit` | `Admin\PresensiController` |
| Admin holidays | `/admin/harilibur` | `Admin\HariLiburController` |

## Models & key relationships

- **Sekolah** (school) → hasMany Siswa
- **Siswa** (student, uses `SoftDeletes`) → belongsTo Sekolah, hasMany Presensi  
  - `nama_siswa` is auto-UPPERCASED on save
  - PKL period: `mulai_pkl` / `selesai_pkl`
- **Presensi** (attendance) → belongsTo Siswa  
  - Statuses: `Hadir`, `Telat`, `Izin`, `Pulang Cepat`, `Kurang`, `Alpa`
  - `metode_izin`: `WA` or `Surat` (max 3x WA/month per student)
- **User** → auth via `username` (not email), has `role` field
- **HariLibur** (holiday) → per-sekolah

## Dev commands

```bash
# Full dev server (PHP serve + queue + logs + Vite)
composer dev

# Run tests (config:clear first)
composer test

# Or directly
php artisan test

# Frontend build
npm run build          # vite build
npm run dev            # vite dev server

# Laravel Pint (PSR-12 linter)
./vendor/bin/pint

# Codegen
php artisan make:controller NameController
php artisan make:model Name -m
php artisan make:migration create_x_table
```

## Testing

- PHPUnit 11 with SQLite `:memory:`
- Tests under `tests/Unit/` and `tests/Feature/`
- To run a single test: `php artisan test --filter=MethodName`
- No integration-service dependencies (mail = array, queue = sync, cache = array)

## Database & migrations

- 14 migration files, run: `php artisan migrate`
- Seed admin user: `php artisan db:seed`

Default seeder creates one admin:
- username: `fredyadmin1`, password: `spv12345`

## Quirks & gotchas

- **Session, cache, and queue all use `database` driver** — run `php artisan queue:work` for background jobs (dev command handles this)
- **Student names are auto-UPPERCASED** via mutator on `Siswa.nama_siswa`
- **Admin routes require `auth` + `verified`** middleware
- **Public presensi page records check-in AND check-out** via the same endpoint (first call = masuk, second call = pulang)
- **Excel export uses Maatwebsite/Laravel-Excel** (not PhpSpreadsheet directly)
- **PDF uses DomPDF** with landscape A4
- No CI/CD workflows configured

## Export features

- PDF: pivot/matrix report grouped by student per date (chunked 5 students/page)
- Excel: flat list with computed lateness/early-leave notes
- Both accept `tanggal_mulai`, `tanggal_selesai`, `sekolah_id` filters
