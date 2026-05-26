<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);



/**
 * Fonction robuste pour récupérer du JSON via cURL
 */
function getJson($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true, // mettre false si ton hébergeur bloque SSL
        CURLOPT_USERAGENT => 'Mozilla/5.0', // logs.tf peut le demander
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        error_log("Erreur cURL : " . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);
    return json_decode($response, true);
}

$blacklist = [4040598];
$url_old = "https://logs.tf/api/v1/log?title=Highlander%20France";
$url_new = "https://logs.tf/api/v1/log?title=highlanderfrance.tf";

$responseOld = getJson($url_old);
$responseNew = getJson($url_new);

// get logs from logs.tf
if (!$responseOld && !$responseNew) {
    die("Erreur : Impossible de contacter logs.tf pour les deux requêtes.");
}

$logsOld = $responseOld["logs"] ?? [];
$logsNew = $responseNew["logs"] ?? [];

$mergedLogs = [];
foreach (array_merge($logsOld, $logsNew) as $l) {
    if (isset($l['id'])) {
        $mergedLogs[$l['id']] = $l;
    }
}

// Filtrage de la Blacklist
$filteredLogs = array_filter($mergedLogs, function($log) use ($blacklist) {
    return !in_array($log["id"], $blacklist);
});

// Tri par ID décroissant pour s'assurer de retirer les 4 plus anciens à la fin
usort($filteredLogs, function($a, $b) {
    return $b['id'] <=> $a['id'];
});

// Retrait des 4 plus anciens de la liste
if (count($filteredLogs) > 4) {
    $filteredLogs = array_slice($filteredLogs, 0, -4);
}

foreach ($filteredLogs as $log) {
    $match_id = $log["id"];

    $stmt = $db->prepare("SELECT length FROM matches_cache WHERE match_id = ?");
    $stmt->execute([$match_id]);
    $cached_match = $stmt->fetch();

    if (!$cached_match) { // if match missing, get it
        $details = getJson("https://logs.tf/api/v1/log/" . $match_id);

        if (!$details) {
            error_log("Erreur 502/404 pour le match $match_id - On passe au suivant.");
            continue; 
        }

        $length = $details["length"] ?? 0;

        $ins = $db->prepare("INSERT INTO matches_cache (match_id, length) VALUES (?, ?)");
        $ins->execute([$match_id, $length]);

        usleep(200000); // 0.2s pour éviter de spam l'API
    }
}

$placeholders = implode(',', array_fill(0, count($filteredLogs), '?'));
$ids_filtres = array_column($filteredLogs, 'id');

$stmt_final = $db->prepare("SELECT COUNT(*) as nb, SUM(length) as total FROM matches_cache WHERE match_id IN ($placeholders)");
$stmt_final->execute($ids_filtres);
$stats = $stmt_final->fetch();

$result = [
    "matches" => (int)$stats['nb'],
    "hours" => round($stats['total'] / 3600)
];

file_put_contents(__DIR__ . '/cache_hlfr_stats.json', json_encode($result));
file_put_contents(__DIR__ . '/log_update_index_stats.txt', date('Y-m-d H:i:s') . " OK\n", FILE_APPEND);

echo "Mise à jour réussie : " . $stats['nb'] . " matchs traités.";
