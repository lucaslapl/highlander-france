<?php
// models/PlayerModel.php

/**
 * Récupère les informations de base d'un joueur
 */
function getPlayerInfos($db, $steamid3) {
    $stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
    $stmt->execute([$steamid3]);
    return $stmt->fetch(PDO::FETCH_ASSOC); // Optionnel mais propre : forcer le tableau associatif
}

/**
 * Récupère le nombre total de matchs joués dans un mode de jeu
 */
function getPlayerTotalMatches($db, $steamid3, $gameMode) {
    $stmt = $db->prepare("SELECT count as total_matches FROM player_stats WHERE steamid = ? AND game_mode = ?");
    $stmt->execute([$steamid3, $gameMode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Récupère le Top 3 des maps jouées
 */
function getPlayerTopMaps($db, $steamid3, $gameMode) {
    $stmt = $db->prepare("SELECT map_name, COUNT(map_name) as total FROM player_matches WHERE steamid = ? AND game_mode = ? GROUP BY map_name ORDER BY total DESC LIMIT 3");
    $stmt->execute([$steamid3, $gameMode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les classes jouées triées par utilisation
 */
function getPlayerClassesPlayed($db, $steamid3, $gameMode) {
    $stmt = $db->prepare("SELECT class_played, COUNT(class_played) as total FROM player_matches WHERE steamid = ? AND game_mode = ? GROUP BY class_played ORDER BY total DESC");
    $stmt->execute([$steamid3, $gameMode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les 5 derniers matchs récents
 */
function getPlayerRecentMatches($db, $steamid3, $gameMode) {
    $stmt = $db->prepare("SELECT match_id, map_name, class_played FROM player_matches WHERE steamid = ? AND game_mode = ? ORDER BY match_id DESC LIMIT 5");
    $stmt->execute([$steamid3, $gameMode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}