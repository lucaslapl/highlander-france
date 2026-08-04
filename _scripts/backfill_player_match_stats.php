<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

$logToken = logScriptExecution('backfill_player_match_stats.php');

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
    curl_close($ch);
    return ($response === false) ? false : json_decode($response, true);
}

try {
    // 1. Logs en base dont les stats ne sont pas encore remplies
    $missing = $db->query("
    SELECT DISTINCT match_id FROM player_matches
    WHERE length = 0 OR won IS NULL
")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($missing)) {
        logScriptExecution('backfill_player_match_stats.php', $logToken, 'SUCCESS (rien à backfiller)');
        echo "Aucun log à backfiller.";
        exit;
    }

    $updated = 0;
    foreach ($missing as $matchId) {
        $details = getJson("https://logs.tf/api/v1/log/$matchId");
        if (!$details || !isset($details['players'])) {
            continue;
        }

        // On relit les valeurs déjà en base (pour ne pas écraser les corrections admin)
        $stmt = $db->prepare("SELECT steamid, map_name, class_played, game_mode FROM player_matches WHERE match_id = ?");
        $stmt->execute([$matchId]);
        $playersInLog = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $perLogStats = extractLogPlayerStats($details);

        foreach ($playersInLog as $row) {
            upsertPlayerMatchStats(
                $db,
                $row['steamid'],
                $matchId,
                $row['map_name'],
                $row['class_played'],
                $row['game_mode'],
                $perLogStats[$row['steamid']] ?? []
            );
            $updated++;
        }
        usleep(300000);
    }

    logScriptExecution(
        'backfill_player_match_stats.php',
        $logToken,
        "SUCCESS ($updated stats joueurs mises à jour sur " . count($missing) . " logs)"
    );
    echo "Backfill terminé : $updated stats mises à jour sur " . count($missing) . " logs.";
} catch (Exception $e) {
    logScriptExecution('backfill_player_match_stats.php', $logToken, 'FAILED: ' . $e->getMessage());
    die("Erreur critique : " . $e->getMessage());
}
