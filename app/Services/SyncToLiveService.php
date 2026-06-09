<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\HariLibur;
use App\Models\Presensi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            'status' => 'active', // Default as Siswa model doesn't have status field
            'start_pkl' => $s->mulai_pkl,
            'end_pkl' => $s->selesai_pkl,
        ]);

        return $this->sendRequest('/api/sync-students', ['students' => $students]);
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

        return $this->sendRequest('/api/sync-holidays', ['holidays' => $holidays]);
    }

    /**
     * Sinkronisasi Kehadiran
     * 
     * @param \Illuminate\Support\Collection|null $presensis
     */
    public function syncAttendance($presensis = null)
    {
        if (!$presensis) {
            $presensis = Presensi::whereDate('tanggal', now()->toDateString())->get();
        }

        $attendance = $presensis->map(function($a) {
            // Map local status to guide status
            $statusMap = [
                'Hadir'        => 'hadir',
                'Telat'        => 'hadir',
                'Pulang Cepat' => 'hadir',
                'Izin'         => 'izin',
                'Sakit'        => 'sakit',
                'Alpa'         => 'alpa',
                'Kurang'       => 'alpa', // Assumed 'Kurang' is equivalent to alpa for monitoring
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

        return $this->sendRequest('/api/sync-attendance', ['attendance' => $attendance]);
    }

    /**
     * Method untuk mengirim request ke Live Monitoring
     */
    protected function sendRequest($endpoint, $data)
    {
        if (!$this->url || !$this->key) {
            Log::warning("SyncToLive: URL atau API Key belum dikonfigurasi di .env. Request ke $endpoint dibatalkan.");
            return false;
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->key,
                'Accept' => 'application/json',
            ])
            ->timeout(30)
            ->retry(3, 100)
            ->post(rtrim($this->url, '/') . $endpoint, $data);

            if ($response->successful()) {
                Log::info("SyncToLive Sukses: $endpoint");
                return true;
            }

            Log::error("SyncToLive Gagal di $endpoint: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("SyncToLive Exception di $endpoint: " . $e->getMessage());
            return false;
        }
    }
}
