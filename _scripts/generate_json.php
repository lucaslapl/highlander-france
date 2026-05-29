<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Initialisation du log d'audit
$logToken = logScriptExecution('generate_json.php');

// 2. Encapsulation globale pour intercepter le moindre problème (BDD ou écriture fichier)
try {
    $modes = ['6s', '9v9'];
    $updatedModes = [];

    foreach ($modes as $mode) {

        $stmt = $db->prepare("SELECT 
                                COALESCE(p.display_name, p.name) AS name, 
                                p.avatar,
                                p.steamid, 
                                s.count 
                             FROM player_stats s 
                             JOIN players_info p ON s.steamid = p.steamid 
                             WHERE s.game_mode = ?
                             ORDER BY s.count DESC LIMIT 18");

        $stmt->execute([$mode]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $final_results = [];

        foreach ($rows as $row) {
            $id64 = steamID3To64($row['steamid']);

            $final_results[] = [
                'name' => $row['name'],
                'avatar' => $row['avatar'],
                'steamid' => $id64,
                'count' => $row['count']
            ];
        }

        // Écriture du fichier JSON de cache pour le mode en cours
        $filePath = __DIR__ . "/leaderboard_cache_{$mode}.json";
        $writeResult = file_put_contents($filePath, json_encode($final_results), LOCK_EX);
        
        if ($writeResult === false) {
            throw new Exception("Impossible d'écrire le fichier de cache JSON pour le mode : " . $mode);
        }

        $updatedModes[] = $mode;
    }

    // 3. SUCCÈS : Tout s'est bien passé, on l'écrit dans le fichier d'audit
    $statusMsg = "SUCCESS (Classements mis à jour : " . implode(', ', $updatedModes) . ")";
    logScriptExecution('generate_json.php', $logToken, $statusMsg);

    // Historique classique conservé
    file_put_contents(__DIR__ . '/log_generate_json.txt', date('Y-m-d H:i:s') . " OK\n", FILE_APPEND);
    echo "Cache mis à jour avec succès.";

} catch (Exception $e) {
    
    // 4. ÉCHEC : Journalisation précise de la panne
    logScriptExecution('generate_json.php', $logToken, 'FAILED: ' . $e->getMessage());
    
    die("Erreur lors de la mise à jour des caches de classement : " . $e->getMessage());
}