<?php
require 'vendor/autoload.php';

$ftp_server = 'ftpupload.net';
$ftp_user = 'if0_41855033';
$ftp_pass = 'panascak1';

$conn_id = ftp_connect($ftp_server);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    echo "Connected\n";
    ftp_pasv($conn_id, true);
    
    if (ftp_chdir($conn_id, "/htdocs/laravel/storage/app/sync")) {
        echo "Changed to /htdocs/...\n";
    } else {
        echo "Failed to change to /htdocs/...\n";
    }

    if (ftp_chdir($conn_id, "htdocs/laravel/storage/app/sync")) {
        echo "Changed to htdocs/...\n";
    } else {
        echo "Failed to change to htdocs/...\n";
    }
} else {
    echo "Couldn't connect\n";
}
ftp_close($conn_id);
