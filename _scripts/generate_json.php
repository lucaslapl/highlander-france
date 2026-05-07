<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

$db = new PDO('sqlite:' . __DIR__ . '/stats.db');
$query = $db->query("SELECT 
                        COALESCE(p.display_name, p.name) AS name, 
                        p.avatar,
                        p.steamid, 
                        s.count 
                     FROM player_stats s 
                     JOIN players_info p ON s.steamid = p.steamid 
                     ORDER BY s.count DESC LIMIT 18");

$rows = $query->fetchAll(PDO::FETCH_ASSOC);
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

file_put_contents(__DIR__ . '/leaderboard_cache.json', json_encode($final_results));