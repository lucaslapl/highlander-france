<?php
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

$env = parse_ini_file(__DIR__ . '/.env');
require_once __DIR__ . '/../_inc/config.php';
$STEAM_API_KEY = $env['STEAM_API_KEY'];

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
        CURLOPT_USERAGENT => 'Mozilla/5.0',
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

$data = getJson("https://logs.tf/api/v1/log?title=Highlander%20France");
$allLogs = $data["logs"] ?? [];

foreach ($allLogs as $log) {
    $logId = $log['id'];
    
    $stmt = $db->prepare("SELECT 1 FROM processed_logs WHERE id = ?");
    $stmt->execute([$logId]);
    
    if (!$stmt->fetch()) {

        // get log details
        $details = getJson("https://logs.tf/api/v1/log/$logId");
        if (!$details) {
            error_log("Erreur API logs.tf pour le log $logId");
            continue;
        }

        if (isset($details['players'])) {
            foreach ($details['players'] as $steamid => $pData) {

                // update stats
                $db->prepare("INSERT INTO player_stats (steamid, count) VALUES (?, 1) 
                              ON CONFLICT(steamid) DO UPDATE SET count = count + 1")
                   ->execute([$steamid]);

                // check if player info exists
                $stmtCheck = $db->prepare("SELECT 1 FROM players_info WHERE steamid = ?");
                $stmtCheck->execute([$steamid]);

                if (!$stmtCheck->fetch()) {

                    // get steam profile
                    $steamUrl = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$steamid";
                    $sData = getJson($steamUrl);

                    if (isset($sData['response']['players'][0])) {
                        $p = $sData['response']['players'][0];
                        $db->prepare("INSERT INTO players_info (steamid, name, avatar, last_updated) VALUES (?, ?, ?, ?)")
                           ->execute([$steamid, $p['personaname'], $p['avatarfull'], time()]);
                    }

                    usleep(500000);
                }
            }
        }

        $db->prepare("INSERT INTO processed_logs (id) VALUES (?)")->execute([$logId]);
        usleep(200000);
    }
}

file_put_contents(__DIR__ . '/log_update_stats.txt', date('Y-m-d H:i:s') . " OK\n", FILE_APPEND);
echo "Mise à jour des stats terminée.";
