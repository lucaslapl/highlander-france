<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

$logToken = logScriptExecution('backfill_match_teams.php');

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
    // 1. Logs en base sans équipe renseignée OU sans score enregistré
    $missing = $db->query("
        SELECT DISTINCT pm.match_id
        FROM player_matches pm
        LEFT JOIN match_scores ms ON ms.match_id = pm.match_id
        WHERE pm.team IS NULL OR ms.match_id IS NULL
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($missing)) {
        logScriptExecution('backfill_match_teams.php', $logToken, 'SUCCESS (rien à backfiller)');
        echo "Aucun log à backfiller.";
        exit;
    }

    $updatedPlayers = 0;
    $updatedScores = 0;
    $failed = 0;

    $stmtTeam = $db->prepare("UPDATE player_matches SET team = ? WHERE steamid = ? AND match_id = ?");
    $stmtScore = $db->prepare("INSERT INTO match_scores (match_id, red_score, blue_score)
                               VALUES (?, ?, ?)
                               ON CONFLICT(match_id) DO UPDATE SET
                                   red_score = excluded.red_score,
                                   blue_score = excluded.blue_score");

    foreach ($missing as $matchId) {
        $details = getJson("https://logs.tf/api/v1/log/$matchId");
        if (!$details || !isset($details['players'])) {
            $failed++;
            continue;
        }

        // 2. Équipe par joueur
        foreach ($details['players'] as $steamid => $pData) {
            $team = strtolower($pData['team'] ?? '');
            if (!in_array($team, ['red', 'blue'])) {
                continue;
            }
            $stmtTeam->execute([$team, $steamid, $matchId]);
            $updatedPlayers += $stmtTeam->rowCount();
        }

        // 3. Scores RED / BLU
        $redScore  = (int)($details['teams']['Red']['score'] ?? 0);
        $blueScore = (int)($details['teams']['Blue']['score'] ?? 0);
        $stmtScore->execute([$matchId, $redScore, $blueScore]);
        $updatedScores++;

        usleep(300000);
    }

    logScriptExecution(
        'backfill_match_teams.php',
        $logToken,
        "SUCCESS ($updatedPlayers équipes joueurs, $updatedScores scores sur " . count($missing) . " logs, $failed échecs)"
    );
    echo "Backfill terminé : $updatedPlayers équipes joueurs et $updatedScores scores mis à jour sur " . count($missing) . " logs ($failed échecs).";
} catch (Exception $e) {
    logScriptExecution('backfill_match_teams.php', $logToken, 'FAILED: ' . $e->getMessage());
    die("Erreur critique : " . $e->getMessage());
}
