<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

header('Content-Type: application/json');

$url_old = "https://logs.tf/api/v1/log?title=Highlander%20France";
$url_new = "https://logs.tf/api/v1/log?title=highlanderfrance.tf";

$data_old = json_decode(@file_get_contents($url_old), true);
$data_new = json_decode(@file_get_contents($url_new), true);

$logs_old = $data_old["logs"] ?? [];
$logs_new = $data_new["logs"] ?? [];

$blacklist = getBlacklistedLogIds($db);

$filtered = [];

foreach (array_merge($logs_old, $logs_new) as $log) {
    $log_id = $log["id"] ?? null;

    if (!$log_id || in_array($log_id, $blacklist)) {
        continue;
    }

    $filtered[$log_id] = $log;
}

usort($filtered, function($a, $b) {
    return $b['id'] <=> $a['id'];
});

echo json_encode(array_values($filtered));