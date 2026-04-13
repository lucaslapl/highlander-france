<?php
header('Content-Type: application/json');

// Fichier de cache
$cacheFile = __DIR__ . '/cache_hlfr_stats.json';
$cacheDuration = 3600; // 1 heure

// --- 1. Vérifier si un cache valide existe ---
if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);

    if (time() - $cache['timestamp'] < $cacheDuration) {
        echo json_encode($cache['data']);
        exit;
    }
}

// --- 2. Sinon, on génère les stats en appelant l'API logs.tf ---

$url = "https://logs.tf/api/v1/log?title=Highlander%20France";
$data = json_decode(file_get_contents($url), true);

// Liste des logs à exclure
$blacklist = [
    4040598
    // ajoute ici d'autres IDs si nécessaire
];

// 4 premiers logs non pris en compte
$logs = array_slice($data["logs"], 0, -4);

// Filtrer la blacklist
$logs = array_filter($logs, function($log) use ($blacklist) {
    return !in_array($log["id"], $blacklist);
});

$totalMatches = 0;
$totalSeconds = 0;

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
