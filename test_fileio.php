<?php
$data = 'test data';
file_put_contents('test.json', $data);
$ch = curl_init('https://file.io/?expires=1d');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
$cfile = curl_file_create('test.json', 'application/json', 'test.json');
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
$response = curl_exec($ch);
echo "Response: $response\n";
