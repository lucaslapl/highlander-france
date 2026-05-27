<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Initialisation du log d'audit
$logToken = logScriptExecution('update_stats.php');

$env = parse_ini_file(__DIR__ . '/.env');
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
        CURLOPT_SSL_VERIFYPEER => true, 
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

// 2. Encapsulation globale pour traquer l'état d'exécution
try {
    $dataOld = getJson("https://logs.tf/api/v1/log?title=Highlander%20France");
    $dataNew = getJson("https://logs.tf/api/v1/log?title=highlanderfrance.tf");

    // Si logs.tf est complètement inaccessible au démarrage
    if (!$dataOld && !$dataNew) {
        throw new Exception("Impossible de récupérer l'index initial sur logs.tf");
    }

    $logsOld = $dataOld["logs"] ?? [];
    $logsNew = $dataNew["logs"] ?? [];

    $mergedLogs = array_merge($logsOld, $logsNew);

    // Nettoyage des doublons éventuels
    $allLogs = [];
    foreach ($mergedLogs as $l) {
        if (isset($l['id'])) {
            $allLogs[$l['id']] = $l;
        }
    }

    $processedCount = 0;

    foreach ($allLogs as $log) {
        $logId = $log['id'];
        $title = $log['title'] ?? '';
        
        $stmt = $db->prepare("SELECT 1 FROM processed_logs WHERE id = ?");
        $stmt->execute([$logId]);
        
        if (!$stmt->fetch()) {

            // get log details
            $details = getJson("https://logs.tf/api/v1/log/$logId");
            if (!$details) {
                error_log("Erreur API logs.tf pour le log $logId");
                continue;
            }

            // get game mode
            $titleLower = strtolower($title);
            $gameMode = '9v9'; 
            
            if (strpos($titleLower, "[6s]") !== false) {
                $gameMode = '6s';
            } elseif (strpos($titleLower, "[9s]") !== false) {
                $gameMode = '9v9';
            }

            $rawMap = $details['info']['map'] ?? 'unknown';
            $mapName = preg_replace('/_(v|rc|f)\d+.*?$/i', '', $rawMap);

            if (isset($details['players'])) {
                foreach ($details['players'] as $steamid => $pData) {

                    // update stats
                    $db->prepare("INSERT INTO player_stats (steamid, count, game_mode) VALUES (?, 1, ?) 
                                  ON CONFLICT(steamid, game_mode) DO UPDATE SET count = count + 1")
                       ->execute([$steamid, $gameMode]);

                    $classPlayed = 'unknown';
                    if (!empty($pData['class_stats']) && isset($pData['class_stats'][0]['type'])) {
                        $classPlayed = $pData['class_stats'][0]['type'];
                    }
                    $db->prepare("INSERT OR IGNORE INTO player_matches (steamid, match_id, map_name, class_played, game_mode) 
                                  VALUES (?, ?, ?, ?, ?)")
                       ->execute([$steamid, $logId, $mapName, $classPlayed, $gameMode]);

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
            $processedCount++;
            usleep(200000);
        }
    }

    // 3. SUCCÈS : Enregistrement de la réussite dans cron_debug.log
    $statusMsg = "SUCCESS (" . $processedCount . " nouveaux logs traités)";
    logScriptExecution('update_stats.php', $logToken, $statusMsg);

    // Ton fichier de log d'historique classique
    file_put_contents(__DIR__ . '/log_update_stats.txt', date('Y-m-d H:i:s') . " OK\n", FILE_APPEND);
    echo "Mise à jour des stats terminée. Nouveaux logs traités : " . $processedCount;

} catch (Exception $e) {
    
    // 4. ÉCHEC : On intercepte l'erreur critique et on ferme la ligne d'audit sur un FAILED
    logScriptExecution('update_stats.php', $logToken, 'FAILED: ' . $e->getMessage());
    
    die("Erreur critique durant la génération des statistiques : " . $e->getMessage());
}