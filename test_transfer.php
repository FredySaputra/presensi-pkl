<?php
$data = 'test data';
$ch = curl_init('https://transfer.sh/test.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_PUT, true);
$fp = fopen('php://temp', 'r+');
fwrite($fp, $data);
rewind($fp);
curl_setopt($ch, CURLOPT_INFILE, $fp);
curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
$response = curl_exec($ch);
echo "Response: $response\n";
