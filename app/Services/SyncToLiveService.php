<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\HariLibur;
use App\Models\Presensi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncToLiveService
{
    protected $url;
    protected $key;

    public function __construct()
    {
        $this->url = config('services.monitoring.url');
        $this->key = config('services.monitoring.key');
    }

    /**
     * Sinkronisasi Siswa
     */
    public function syncStudents()
    {
        try {
            $students = Siswa::with('sekolah')->get()->map(fn($s) => [
                'external_id' => (string)$s->id,
                'name' => $s->nama_siswa,
                'school_name' => $s->sekolah->nama_sekolah ?? 'N/A',
                'status' => 'active',
                'start_pkl' => $s->mulai_pkl,
                'end_pkl' => $s->selesai_pkl,
            ]);

            if ($students->isEmpty()) {
                Log::info("SyncToLive: Tidak ada data siswa untuk dikirim.");
                return true;
            }

            return $this->sendRequest('/api/sync/students', ['students' => $students], 'students');
        } catch (\Exception $e) {
            Log::error("SyncToLive Siswa Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sinkronisasi Hari Libur
     */
    public function syncHolidays()
    {
        try {
            $holidays = HariLibur::all()->map(fn($l) => [
                'date' => $l->tanggal,
                'description' => $l->keterangan,
            ]);

            if ($holidays->isEmpty()) {
                return true;
            }

            return $this->sendRequest('/api/sync/holidays', ['holidays' => $holidays], 'holidays');
        } catch (\Exception $e) {
            Log::error("SyncToLive Libur Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sinkronisasi Kehadiran
     */
    public function syncAttendance($presensis = null)
    {
        try {
            if (!$presensis) {
                $presensis = Presensi::whereDate('tanggal', now()->toDateString())->get();
            }

            $attendance = $presensis->map(function($a) {
                $statusMap = [
                    'Hadir'        => 'hadir',
                    'Telat'        => 'hadir',
                    'Pulang Cepat' => 'hadir',
                    'Izin'         => 'izin',
                    'Sakit'        => 'sakit',
                    'Alpa'         => 'alpa',
                    'Kurang'       => 'alpa',
                ];

                return [
                    'external_id' => (string)$a->siswa_id,
                    'date' => $a->tanggal,
                    'status' => $statusMap[$a->status] ?? 'hadir',
                    'description' => $a->keterangan ?? '-',
                ];
            });

            if ($attendance->isEmpty()) {
                Log::info("SyncToLive: Tidak ada data kehadiran hari ini.");
                return true;
            }

            return $this->sendRequest('/api/sync/attendance', ['attendance' => $attendance], 'attendance');
        } catch (\Exception $e) {
            Log::error("SyncToLive Kehadiran Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Method untuk mengirim request
     */
    protected function sendRequest($endpoint, $data, $type)
    {
        // Cek jika FTP dikonfigurasi
        $ftpHost = config('filesystems.disks.ftp_monitoring.host');
        $ftpUser = config('filesystems.disks.ftp_monitoring.username');

        if ($ftpHost && $ftpUser && $ftpUser !== 'isi_username_ftp_anda') {
            Log::info("SyncToLive: Mencoba sinkronisasi via FTP ($type)...");
            return $this->uploadViaFtp($type, $data);
        }

        Log::info("SyncToLive: Mencoba sinkronisasi via HTTP ($type)...");
        if (!$this->url || !$this->key) {
            Log::warning("SyncToLive: URL atau API Key belum dikonfigurasi.");
            return false;
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->key,
                'Accept' => 'application/json',
            ])
            ->withoutVerifying()
            ->timeout(30)
            ->post(rtrim($this->url, '/') . $endpoint, $data);

            if ($response->successful()) {
                Log::info("SyncToLive HTTP Sukses: $endpoint");
                return true;
            }

            Log::error("SyncToLive HTTP Gagal: (" . $response->status() . ") " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("SyncToLive HTTP Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload data ke server via FTP sebagai file JSON
     */
    protected function uploadViaFtp($type, $data)
    {
        try {
            $fileName = "sync_{$type}_" . date('Ymd_His') . ".json";
            $content = json_encode($data, JSON_PRETTY_PRINT);

            $disk = Storage::disk('ftp_monitoring');
            
            // Coba simpan file
            $uploaded = $disk->put($fileName, $content);

            if ($uploaded) {
                Log::info("SyncToLive FTP Berhasil: $fileName");
                return true;
            }

            Log::error("SyncToLive FTP Gagal: Gagal menulis file $fileName ke server.");
            return false;
        } catch (\Exception $e) {
            Log::error("SyncToLive FTP Exception: " . $e->getMessage());
            // Berikan detail lebih lanjut jika itu adalah error koneksi
            if (str_contains($e->getMessage(), 'Could not connect')) {
                Log::error("Detail: Koneksi FTP ditolak atau host tidak ditemukan.");
            }
            return false;
        }
    }
}
