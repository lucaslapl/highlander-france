<?php
header('Content-Type: application/json');

// Fichier de cache
$cacheFile = __DIR__ . '/cache_hlfr_stats.json';
$cacheDuration = 3600; // 1 heure

// --- 1. Vérifier si un cache valide existe ---
if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);

    // Si le cache est encore valide, on le renvoie directement
    if (time() - $cache['timestamp'] < $cacheDuration) {
        echo json_encode($cache['data']);
        exit;
    }
}

// --- 2. Sinon, on génère les stats en appelant l'API logs.tf ---

$url = "https://logs.tf/api/v1/log?title=Highlander%20France";
$data = json_decode(file_get_contents($url), true);

$totalMatches = 0;
$totalSeconds = 0;

// 4 premiers logs non pris en compte
$logs = array_slice($data["logs"], 0, -4);

foreach ($logs as $log) {

    $totalMatches++;

    $logId = $log["id"];
    $detailsUrl = "https://logs.tf/api/v1/log/$logId";
    $details = json_decode(file_get_contents($detailsUrl), true);

    if (isset($details["length"])) {
        $totalSeconds += $details["length"];
    }
}

$totalHours = round($totalSeconds / 3600);

// Données finales
$result = [
    "matches" => $totalMatches,
    "hours" => $totalHours
];

// --- 3. Sauvegarde dans le cache ---
file_put_contents($cacheFile, json_encode([
    "timestamp" => time(),
    "data" => $result
], JSON_PRETTY_PRINT));

// --- 4. Retour JSON ---
echo json_encode($result);
