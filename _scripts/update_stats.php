<?php
$env = parse_ini_file(__DIR__ . '/.env');

$db = new PDO('sqlite:' . __DIR__ . '/stats.db');
$STEAM_API_KEY = $env['STEAM_API_KEY'];

// 1. Récupérer la liste des logs récents
$data = json_decode(file_get_contents("https://logs.tf/api/v1/log?title=Highlander%20France"), true);
$allLogs = $data["logs"] ?? [];

foreach ($allLogs as $log) {
    $logId = $log['id'];
    
    // Vérifier si déjà traité
    $stmt = $db->prepare("SELECT 1 FROM processed_logs WHERE id = ?");
    $stmt->execute([$logId]);
    
    if (!$stmt->fetch()) {
        // Nouveau log : on le récupère
        $details = json_decode(file_get_contents("https://logs.tf/api/v1/log/$logId"), true);
        
        if (isset($details['players'])) {
            foreach ($details['players'] as $steamid => $pData) {
                // 1. Incrémenter le score
                $db->prepare("INSERT INTO player_stats (steamid, count) VALUES (?, 1) 
                              ON CONFLICT(steamid) DO UPDATE SET count = count + 1")
                   ->execute([$steamid]);
                
                // 2. Si on n'a pas les infos du joueur, on va chercher sur Steam
                $stmtCheck = $db->prepare("SELECT 1 FROM players_info WHERE steamid = ?");
                $stmtCheck->execute([$steamid]);
                if (!$stmtCheck->fetch()) {
                    // Appel API Steam pour le nom/avatar
                    $steamUrl = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$steamid";
                    $sData = json_decode(file_get_contents($steamUrl), true);
                    if (isset($sData['response']['players'][0])) {
                        $p = $sData['response']['players'][0];
                        $db->prepare("INSERT INTO players_info (steamid, name, avatar, last_updated) VALUES (?, ?, ?, ?)")
                           ->execute([$steamid, $p['personaname'], $p['avatarfull'], time()]);
                    }
                    usleep(500000); // Pause anti-spam API Steam
                }
            }
        }
        // Marquer comme traité
        $db->prepare("INSERT INTO processed_logs (id) VALUES (?)")->execute([$logId]);
        usleep(200000); // Pause anti-spam logs.tf
    }
}