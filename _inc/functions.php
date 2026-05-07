<?php
function steamID64ToSteamID3($steamid64) {
    // La constante de base de Steam
    $steamid_constant = 76561197960265728;
    
    // Calcul de l'AccountID
    $account_id = bcsub($steamid64, (string)$steamid_constant);
    
    // Formatage final
    return "[U:1:" . $account_id . "]";
}

function steamID3ToSteamID64($steamid3) {
    // On extrait le nombre du format [U:1:XXXXXX]
    $account_id = str_replace(['[U:1:', ']'], '', $steamid3);
    // On ajoute la constante pour retrouver le SteamID64
    return bcadd($account_id, '76561197960265728');
}

function steamID3To64($steamID3) {
    // Extrait le chiffre après le deuxième deux-points [U:1:XXXXXXXX]
    if (preg_match('/\[U:1:(\d+)\]/', $steamID3, $matches)) {
        return bcadd($matches[1], '76561197960265728');
    }
    return null;
}

function syncSteamProfile($steamid3, $db, $apiKey) {
    $env = parse_ini_file(__DIR__ . '/.env');
    $STEAM_API_KEY = $env['STEAM_API_KEY'];
    $steamid64 = steamID3ToSteamID64($steamid3);
    
    // Appel à l'API Steam
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$steamid64";
    $json = @file_get_contents($url);
    
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['response']['players'][0])) {
            $player = $data['response']['players'][0];
            
            // Mise à jour de la base
            $stmt = $db->prepare("UPDATE players_info SET name = ?, avatar = ?, last_updated = ? WHERE steamid = ?");
            $stmt->execute([
                $player['personaname'], 
                $player['avatarfull'], 
                time(), 
                $steamid3
            ]);
        }
    }
}