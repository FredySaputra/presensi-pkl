<?php
require 'vendor/autoload.php';

$ftp_server = 'ftpupload.net';
$ftp_user = 'if0_41855033';
$ftp_pass = 'panascak1';

$conn_id = ftp_connect($ftp_server);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    echo "Connected\n";
    ftp_pasv($conn_id, true);
    
    $contents = ftp_nlist($conn_id, "htdocs/laravel/storage");
    echo "Contents of htdocs/laravel/storage:\n";
    print_r($contents);
    
    $contents2 = ftp_nlist($conn_id, "htdocs/laravel/storage/app");
    echo "\nContents of htdocs/laravel/storage/app:\n";
    print_r($contents2);

    $contents3 = ftp_nlist($conn_id, "htdocs/laravel/storage/app/sync");
    echo "\nContents of htdocs/laravel/storage/app/sync:\n";
    print_r($contents3);

} else {
    echo "Couldn't connect as $ftp_user\n";
}
ftp_close($conn_id);
