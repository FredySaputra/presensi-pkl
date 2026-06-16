<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['filesystems.disks.ftp_monitoring.passive' => false]);

try {
    $disk = \Illuminate\Support\Facades\Storage::disk('ftp_monitoring');
    $disk->put('test_active.txt', 'test');
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
