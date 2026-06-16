<?php
$url = "https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/calendar.json";
$response = file_get_contents($url);
$data = json_decode($response, true);
foreach ($data as $date => $info) {
    if (strpos($date, '2026-') === 0) {
        echo "$date : " . implode(", ", $info['description']) . " | " . implode(", ", $info['summary']) . "\n";
    }
}
