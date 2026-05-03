<?php
header('Content-Type: application/json');

$cacheFile = __DIR__ . '/cache_hlfr_stats.json';

if (file_exists($cacheFile)) {
    echo file_get_contents($cacheFile);
} else {
    // Si le cache n'existe pas encore
    echo json_encode(["error" => "Stats en cours de calcul..."]);
}