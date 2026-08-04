<?php
function steamID64ToSteamID3($steamid64)
{
    $steamid_constant = 76561197960265728;
    $account_id = bcsub($steamid64, (string)$steamid_constant);
    return "[U:1:" . $account_id . "]";
}

/* will need to optimize the two functions below */
function steamID3ToSteamID64($steamid3)
{
    $account_id = str_replace(['[U:1:', ']'], '', $steamid3);
    return bcadd($account_id, '76561197960265728');
}

function steamID3To64($steamID3)
{
    if (preg_match('/\[U:1:(\d+)\]/', $steamID3, $matches)) {
        return bcadd($matches[1], '76561197960265728');
    }
    return null;
}
/*****************************************/

function syncSteamProfile($steamid3, $db, $apiKey)
{
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
function syncPlayerWithSteamAPI($steamid64, $db)
{
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
function checkAdminOrDie()
{
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

/********************************/

/**
 * Récupère la liste de tous les membres de l'équipe technique (administrateurs)
 */
function getTechnicalTeam($db)
{
    try {
        // On récupère le SteamID, le pseudo d'affichage et le pays des admins
        $stmt = $db->prepare("
            SELECT steamid, display_name, country 
            FROM players_info 
            WHERE is_admin = 1 
            ORDER BY display_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // En cas d'erreur de base de données, on renvoie un tableau vide
        return [];
    }
}


/************************************/

/**
 * Enregistre et audite l'exécution des scripts avec gestion du statut (Début / Succès / Échec)
 * @param string $scriptName Nom du script
 * @param string|null $updateId Si fourni, met à jour le log existant avec ce token unique
 * @param string $status Le statut à appliquer ('SUCCESS' ou une raison d'échec)
 * @return string Le token unique du log généré
 */
function logScriptExecution($scriptName, $updateId = null, $status = 'STARTED')
{
    date_default_timezone_set('Europe/Paris');
    $logFile = __DIR__ . '/../_scripts/cron_debug.log';

    // 1. Si on est en mode MISE À JOUR (Fin du script)
    if ($updateId !== null) {
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            // On cherche la balise [TOKEN:xyz] [STATUS:STARTED] pour la remplacer
            $search = "[TOKEN:{$updateId}] [STATUS:STARTED]";
            $replace = "[TOKEN:{$updateId}] [STATUS:{$status}]";

            if (strpos($content, $search) !== false) {
                $content = str_replace($search, $replace, $content);
                file_put_contents($logFile, $content, LOCK_EX);
                return $updateId;
            }
        }
    }

    // 2. Si on est en mode INITIALISATION (Début du script)
    $token = uniqid('req_', true); // Génère un ID unique pour cette exécution

    // Détection de l'utilisateur (Code précédent conservé et optimisé)
    if (php_sapi_name() === 'cli') {
        $user = "SERVER (CLI / CRON)";
    } else {
        //if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $steamid64 = $_SESSION['steamid'] ?? 'Pas de SteamID';

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP Inconnue';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }

        $pseudo = 'Inconnu';
        if (isset($_SESSION['steamid'])) {
            global $db;
            try {
                $stmt = $db->prepare("SELECT display_name FROM players_info WHERE steamid = ?");
                $stmt->execute([steamID64ToSteamID3($steamid64)]);
                $player = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($player) {
                    $pseudo = $player['display_name'];
                }
            } catch (Exception $e) {
                $pseudo = 'Erreur BDD';
            }
        }
        $user = "Web User: {$pseudo} ({$steamid64}) - IP: {$ip}";
    }

    // Écriture de la ligne initiale
    $date = date('Y-m-d H:i:s');
    $logLine = "[{$date}] [TOKEN:{$token}] [STATUS:{$status}] [SCRIPT: {$scriptName}] [BY: {$user}]" . PHP_EOL;
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

    return $token; // On renvoie le token pour pouvoir mettre à jour la ligne plus tard
}


/**
 * Retourne la liste des IDs de logs blacklistés (entiers).
 */
function getBlacklistedLogIds($db)
{
    try {
        $rows = $db->query("SELECT log_id FROM log_blacklist")->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $rows);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Retourne la liste complète de la blacklist (id, raison, auteur, date).
 */
function getBlacklist($db)
{
    try {
        $stmt = $db->query("SELECT log_id, reason, added_by, created_at FROM log_blacklist ORDER BY created_at DESC, log_id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Ajoute un log à la blacklist (idempotent).
 * @return bool true si le log vient d'être ajouté, false s'il y était déjà.
 */
function blacklistLog($db, $logId, $reason, $addedBy)
{
    $stmt = $db->prepare("INSERT OR IGNORE INTO log_blacklist (log_id, reason, added_by) VALUES (?, ?, ?)");
    $stmt->execute([(int)$logId, $reason, $addedBy]);
    return $stmt->rowCount() > 0;
}

/**
 * Résout le pseudo d'un admin à partir du SteamID64 stocké en base.
 * Les valeurs non-SteamID ('legacy', 'auto', 'Inconnu') sont retournées telles quelles.
 */
function getAdminDisplayName($db, $addedBy)
{
    if (empty($addedBy) || !preg_match('/^\d{17}$/', $addedBy)) {
        return $addedBy;
    }
    $steamid3 = steamID64ToSteamID3($addedBy);
    $stmt = $db->prepare("SELECT display_name, name FROM players_info WHERE steamid = ?");
    $stmt->execute([$steamid3]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($p) {
        return !empty($p['display_name']) ? $p['display_name'] : $p['name'];
    }
    return $addedBy; // joueur introuvable en BDD : on retombe sur le SteamID64
}

/**
 * Extrait les stats par joueur depuis la réponse complète d'un log logs.tf.
 * @param array $details Réponse de /api/v1/log/<id>
 * @return array [steamid3 => [dmg, kills, ..., classes_killed]]
 */
function extractLogPlayerStats($details)
{
    $classKills = $details['classkills'] ?? [];
    $stats = [];

    foreach (($details['players'] ?? []) as $steamid => $pData) {
        $stats[$steamid] = [
            'dmg'                => (int)($pData['dmg'] ?? 0),
            'kills'              => (int)($pData['kills'] ?? 0),
            'deaths'             => (int)($pData['deaths'] ?? 0),
            'assists'            => (int)($pData['assists'] ?? 0),
            'suicides'           => (int)($pData['suicides'] ?? 0),
            'heal'               => (int)($pData['heal'] ?? 0),
            'medkits'            => (int)($pData['medkits'] ?? 0),
            'ubers'              => (int)($pData['ubers'] ?? 0),
            'drops'              => (int)($pData['drops'] ?? 0),
            'backstabs'          => (int)($pData['backstabs'] ?? 0),
            'headshots'          => (int)($pData['headshots'] ?? 0),
            'longest_killstreak' => (int)($pData['lks'] ?? 0),
            'classes_killed'     => json_encode($classKills[$steamid] ?? [], JSON_UNESCAPED_SLASHES),
        ];
    }
    return $stats;
}

/**
 * Insère ou met à jour la ligne player_matches d'un joueur pour un log.
 * Ne modifie JAMAIS map_name/class_played/game_mode sur un conflit
 * (pour préserver les corrections manuelles des admins).
 */
function upsertPlayerMatchStats($db, $steamid, $matchId, $mapName, $classPlayed, $gameMode, $stats)
{
    $stmt = $db->prepare("INSERT INTO player_matches
        (steamid, match_id, map_name, class_played, game_mode,
         dmg, kills, deaths, assists, suicides, heal, medkits, ubers, drops,
         backstabs, headshots, longest_killstreak, classes_killed)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT(steamid, match_id) DO UPDATE SET
            dmg = excluded.dmg,
            kills = excluded.kills,
            deaths = excluded.deaths,
            assists = excluded.assists,
            suicides = excluded.suicides,
            heal = excluded.heal,
            medkits = excluded.medkits,
            ubers = excluded.ubers,
            drops = excluded.drops,
            backstabs = excluded.backstabs,
            headshots = excluded.headshots,
            longest_killstreak = excluded.longest_killstreak,
            classes_killed = excluded.classes_killed");

    $stmt->execute([
        $steamid,
        $matchId,
        $mapName,
        $classPlayed,
        $gameMode,
        (int)($stats['dmg'] ?? 0),
        (int)($stats['kills'] ?? 0),
        (int)($stats['deaths'] ?? 0),
        (int)($stats['assists'] ?? 0),
        (int)($stats['suicides'] ?? 0),
        (int)($stats['heal'] ?? 0),
        (int)($stats['medkits'] ?? 0),
        (int)($stats['ubers'] ?? 0),
        (int)($stats['drops'] ?? 0),
        (int)($stats['backstabs'] ?? 0),
        (int)($stats['headshots'] ?? 0),
        (int)($stats['longest_killstreak'] ?? 0),
        $stats['classes_killed'] ?? '[]',
    ]);
}

/**
 * Agrége les stats d'un joueur pour un mode (dégâts, kills, morts, K/D, classes tuées).
 * @return array
 */
function getPlayerMatchStats($db, $steamid3, $mode)
{
    $empty = [
        'total_damage' => 0,
        'total_kills' => 0,
        'total_deaths' => 0,
        'total_assists' => 0,
        'kd_ratio' => 0,
        'classes_killed' => [],
    ];
    if (!playerMatchColumnsAvailable($db)) {
        return $empty;
    }
    try {
        $stmt = $db->prepare("
        SELECT COALESCE(SUM(dmg), 0)    AS total_damage,
               COALESCE(SUM(kills), 0)  AS total_kills,
               COALESCE(SUM(deaths), 0) AS total_deaths,
               COALESCE(SUM(assists), 0) AS total_assists
        FROM player_matches
        WHERE steamid = ? AND game_mode = ?
    ");
        $stmt->execute([$steamid3, $mode]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);

        $kd = 0;
        if ((int)$t['total_deaths'] > 0) {
            $kd = round((int)$t['total_kills'] / (int)$t['total_deaths'], 2);
        } elseif ((int)$t['total_kills'] > 0) {
            $kd = (int)$t['total_kills'];
        }

        // Fusion des JSON "classes_killed" de chaque match
        $stmtCk = $db->prepare("SELECT classes_killed FROM player_matches
                            WHERE steamid = ? AND game_mode = ?
                            AND classes_killed IS NOT NULL AND classes_killed != ''");
        $stmtCk->execute([$steamid3, $mode]);
        $classesKilled = [];
        foreach ($stmtCk->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $decoded = json_decode($row['classes_killed'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $class => $count) {
                    $classesKilled[$class] = ($classesKilled[$class] ?? 0) + (int)$count;
                }
            }
        }
        arsort($classesKilled);

        return [
            'total_damage'   => (int)$t['total_damage'],
            'total_kills'    => (int)$t['total_kills'],
            'total_deaths'   => (int)$t['total_deaths'],
            'total_assists'  => (int)$t['total_assists'],
            'kd_ratio'       => $kd,
            'classes_killed' => $classesKilled,
        ];
    } catch (PDOException $e) {
        return $empty;
    }
}

/**
 * Vrai si les colonnes de stats ont été migrées dans player_matches.
 */
function playerMatchColumnsAvailable($db)
{
    static $available = null;
    if ($available === null) {
        $cols = [];
        foreach ($db->query("PRAGMA table_info(player_matches)")->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $cols[$c['name']] = true;
        }
        $available = isset($cols['dmg'], $cols['kills'], $cols['deaths'], $cols['classes_killed']);
    }
    return $available;
}

function getRecentPlayerMatches($db, $steamid3, $mode, $limit = 5)
{
    $extra = playerMatchColumnsAvailable($db) ? ', dmg, kills, deaths' : '';
    $stmt = $db->prepare("SELECT match_id, map_name, class_played$extra
                          FROM player_matches
                          WHERE steamid = ? AND game_mode = ?
                          ORDER BY match_id DESC
                          LIMIT " . (int)$limit);
    $stmt->execute([$steamid3, $mode]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['dmg']    = (int)($r['dmg'] ?? 0);
        $r['kills']  = (int)($r['kills'] ?? 0);
        $r['deaths'] = (int)($r['deaths'] ?? 0);
    }
    unset($r);
    return $rows;
}