<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

$logToken = logScriptExecution('backfill_log_dates.php');

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
    // 1. Les deux index logs.tf (limit 200 chacun => couvre ~les 204 logs en base)
    $dataOld = getJson("https://logs.tf/api/v1/log?title=Highlander%20France&limit=200");
    $dataNew = getJson("https://logs.tf/api/v1/log?title=highlanderfrance.tf&limit=200");

    if (!$dataOld && !$dataNew) {
        throw new Exception("Impossible de récupérer l'index initial sur logs.tf");
    }

    $dates = [];
    foreach (array_merge($dataOld["logs"] ?? [], $dataNew["logs"] ?? []) as $l) {
        if (isset($l['id'])) {
            $dates[$l['id']] = $l['date'] ?? 0;
        }
    }

    // 2. Upsert des dates connues
    $upsert = $db->prepare("INSERT OR IGNORE INTO log_dates (log_id, date) VALUES (?, ?)");
    $covered = 0;
    foreach ($dates as $id => $date) {
        $upsert->execute([$id, $date]);
        $covered++;
    }

    // 3. Compléter les trous : matchs en base sans date (fetch détail individuel)
    $missing = $db->query("SELECT DISTINCT pm.match_id
        FROM player_matches pm
        LEFT JOIN log_dates ld ON ld.log_id = pm.match_id
        WHERE ld.log_id IS NULL")->fetchAll(PDO::FETCH_COLUMN);

    $fetched = 0;
    foreach ($missing as $matchId) {
        $details = getJson("https://logs.tf/api/v1/log/$matchId");
        if ($details && isset($details['date'])) {
            $upsert->execute([$matchId, $details['date']]);
            $fetched++;
            usleep(300000);
        }
    }

    // 4. Récapitulatif
    $remaining = count($missing) - $fetched;
    logScriptExecution('backfill_log_dates.php', $logToken,
        "SUCCESS ($covered dates depuis l'index, $fetched complétées, $remaining manquantes)");

    echo "Backfill terminé : $covered depuis l'index, $fetched complétées individuellement, $remaining manquantes.";
} catch (Exception $e) {
    logScriptExecution('backfill_log_dates.php', $logToken, 'FAILED: ' . $e->getMessage());
    die("Erreur critique : " . $e->getMessage());
}