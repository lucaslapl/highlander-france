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

/**
 * Récupère le pseudo et l'avatar d'un joueur via l'API Steam
 * Même s'il n'a jamais joué de matchs ou n'a jamais été synchronisé auparavant.
 */
function syncPlayerWithSteamAPI($steamid64, $db) {
    $env = parse_ini_file(__DIR__ . '/.env');
    $STEAM_API_KEY = $env['STEAM_API_KEY'];

    // URL de l'API Steam pour obtenir les profils utilisateurs
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $STEAM_API_KEY . "&steamids=" . $steamid64;

    // Appel de l'API de manière sécurisée
    $response = @file_get_contents($url);
    if ($response === false) {
        return false; // L'API Steam est indisponible ou la clé est mauvaise
    }

    $data = json_decode($response, true);
    
    // On vérifie que Steam nous renvoie bien les données du joueur
    if (isset($data['response']['players'][0])) {
        $player_data = $data['response']['players'][0];
        
        $steam_name = $player_data['personaname'] ?? 'Joueur Steam';
        $steam_avatar = $player_data['avatarfull'] ?? ''; // Version 184x184px (la plus propre)

        // Convertir le SteamID64 en SteamID3 pour correspondre à ton architecture de table
        $steamid3 = steamID64ToSteamID3($steamid64);

        $stmt = $db->prepare("
            UPDATE players_info 
            SET name = ?, 
                avatar = ?,
                display_name = CASE 
                    WHEN display_name = 'Nouveau Joueur' OR display_name IS NULL OR display_name = '' THEN ? 
                    ELSE display_name 
                END
            WHERE steamid = ?
        ");

        // On passe les paramètres dans le bon ordre pour la requête
        $stmt->execute([$steam_name, $steam_avatar, $steam_name, $steamid3]);
        
        return true;
    }

    return false;
}

/*****************************/

/**
 * Vérifie si l'utilisateur en session est un administrateur authentifié.
 * Bloque immédiatement l'accès en cas d'échec.
 */
function checkAdminOrDie() {
    // 1. On s'assure que la session est démarrée
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 2. Double vérification stricte (existence + valeur booléenne)
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true || !isset($_SESSION['steamid'])) {
        // Sécurité : On détruit la session suspecte au cas où
        unset($_SESSION['is_admin']);
        
        // On renvoie un code HTTP 403 (Accès interdit) et on arrête le script
        http_response_code(403);
        echo "<h1>403 Forbidden - Accès refusé</h1>";
        exit();
    }
}