<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SyncToLiveService;

class SyncDataToLive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-data-to-live';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data siswa, libur, dan kehadiran ke server monitoring live';

    /**
     * Execute the console command.
     */
    public function handle(SyncToLiveService $syncService)
    {
        $this->info('Memulai sinkronisasi data ke Live Monitoring...');

        $this->comment('1. Sinkronisasi Data Siswa...');
        if ($syncService->syncStudents()) {
            $this->info('Siswa berhasil disinkronkan.');
        } else {
            $this->error('Siswa gagal disinkronkan.');
        }

        $this->comment('2. Sinkronisasi Data Hari Libur...');
        if ($syncService->syncHolidays()) {
            $this->info('Hari libur berhasil disinkronkan.');
        } else {
            $this->error('Hari libur gagal disinkronkan.');
        }

        $this->comment('3. Sinkronisasi Data Kehadiran (Hadir, Izin, Sakit, Alpa)...');
        if ($syncService->syncAttendance()) {
            $this->info('Kehadiran berhasil disinkronkan.');
        } else {
            $this->error('Kehadiran gagal disinkronkan.');
        }

        $this->info('Sinkronisasi selesai.');
    }
}
