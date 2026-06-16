<?php
$ftp_server = 'ftpupload.net';
$ftp_user = 'if0_41855033';
$ftp_pass = 'panascak1';
$file_path = 'htdocs/laravel/storage/app/sync/test_curl_active.txt';
$url = "ftp://$ftp_user:$ftp_pass@$ftp_server/$file_path";

$ch = curl_init();
$fp = fopen('php://temp', 'r+');
fwrite($fp, 'test curl active');
rewind($fp);

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_UPLOAD, 1);
curl_setopt($ch, CURLOPT_INFILE, $fp);
curl_setopt($ch, CURLOPT_INFILESIZE, strlen('test curl active'));
curl_setopt($ch, CURLOPT_FTPPORT, '-'); // FORCE ACTIVE MODE IN CURL

$result = curl_exec($ch);
if ($result) {
    echo "SUCCESS\n";
} else {
    echo "ERROR: " . curl_error($ch) . "\n";
}
curl_close($ch);
fclose($fp);
