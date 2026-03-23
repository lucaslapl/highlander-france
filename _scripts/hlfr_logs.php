<?php
header('Content-Type: application/json');

$url = "https://logs.tf/api/v1/log?title=Highlander%20France";
$data = json_decode(file_get_contents($url), true);

$filtered = [];

foreach ($data["logs"] as $log) {
    $filtered[] = $log;
}

echo json_encode($filtered);
