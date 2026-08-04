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
function getJson($url)
{
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

    // Cache des dates logs.tf (utilisé par les graphiques du dashboard admin)
    $stmtDateCache = $db->prepare("INSERT OR IGNORE INTO log_dates (log_id, date) VALUES (?, ?)");
    foreach ($allLogs as $log) {
        $stmtDateCache->execute([$log['id'], $log['date'] ?? 0]);
    }

    $processedCount = 0;

    $blacklistedLogIds = getBlacklistedLogIds($db);
    // Purge rétroactive : les logs blacklistés déjà traités sont retirés des stats joueurs
    $purgedCount = 0;
    if (!empty($blacklistedLogIds)) {
        $ph = implode(',', array_fill(0, count($blacklistedLogIds), '?'));
        $stmtList = $db->prepare("SELECT match_id, steamid, game_mode FROM player_matches WHERE match_id IN ($ph)");
        $stmtList->execute($blacklistedLogIds);
        $matchesToPurge = $stmtList->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matchesToPurge as $m) {
            $db->prepare("UPDATE player_stats SET count = count - 1 WHERE steamid = ? AND game_mode = ?")
                ->execute([$m['steamid'], $m['game_mode']]);
        }
        $db->exec("DELETE FROM player_stats WHERE count <= 0");

        $stmtDel = $db->prepare("DELETE FROM player_matches WHERE match_id IN ($ph)");
        $stmtDel->execute($blacklistedLogIds);
        $purgedCount = $stmtDel->rowCount();
    }

    foreach ($allLogs as $log) {
        $logId = $log['id'];
        // Log blacklisté : exclu de toutes les statistiques
        if (in_array($logId, $blacklistedLogIds)) {
            continue;
        }
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

            // Auto-blacklist : un log de moins de 5 minutes est exclu de toutes les stats
            $logLength = (int)($details['length'] ?? 0);
            if ($logLength > 0 && $logLength < MIN_MATCH_LENGTH) {
                blacklistLog($db, $logId, 'Durée inférieure à 5 minutes (blacklist automatique)', 'auto');
                $db->prepare("INSERT OR IGNORE INTO processed_logs (id) VALUES (?)")->execute([$logId]);
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

            $perLogStats = extractLogPlayerStats($details);

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
                    $stats = $perLogStats[$steamid] ?? [];
                    upsertPlayerMatchStats($db, $steamid, $logId, $mapName, $classPlayed, $gameMode, $stats);

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
