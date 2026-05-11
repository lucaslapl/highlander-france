<?php
function steamID64ToSteamID3($steamid64) {
    $steamid_constant = 76561197960265728;
    $account_id = bcsub($steamid64, (string)$steamid_constant);
    return "[U:1:" . $account_id . "]";
}

/* will need to optimize the two functions below */
function steamID3ToSteamID64($steamid3) {
    $account_id = str_replace(['[U:1:', ']'], '', $steamid3);
    return bcadd($account_id, '76561197960265728');
}

function steamID3To64($steamID3) {
    if (preg_match('/\[U:1:(\d+)\]/', $steamID3, $matches)) {
        return bcadd($matches[1], '76561197960265728');
    }
    return null;
}
/*****************************************/

function syncSteamProfile($steamid3, $db, $apiKey) {
    $env = parse_ini_file(__DIR__ . '/.env');
    $STEAM_API_KEY = $env['STEAM_API_KEY'];
    $steamid64 = steamID3ToSteamID64($steamid3);
    
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$steamid64";
    $json = @file_get_contents($url);
    
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['response']['players'][0])) {
            $player = $data['response']['players'][0];
            
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