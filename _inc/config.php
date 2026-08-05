<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
// EXCLUDE CLI
if (php_sapi_name() !== 'cli') {

    $session_lifetime = 30 * 24 * 3600; // 30 jours en secondes
    $session_save_path = __DIR__ . '/../_sessions';

    if (!file_exists($session_save_path)) {
        mkdir($session_save_path, 0755, true);
    }

    ini_set('session.save_path', $session_save_path);
    ini_set('session.gc_maxlifetime', $session_lifetime);
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);

    session_name('HLFR_SESSION');

    session_set_cookie_params([
        'lifetime' => $session_lifetime,
        'path' => '/',
        // 'domain' => $_SERVER['HTTP_HOST'] ?? 'highlanderfrance.tf',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

$db_path = __DIR__ . '/../_scripts/stats.db';

try {
    // sqlite connect
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Table de blacklist des logs logs.tf (gérée depuis le panel admin et la page Match Stats)
$db->exec("CREATE TABLE IF NOT EXISTS log_blacklist (
    log_id     INTEGER PRIMARY KEY,
    reason     TEXT,
    added_by   TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Migration : IDs historiquement codés en dur, injectés une seule fois
$db->exec("INSERT OR IGNORE INTO log_blacklist (log_id, added_by) VALUES
    (4040598, 'legacy'),
    (4062936, 'legacy'),
    (4062933, 'legacy'),
    (4062917, 'legacy'),
    (4062908, 'legacy'),
    (4062900, 'legacy'),
    (4062895, 'legacy')");

// Cache des durées de logs logs.tf (utilisé par la page admin des logs de matchs)
$db->exec("CREATE TABLE IF NOT EXISTS log_length_cache (
    log_id INTEGER PRIMARY KEY,
    length INTEGER
)");

// Cache des dates de logs logs.tf (utilisé par les graphiques du dashboard admin)
$db->exec("CREATE TABLE IF NOT EXISTS log_dates (
    log_id INTEGER PRIMARY KEY,
    date   INTEGER
)");

// Scores RED/BLU des matchs (page détail d'un log)
$db->exec("CREATE TABLE IF NOT EXISTS match_scores (
    match_id   INTEGER PRIMARY KEY,
    red_score  INTEGER DEFAULT 0,
    blue_score INTEGER DEFAULT 0
)");

// Migration : colonne team sur player_matches (red / blue), ajoutée une seule fois
try {
    $pmCols = $db->query("PRAGMA table_info(player_matches)")->fetchAll(PDO::FETCH_ASSOC);
    $pmHasTeam = false;
    foreach ($pmCols as $pmCol) {
        if ($pmCol['name'] === 'team') {
            $pmHasTeam = true;
            break;
        }
    }
    if (!$pmHasTeam) {
        $db->exec("ALTER TABLE player_matches ADD COLUMN team TEXT DEFAULT NULL");
    }
} catch (PDOException $e) {
    error_log("Migration team: " . $e->getMessage());
}

$db->exec("CREATE TABLE IF NOT EXISTS player_matches (
    steamid            TEXT,
    match_id           INTEGER,
    map_name           TEXT,
    class_played       TEXT,
    game_mode          TEXT DEFAULT '9v9',
    dmg                INTEGER DEFAULT 0,
    kills              INTEGER DEFAULT 0,
    deaths             INTEGER DEFAULT 0,
    assists            INTEGER DEFAULT 0,
    suicides           INTEGER DEFAULT 0,
    heal               INTEGER DEFAULT 0,
    medkits            INTEGER DEFAULT 0,
    ubers              INTEGER DEFAULT 0,
    drops              INTEGER DEFAULT 0,
    backstabs          INTEGER DEFAULT 0,
    headshots          INTEGER DEFAULT 0,
    longest_killstreak INTEGER DEFAULT 0,
    classes_killed     TEXT DEFAULT NULL,
    PRIMARY KEY (steamid, match_id)
)");

// Durée minimale d'un log (en secondes) : en dessous, blacklist automatique (5 minutes)
define('MIN_MATCH_LENGTH', 300);
