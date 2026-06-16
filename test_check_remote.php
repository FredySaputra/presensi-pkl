<?php
$ftp_server='ftpupload.net';
$conn_id=ftp_connect($ftp_server);
ftp_login($conn_id,'if0_41855033','panascak1');
ftp_pasv($conn_id,true);
$temp=fopen('php://temp','r+');
ftp_fget($conn_id,$temp,'htdocs/laravel/routes/web.php',FTP_BINARY,0);
rewind($temp);
echo stream_get_contents($temp);
fclose($temp);
ftp_close($conn_id);
