<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

header('Content-Type: application/json');

$cacheFile = __DIR__ . '/cache_hlfr_logs.json';
$cacheTtl  = 300; // 5 minutes

// Cache frais : on sert directement sans toucher l'API logs.tf
if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    echo file_get_contents($cacheFile);
    exit;
}

function fetchJson($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log("hlfr_logs.php - cURL error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    return json_decode($response, true);
}

$url_old = "https://logs.tf/api/v1/log?title=Highlander%20France";
$url_new = "https://logs.tf/api/v1/log?title=highlanderfrance.tf";

$data_old = fetchJson($url_old);
$data_new = fetchJson($url_new);

$logs_old = $data_old["logs"] ?? [];
$logs_new = $data_new["logs"] ?? [];

// Si l'API est injoignable, on renvoie le cache même expiré plutôt que rien
if (empty($logs_old) && empty($logs_new) && is_file($cacheFile)) {
    echo file_get_contents($cacheFile);
    exit;
}

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

$json = json_encode(array_values($filtered));

file_put_contents($cacheFile, $json, LOCK_EX);

echo $json;
