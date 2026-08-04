<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

$logToken = logScriptExecution('migrate_player_match_stats.php');

$newColumns = [
    'dmg'                => 'INTEGER DEFAULT 0',
    'kills'              => 'INTEGER DEFAULT 0',
    'deaths'             => 'INTEGER DEFAULT 0',
    'assists'            => 'INTEGER DEFAULT 0',
    'suicides'           => 'INTEGER DEFAULT 0',
    'heal'               => 'INTEGER DEFAULT 0',
    'medkits'            => 'INTEGER DEFAULT 0',
    'ubers'              => 'INTEGER DEFAULT 0',
    'drops'              => 'INTEGER DEFAULT 0',
    'backstabs'          => 'INTEGER DEFAULT 0',
    'headshots'          => 'INTEGER DEFAULT 0',
    'longest_killstreak' => 'INTEGER DEFAULT 0',
    'classes_killed'     => 'TEXT DEFAULT NULL',
];

try {
    $existing = [];
    foreach ($db->query("PRAGMA table_info(player_matches)")->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $existing[$col['name']] = true;
    }

    $added = 0;
    foreach ($newColumns as $name => $definition) {
        if (!isset($existing[$name])) {
            $db->exec("ALTER TABLE player_matches ADD COLUMN $name $definition");
            $added++;
        }
    }

    logScriptExecution('migrate_player_match_stats.php', $logToken, "SUCCESS ($added colonnes ajoutées)");
    echo "Migration terminée : $added colonnes ajoutées à player_matches.";
} catch (Exception $e) {
    logScriptExecution('migrate_player_match_stats.php', $logToken, 'FAILED: ' . $e->getMessage());
    die("Erreur critique : " . $e->getMessage());
}