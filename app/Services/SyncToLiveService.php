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
            'external_id' => $s->id,
            'name' => $s->nama_siswa,
            'school_name' => $s->sekolah->nama_sekolah ?? 'N/A',
            'lab_name' => env('LAB_NAME', 'Lab ICT'),
            'start_pkl' => $s->mulai_pkl,
            'end_pkl' => $s->selesai_pkl,
        ]);

        return $this->sendRequest('/api/sync/students', ['students' => $students]);
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

        return $this->sendRequest('/api/sync/holidays', ['holidays' => $holidays]);
    }

    /**
     * Sinkronisasi Kehadiran (Alpa)
     */
    public function syncAttendance()
    {
        $attendance = Presensi::where('status', 'alpa')->get()->map(fn($a) => [
            'external_id' => $a->siswa_id,
            'date' => $a->tanggal,
            'status' => 'alpa',
        ]);

        return $this->sendRequest('/api/sync/attendance', ['attendance' => $attendance]);
    }

    /**
     * Method untuk mengirim request ke Live Monitoring
     */
    protected function sendRequest($endpoint, $data)
    {
        if (!$this->url || !$this->key) {
            Log::error("SyncToLive: URL atau API Key belum dikonfigurasi di .env");
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
