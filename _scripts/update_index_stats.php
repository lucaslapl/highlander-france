<?php
// On s'assure qu'on est en CLI (Command Line Interface)
if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.");
}

require_once __DIR__ . '/../_inc/config.php';

// 1. Vos réglages
$blacklist = [4040598];
$url_api_list = "https://logs.tf/api/v1/log?title=Highlander%20France";

// 2. Récupération de la liste des logs (la liste globale est légère)
$response = json_decode(@file_get_contents($url_api_list), true);
if (!$response) die("Erreur : Impossible de contacter logs.tf");

$logs = $response["logs"];

// 3. Application de vos filtres
$logs = array_filter($logs, function($log) use ($blacklist) {
    return !in_array($log["id"], $blacklist);
});
$logs = array_slice($logs, 0, -4); // On retire les 4 plus anciens

// 4. Boucle intelligente
foreach ($logs as $log) {
    $match_id = $log["id"];

    // On vérifie si on a déjà la durée en BDD
    $stmt = $db->prepare("SELECT length FROM matches_cache WHERE match_id = ?");
    $stmt->execute([$match_id]);
    $cached_match = $stmt->fetch();

    if (!$cached_match) {
        // MATCH INCONNU : On appelle l'API pour les détails (Le moment critique)
        $details_json = @file_get_contents("https://logs.tf/api/v1/log/" . $match_id);
        
        if ($details_json === false) {
            error_log("Erreur 502/404 pour le match $match_id - On passe au suivant.");
            continue; 
        }

        $details = json_decode($details_json, true);
        $length = $details["length"] ?? 0;

        // On l'enregistre pour la prochaine fois
        $ins = $db->prepare("INSERT INTO matches_cache (match_id, length) VALUES (?, ?)");
        $ins->execute([$match_id, $length]);
        
        usleep(200000); // Pause de 0.2s pour ne pas spammer l'API
    }
}

// 5. Calcul final à partir de la BDD (Rapide et fiable)
// On ne compte que les matchs qui sont présents dans notre liste filtrée $logs
$placeholders = implode(',', array_fill(0, count($logs), '?'));
$ids_filtres = array_column($logs, 'id');

$stmt_final = $db->prepare("SELECT COUNT(*) as nb, SUM(length) as total FROM matches_cache WHERE match_id IN ($placeholders)");
$stmt_final->execute($ids_filtres);
$stats = $stmt_final->fetch();

$result = [
    "matches" => (int)$stats['nb'],
    "hours" => round($stats['total'] / 3600)
];

file_put_contents(__DIR__ . '/cache_hlfr_stats.json', json_encode($result));
echo "Mise à jour réussie : " . $stats['nb'] . " matchs traités.";