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
    // Seuil minimum de matchs pour les classements de stats (évite qu'un joueur à 1 match domine)
    $minMatches = 5;

    // Le classement DPM dépend des colonnes length / dapm (ajoutées par migrate_player_match_stats.php)
    $dapmAvailable = playerMatchColumnExists($db, 'length') && playerMatchColumnExists($db, 'dapm');

    $modes = ['6s', '9v9'];
    $updatedModes = [];

    // Définition des catégories : requête + clé de sortie + suffixe du fichier de cache
    $categories = [
        'matches' => [
            'value_key' => 'count',
            'suffix'    => '',
            'sql'       => "SELECT COALESCE(p.display_name, p.name) AS name,
                                   p.avatar, p.steamid, s.count AS value
                            FROM player_stats s
                            JOIN players_info p ON s.steamid = p.steamid
                            WHERE s.game_mode = ?
                            ORDER BY s.count DESC LIMIT 18",
        ],
        'kills' => [
            'value_key' => 'value',
            'suffix'    => '_kills',
            'sql'       => "SELECT COALESCE(p.display_name, p.name) AS name,
                                   p.avatar, p.steamid, SUM(pm.kills) AS value
                            FROM player_matches pm
                            JOIN players_info p ON p.steamid = pm.steamid
                            WHERE pm.game_mode = ? AND p.created_at IS NOT NULL
                            GROUP BY pm.steamid
                            HAVING COUNT(*) >= $minMatches
                            ORDER BY value DESC LIMIT 18",
        ],
        'heal' => [
            'value_key' => 'value',
            'suffix'    => '_heal',
            'sql'       => "SELECT COALESCE(p.display_name, p.name) AS name,
                                   p.avatar, p.steamid, SUM(pm.heal) AS value
                            FROM player_matches pm
                            JOIN players_info p ON p.steamid = pm.steamid
                            WHERE pm.game_mode = ? AND p.created_at IS NOT NULL
                            GROUP BY pm.steamid
                            HAVING COUNT(*) >= $minMatches
                            ORDER BY value DESC LIMIT 18",
        ],
        'dpm' => [
            'value_key' => 'value',
            'suffix'    => '_dpm',
            'sql'       => "SELECT COALESCE(p.display_name, p.name) AS name,
                                   p.avatar, p.steamid,
                                   AVG(CASE WHEN pm.length > 0 THEN pm.dapm END) AS value
                            FROM player_matches pm
                            JOIN players_info p ON p.steamid = pm.steamid
                            WHERE pm.game_mode = ? AND p.created_at IS NOT NULL
                            GROUP BY pm.steamid
                            HAVING COUNT(*) >= $minMatches AND value IS NOT NULL
                            ORDER BY value DESC LIMIT 18",
        ],
    ];

    // Si les colonnes DPM ne sont pas encore migrées, on désactive cette catégorie
    if (!$dapmAvailable) {
        unset($categories['dpm']);
    }

    foreach ($modes as $mode) {
        foreach ($categories as $category) {
            $stmt = $db->prepare($category['sql']);
            $stmt->execute([$mode]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $final_results = [];
            foreach ($rows as $row) {
                $final_results[] = [
                    'name'    => $row['name'],
                    'avatar'  => $row['avatar'],
                    'steamid' => steamID3To64($row['steamid']),
                    $category['value_key'] => $row['value'],
                ];
            }

            $filePath = __DIR__ . "/leaderboard_cache_{$mode}{$category['suffix']}.json";
            $writeResult = file_put_contents($filePath, json_encode($final_results), LOCK_EX);

            if ($writeResult === false) {
                throw new Exception("Impossible d'écrire le fichier de cache JSON pour le mode : " . $mode);
            }

            $updatedModes[] = $mode . $category['suffix'];
        }
    }

    // 3. SUCCÈS
    $statusMsg = "SUCCESS (Classements mis à jour : " . implode(', ', $updatedModes) . ")";
    logScriptExecution('generate_json.php', $logToken, $statusMsg);

    file_put_contents(__DIR__ . '/log_generate_json.txt', date('Y-m-d H:i:s') . " OK\n", FILE_APPEND);
    echo "Cache mis à jour avec succès.";

} catch (Exception $e) {
    logScriptExecution('generate_json.php', $logToken, 'FAILED: ' . $e->getMessage());
    die("Erreur lors de la mise à jour des caches de classement : " . $e->getMessage());
}
