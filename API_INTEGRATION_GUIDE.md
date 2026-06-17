# Panduan Integrasi API - Sistem Monitoring Tugas PKL

Dokumen ini berisi spesifikasi teknis untuk menyesuaikan sistem presensi lokal agar dapat tersinkronisasi dengan Sistem Monitoring Tugas PKL.

## 1. Konfigurasi Keamanan
Semua request harus menyertakan API Key untuk autentikasi.
- **Header:** `X-API-KEY: [TOKEN_ANDA]`
- **Atau Query Param:** `?api_key=[TOKEN_ANDA]`

---

## 2. Endpoint: Sinkronisasi Siswa
Digunakan untuk sinkronisasi data siswa, sekolah, dan periode PKL.

- **URL:** `/api/sync/students`
- **Method:** `POST`
- **Catatan:** Pencocokan Nama Siswa dan Sekolah bersifat *Case-Insensitive* (tidak peka huruf besar/kecil).

### Format JSON:
```json
{
  "students": [
    {
      "external_id": "101",
      "name": "MUHAMMAD ARIF",
      "school_name": "SMKN 1 KOTA",
      "status": "active",
      "start_pkl": "2026-01-01",
      "end_pkl": "2026-06-30"
    }
  ]
}
```

---

## 3. Endpoint: Sinkronisasi Kehadiran & Izin
Digunakan untuk mengirim status kehadiran harian. Data ini akan otomatis mengontrol pemberian tugas harian dan piket.

- **URL:** `/api/sync/attendance`
- **Method:** `POST`

### Aturan Status:
| Status | Dampak pada Sistem Monitoring |
| :--- | :--- |
| `hadir` | Siswa tetap mendapatkan tugas rutin dan piket. |
| `izin` | Tugas rutin/piket hari tersebut **tidak dibuat** atau **dihapus otomatis**. |
| `sakit` | Tugas rutin/piket hari tersebut **tidak dibuat** atau **dihapus otomatis**. |
| `alpa` | Tugas tetap ada, tapi diberi catatan sistem "[Auto-System] Siswa ALPA". |

### Format JSON:
```json
{
  "attendance": [
    {
      "external_id": "101",
      "date": "2026-06-10",
      "status": "izin",
      "description": "Izin karena keperluan keluarga"
    },
    {
      "external_id": "102",
      "date": "2026-06-10",
      "status": "sakit",
      "description": "Demam tinggi"
    }
  ]
}
```

---

## 4. Endpoint: Sinkronisasi Hari Libur
Digunakan untuk menandai hari libur nasional atau lokal agar sistem tidak membuat tugas untuk seluruh siswa.

- **URL:** `/api/sync/holidays`
- **Method:** `POST`

### Format JSON:
```json
{
  "holidays": [
    {
      "date": "2026-08-17",
      "description": "Hari Kemerdekaan RI"
    }
  ]
}
```

---

## Tips Integrasi:
1. **Real-time Izin:** Sangat disarankan untuk memicu endpoint `sync-attendance` segera setelah admin di sistem lokal menginput status **Izin** atau **Sakit** agar tugas di sistem monitoring langsung terhapus.
2. **Case Sensitivity:** Anda bebas mengirimkan nama dalam HURUF KAPITAL SEMUA. Sistem kami akan melakukan normalisasi secara otomatis.
3. **External ID:** Pastikan `external_id` konsisten dan tidak berubah bagi tiap siswa karena ini adalah kunci utama sinkronisasi.

