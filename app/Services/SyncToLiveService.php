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
        $students = Siswa::with('sekolah')->get()->map(fn($s) => [
            'external_id' => (string)$s->id,
            'name' => $s->nama_siswa,
            'school_name' => $s->sekolah->nama_sekolah ?? 'N/A',
            'status' => 'active',
            'start_pkl' => $s->mulai_pkl,
            'end_pkl' => $s->selesai_pkl,
        ]);

        return $this->sendRequest('/api/sync/students', ['students' => $students], 'students');
    }

    /**
     * Sinkronisasi Hari Libur
     */
    public function syncHolidays()
    {
        $holidays = HariLibur::all()->map(fn($l) => [
            'date' => $l->tanggal,
            'description' => $l->keterangan,
        ]);

        return $this->sendRequest('/api/sync/holidays', ['holidays' => $holidays], 'holidays');
    }

    /**
     * Sinkronisasi Kehadiran
     */
    public function syncAttendance($presensis = null)
    {
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
            return true;
        }

        return $this->sendRequest('/api/sync/attendance', ['attendance' => $attendance], 'attendance');
    }

    /**
     * Method untuk mengirim request (Sekarang mendukung FTP untuk InfinityFree)
     */
    protected function sendRequest($endpoint, $data, $type)
    {
        // Strategi 1: Sinkronisasi via FTP (Solusi untuk InfinityFree)
        if (config('filesystems.disks.ftp_monitoring.host') && config('filesystems.disks.ftp_monitoring.username') !== 'isi_username_ftp_anda') {
            return $this->uploadViaFtp($type, $data);
        }

        // Fallback ke HTTP (Strategi 2 / Normal)
        if (!$this->url || !$this->key) {
            Log::warning("SyncToLive: URL atau API Key belum dikonfigurasi.");
            return false;
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->key,
                'Accept' => 'application/json',
            ])
            ->timeout(30)
            ->post(rtrim($this->url, '/') . $endpoint, $data);

            if ($response->successful()) {
                Log::info("SyncToLive HTTP Sukses: $endpoint");
                return true;
            }

            Log::error("SyncToLive HTTP Gagal: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("SyncToLive Exception: " . $e->getMessage());
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
            $content = json_encode($data);

            $uploaded = Storage::disk('ftp_monitoring')->put($fileName, $content);

            if ($uploaded) {
                Log::info("SyncToLive FTP Sukses: $fileName");
                return true;
            }

            Log::error("SyncToLive FTP Gagal upload: $fileName");
            return false;
        } catch (\Exception $e) {
            Log::error("SyncToLive FTP Exception: " . $e->getMessage());
            return false;
        }
    }
}
