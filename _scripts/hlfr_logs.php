<?php
header('Content-Type: application/json');

$url = "https://logs.tf/api/v1/log?title=Highlander%20France";
$data = json_decode(file_get_contents($url), true);

// Liste des logs à exclure
$blacklist = [
    4040598
];

$filtered = [];

foreach ($data["logs"] as $log) {

    if (in_array($log["id"], $blacklist)) {
        continue;
    }

    $filtered[] = $log;
}

echo json_encode($filtered);