<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $disk = \Illuminate\Support\Facades\Storage::disk('ftp_monitoring');
    $disk->put('test1.txt', 'test1');
    echo "First put succeeded.\n";
    $disk->put('test2.txt', 'test2');
    echo "Second put succeeded.\n";
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
