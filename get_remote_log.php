<?php
$ftp_server = 'ftpupload.net';
$ftp_user = 'if0_41855033';
$ftp_pass = 'panascak1';

$conn_id = ftp_connect($ftp_server);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    
    $remote_file = 'htdocs/laravel/storage/logs/laravel.log';
    $temp = fopen('php://temp', 'r+');
    if (ftp_fget($conn_id, $temp, $remote_file, FTP_BINARY, 0)) {
        rewind($temp);
        $content = stream_get_contents($temp);
        
        $lines = explode("\n", $content);
        // We want to find the latest "[YYYY-MM-DD HH:MM:SS] production.ERROR"
        $last_error_index = 0;
        foreach ($lines as $i => $line) {
            if (strpos($line, 'production.ERROR') !== false || strpos($line, 'local.ERROR') !== false) {
                $last_error_index = $i;
            }
        }
        
        // Print the lines from the last error
        $error_lines = array_slice($lines, $last_error_index, 30);
        echo implode("\n", $error_lines);
    } else {
        echo "Could not download log file.\n";
    }
    fclose($temp);
} else {
    echo "Couldn't connect\n";
}
ftp_close($conn_id);
