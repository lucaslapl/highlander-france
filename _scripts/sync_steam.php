<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Forbidden');
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

$env = parse_ini_file(__DIR__ . '/.env');
$STEAM_API_KEY = $env['STEAM_API_KEY'];

// Log helper
function log_msg($msg) {
    file_put_contents(__DIR__ . '/log_sync_steam.txt', date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Vérification BCMath
if (!function_exists('bcadd')) {
    log_msg("ERREUR : BCMath n'est pas activé en CLI !");
}

// Fonction cURL
function getSteamData($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        log_msg("Erreur cURL : $error");
        return false;
    }

    return json_decode($response, true);
}

// Récupération des IDs manquants
$query = $db->query("SELECT DISTINCT s.steamid FROM player_stats s 
                     LEFT JOIN players_info p ON s.steamid = p.steamid 
                     WHERE p.steamid IS NULL");
$missing = $query->fetchAll(PDO::FETCH_COLUMN);

if (empty($missing)) {
    log_msg("Aucun nouveau profil à traiter.");
    die("Aucun nouveau profil à traiter.");
}

log_msg("Nombre d'IDs à traiter : " . count($missing));

$chunks = array_chunk($missing, 100);

foreach ($chunks as $chunk) {

    // Conversion SteamID3 → SteamID64
    $ids64 = array_map('steamid3To64', $chunk);
    $idsParam = implode(',', $ids64);

    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$idsParam";

    $data = getSteamData($url);

    if (!$data) {
        log_msg("Erreur API Steam pour chunk : $idsParam");
        continue;
    }

    if (!isset($data['response']['players'])) {
        log_msg("Réponse Steam invalide pour chunk : $idsParam");
        continue;
    }

    foreach ($data['response']['players'] as $p) {
        $originalId = "[U:1:" . ($p['steamid'] - 76561197960265728) . "]";

        $stmt = $db->prepare("INSERT INTO players_info (steamid, name, avatar, last_updated) VALUES (?, ?, ?, ?)");
        $stmt->execute([$originalId, $p['personaname'], $p['avatarfull'], time()]);

        log_msg("Ajouté : " . $p['personaname']);
    }

    sleep(1);
}

log_msg("Synchronisation terminée avec succès.");
echo "Synchronisation terminée avec succès.";
