<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['filesystems.disks.ftp_monitoring.root' => '/htdocs/laravel/storage/app/sync']);

try {
    $disk = \Illuminate\Support\Facades\Storage::disk('ftp_monitoring');
    $disk->put('test_slash.txt', 'test');
    echo "SUCCESS\n";
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
