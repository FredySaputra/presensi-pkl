<?php
require 'vendor/autoload.php';

$ftp_server = 'ftpupload.net';
$ftp_user = 'if0_41855033';
$ftp_pass = 'panascak1';

$conn_id = ftp_connect($ftp_server);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    echo "Connected\n";
    ftp_pasv($conn_id, true);
    $contents = ftp_nlist($conn_id, "htdocs");
    echo "Contents of htdocs:\n";
    print_r($contents);
    
    $contents2 = ftp_nlist($conn_id, "htdocs/storage/app");
    echo "\nContents of htdocs/storage/app:\n";
    print_r($contents2);
} else {
    echo "Couldn't connect as $ftp_user\n";
}
ftp_close($conn_id);
