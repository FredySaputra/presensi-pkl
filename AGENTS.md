# PresensiPKL - Agent Knowledge Base

## Overview
`PresensiPKL` is a localized Laravel application acting as a student attendance and master data system for students undertaking their internship (PKL) at the user's institution. It handles manual attendance, bulk leave (izin) logging, managing school data, student data, and holidays.

Crucially, `PresensiPKL` is responsible for pushing this data into a remote Infinity Free hosting server (`SistemMonitoringTugasPKL`) which acts as the Task Monitoring System.

## Architecture & Modifications Implemented

### 1. Security & Bypassing Infinity Free AES Challenge
The target server (`SistemMonitoringTugasPKL` on Infinity Free) has an aggressive Javascript-based anti-bot protection (AES cookie challenge) that blocks all standard REST API POST requests and FTP ports on certain networks.
- **Solution Implemented:** We abandoned traditional cURL/Guzzle API calls. Instead, we use a `window.open` (Popup) mechanism in the browser. 
- **How it works:** The admin triggers a sync. `PresensiPKL` gathers all JSON data and stores it in `localStorage` inside the browser. It then opens a popup pointing to the live server's `/sync/receiver` route. The popup solves the AES challenge natively in the browser, obtains the session cookies, and then uses AJAX (`XMLHttpRequest`) to fetch the JSON from its `window.opener` (`PresensiPKL`) and push it successfully to the backend. Progress tracking is relayed back via `window.opener`.

### 2. Automated Javascript Cronjob (09:45 AM Sync)
Since standard OS cronjobs cannot bypass the Infinity Free AES challenge, an automated sync mechanism was placed directly into the browser.
- **File modified:** `resources/views/admin/dashboard.blade.php`
- **Logic:** An interval checks the time every 30 seconds. If the local time hits **09:45 AM**, and today is **not a holiday** (`$isHoliday` flag passed from `DashboardController`), it automatically redirects to `admin.sync-live` which opens the popup to push the data.
- **Prerequisite:** The `PresensiPKL` Admin Dashboard must be kept open on the admin's computer for the cronjob to fire.

### 3. Automated Public Holiday Fetching
The user requested automatic generation of public holidays (Tanggal Merah) to avoid manual data entry.
- **Solution Implemented:** Integration with a public GitHub JSON API (`https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/calendar.json`).
- **File modified:** `app/Http/Controllers/Admin/HariLiburController.php` (`fetchAuto` method).
- **Logic:** Retrieves holidays for the current and next year. It rigorously filters out "Cuti Bersama" (collective leave) by checking the strings. Valid "Hari Libur Nasional" are inserted into the local `HariLibur` model. This can be triggered manually via a button in the UI (`admin/harilibur/index.blade.php`).

### 4. Application Folder Structure
```
D:\laragon\www\PresensiPKL
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php (Passes $isHoliday flag for JS Cronjob)
│   │   │   ├── HariLiburController.php (Holiday CRUD + fetchAuto API)
│   │   │   ├── SyncLiveController.php (Prepares JSON payload for Popup Sync)
│   │   │   └── ...
│   ├── Models/
│   │   ├── HariLibur.php
│   │   ├── Presensi.php
│   │   ├── Siswa.php
│   │   └── Sekolah.php
│   ├── Services/
│   │   └── SyncToLiveService.php (Deprecated API approaches kept for reference)
├── resources/
│   ├── views/admin/
│   │   ├── dashboard.blade.php (Contains JS Cronjob logic)
│   │   ├── sync-browser.blade.php (Handles window.open and IPC progress bar)
│   │   └── harilibur/index.blade.php (Auto-Fetch button)
├── routes/
│   ├── web.php (Routes including /sync-live and /harilibur/fetch-auto)
└── ...
```

### 5. Tech Stack & Dependencies
- **Framework:** Laravel 12.0 (`php: ^8.2`)
- **Key Packages:**
  - `barryvdh/laravel-dompdf` (^3.1): For generating PDF reports.
  - `jeroennoten/laravel-adminlte` (^3.15): UI Dashboard framework.
  - `maatwebsite/excel` (^3.1): For generating Excel reports.
  - `league/flysystem-ftp` (^3.31): Initially used for FTP sync attempts (currently blocked by the user's campus network).

## Important Handover Notes
- **DO NOT** attempt to replace the Popup synchronization mechanism with cURL, Guzzle, or HTTP Client. Infinity Free will return a `403 Forbidden` or `html/javascript` math challenge.
- **DO NOT** attempt to use FTP/SFTP sync from within this app while it is connected to the campus network, as port 21 is blocked.
- If you need to debug the sync payload, check `sync-browser.blade.php` and the Javascript `localStorage.getItem('sync_payload')`.
