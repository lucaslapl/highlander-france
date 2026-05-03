<?php
$db = new PDO('sqlite:stats.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Table des logs déjà traités (évite de les refaire)
$db->exec("CREATE TABLE IF NOT EXISTS processed_logs (id INTEGER PRIMARY KEY)");

// Table des scores des joueurs
$db->exec("CREATE TABLE IF NOT EXISTS player_stats (steamid TEXT PRIMARY KEY, count INTEGER)");

// Table de cache pour les noms/avatars (évite d'appeler Steam à chaque fois)
$db->exec("CREATE TABLE IF NOT EXISTS players_info (steamid TEXT PRIMARY KEY, name TEXT, avatar TEXT, last_updated INTEGER)");

echo "Base de données initialisée avec succès.";
?>