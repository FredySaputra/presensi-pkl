<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\HariLibur;
use App\Models\Presensi;

class SyncLiveController extends Controller
{
    public function syncViaBrowser()
    {
        // Kumpulkan data siswa
        $students = Siswa::with('sekolah')->get()->map(fn($s) => [
            'external_id' => (string)$s->id,
            'name' => $s->nama_siswa,
            'school_name' => $s->sekolah->nama_sekolah ?? 'N/A',
            'status' => 'active',
            'start_pkl' => $s->mulai_pkl,
            'end_pkl' => $s->selesai_pkl,
        ]);

        // Kumpulkan data hari libur
        $holidays = HariLibur::all()->map(fn($l) => [
            'date' => $l->tanggal,
            'description' => $l->keterangan,
        ]);

        // Kumpulkan data kehadiran HARI INI
        $presensis = Presensi::whereDate('tanggal', now()->toDateString())->get();
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

        $payload = json_encode([
            'students' => $students,
            'holidays' => $holidays,
            'attendance' => $attendance,
        ]);

        $targetUrl = rtrim(config('services.monitoring.url', 'https://pkl-monitoring.page.gd'), '/') . '/api/sync/all';
        $apiKey = config('services.monitoring.key');

        return view('admin.sync-browser', compact('payload', 'targetUrl', 'apiKey'));
    }
}
