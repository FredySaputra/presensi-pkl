<?php
$url = 'https://pkl-monitoring.page.gd/api/sync/all';
$data = ['payload' => '{}', 'api_key' => 'RAHASIA123_GANTI_DI_ENV'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpcode\n";
echo "Response: $response\n";
curl_close($ch);
