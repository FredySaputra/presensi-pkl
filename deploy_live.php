<?php
$ftp_server = 'ftpupload.net';
$ftp_user = 'if0_41855033';
$ftp_pass = 'panascak1';

$conn_id = ftp_connect($ftp_server);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    echo "Connected\n";
    ftp_pasv($conn_id, true);
    
    // File 1: routes/web.php
    $local_file1 = 'D:\laragon\www\SistemMonitoringTugasPKL\routes\web.php';
    $remote_file1 = 'htdocs/laravel/routes/web.php';
    if (ftp_put($conn_id, $remote_file1, $local_file1, FTP_BINARY)) {
        echo "Successfully uploaded $remote_file1\n";
    } else {
        echo "There was a problem while uploading $remote_file1\n";
    }
    
    // File 2: app/Http/Controllers/Api/SyncController.php
    $local_file2 = 'D:\laragon\www\SistemMonitoringTugasPKL\app\Http\Controllers\Api\SyncController.php';
    $remote_file2 = 'htdocs/laravel/app/Http/Controllers/Api/SyncController.php';
    if (ftp_put($conn_id, $remote_file2, $local_file2, FTP_BINARY)) {
        echo "Successfully uploaded $remote_file2\n";
    } else {
        echo "There was a problem while uploading $remote_file2\n";
    }

} else {
    echo "Couldn't connect\n";
}
ftp_close($conn_id);
