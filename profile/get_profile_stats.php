<?php
header('Content-Type: application/json');

// 1. On charge la configuration et les fonctions
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// 2. Récupération et sécurisation des paramètres
$steamid = $_GET['steamid'] ?? null;
$mode = $_GET['mode'] ?? '9v9';

if (!$steamid || !preg_match('/^\d{17}$/', $steamid) || !in_array($mode, ['6s', '9v9'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres invalides ou SteamID manquant.']);
    exit();
}

// Conversion du SteamID64 en SteamID3 pour SQLite
$steamid3 = steamID64ToSteamID3($steamid);

try {
    /** 1. COMPTEUR DE MATCHS **/
    $stmt = $db->prepare("SELECT count as total_matches FROM player_stats WHERE steamid = ? AND game_mode = ?");
    $stmt->execute([$steamid3, $mode]);
    $matches = $stmt->fetch(PDO::FETCH_ASSOC);

    /** 2. TOP 3 MAPS **/
    $stmtMaps = $db->prepare("
        SELECT map_name, COUNT(map_name) as total 
        FROM player_matches 
        WHERE steamid = ? AND game_mode = ? 
        GROUP BY map_name 
        ORDER BY total DESC 
        LIMIT 3
    ");
    $stmtMaps->execute([$steamid3, $mode]);
    $topMaps = $stmtMaps->fetchAll(PDO::FETCH_ASSOC);

    /** 3. CLASSES JOUÉES **/
    $stmtClasses = $db->prepare("
        SELECT class_played, COUNT(class_played) as total 
        FROM player_matches 
        WHERE steamid = ? AND game_mode = ? 
        GROUP BY class_played 
        ORDER BY total DESC
    ");
    $stmtClasses->execute([$steamid3, $mode]);
    $classesPlayed = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

    /** 4. MATCHS RÉCENTS **/
    $stmtRecent = $db->prepare("
        SELECT match_id, map_name, class_played 
        FROM player_matches 
        WHERE steamid = ? AND game_mode = ? 
        ORDER BY match_id DESC 
        LIMIT 5
    ");
    $stmtRecent->execute([$steamid3, $mode]);
    $recentMatches = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    /** 5. ENVOI DE LA RÉPONSE **/
    echo json_encode([
        'total_matches'  => $matches['total_matches'] ?? 0,
        'top_maps'       => $topMaps ? $topMaps : [],
        'classes_played' => $classesPlayed ? $classesPlayed : [],
        'recent_matches' => $recentMatches ? $recentMatches : []
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des données.']);
}