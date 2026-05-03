<?php
// On s'assure qu'on est en CLI (Command Line Interface)
if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.");
}

// 1. Récupération des données (votre logique actuelle)
$url = "https://logs.tf/api/v1/log?title=Highlander%20France";
$data = json_decode(file_get_contents($url), true);

$blacklist = [4040598];
$logs = array_slice($data["logs"], 0, -4);
$logs = array_filter($logs, function($log) use ($blacklist) {
    return !in_array($log["id"], $blacklist);
});

$totalMatches = 0;
$totalSeconds = 0;

foreach ($logs as $log) {
    $totalMatches++;
    $detailsUrl = "https://logs.tf/api/v1/log/" . $log["id"];
    $details = json_decode(file_get_contents($detailsUrl), true);
    if (isset($details["length"])) {
        $totalSeconds += $details["length"];
    }
    // Petite pause pour ne pas surcharger l'API logs.tf (bonne pratique)
    usleep(200000); 
}

$result = [
    "matches" => $totalMatches,
    "hours" => round($totalSeconds / 3600)
];

// 2. Sauvegarde dans le fichier cache (sans structure JSON complexe)
file_put_contents(__DIR__ . '/cache_hlfr_stats.json', json_encode($result));