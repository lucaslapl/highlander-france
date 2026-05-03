<?php
$db = new PDO('sqlite:' . __DIR__ . '/stats.db');
$query = $db->query("SELECT p.name, p.avatar, s.count 
                     FROM player_stats s 
                     JOIN players_info p ON s.steamid = p.steamid 
                     ORDER BY s.count DESC LIMIT 18");

$results = $query->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/leaderboard_cache.json', json_encode($results));