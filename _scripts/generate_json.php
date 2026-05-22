<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Forbidden');
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

$modes = ['6s', '9v9'];

foreach ($modes as $mode) {

    $stmt = $db->query("SELECT 
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

    file_put_contents(__DIR__ . "/leaderboard_cache_{$mode}.json", json_encode($final_results));

}

file_put_contents(__DIR__ . '/log_generate_json.txt', date('Y-m-d H:i:s') . " OK\n", FILE_APPEND);
echo "Cache mis à jour avec succès.";