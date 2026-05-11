<?php
$env = parse_ini_file(__DIR__ . '/.env');
require_once __DIR__ . '/../_inc/config.php';
$STEAM_API_KEY = $env['STEAM_API_KEY'];

$data = json_decode(file_get_contents("https://logs.tf/api/v1/log?title=Highlander%20France"), true);
$allLogs = $data["logs"] ?? [];

foreach ($allLogs as $log) {
    $logId = $log['id'];
    
    $stmt = $db->prepare("SELECT 1 FROM processed_logs WHERE id = ?");
    $stmt->execute([$logId]);
    
    if (!$stmt->fetch()) {
        // if new log, process it
        $details = json_decode(file_get_contents("https://logs.tf/api/v1/log/$logId"), true);
        
        if (isset($details['players'])) {
            // if new player, add to stats and get steam profile if missing
            foreach ($details['players'] as $steamid => $pData) {
                $db->prepare("INSERT INTO player_stats (steamid, count) VALUES (?, 1) 
                              ON CONFLICT(steamid) DO UPDATE SET count = count + 1")
                   ->execute([$steamid]);
                
                $stmtCheck = $db->prepare("SELECT 1 FROM players_info WHERE steamid = ?");
                $stmtCheck->execute([$steamid]);
                if (!$stmtCheck->fetch()) {
                    $steamUrl = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$steamid";
                    $sData = json_decode(file_get_contents($steamUrl), true);
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
        usleep(200000);
    }
}