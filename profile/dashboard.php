<?php
// /profile/dashboard.php (Le Contrôleur)

// On charge la configuration et les fonctions globales
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// On inclut le Modèle fraîchement créé
require_once __DIR__ . '/../models/PlayerModel.php';

// 1. Protection : Si pas de session, on renvoie à la connexion
if (!isset($_SESSION['steamid'])) {
    header('Location: ../login.php');
    exit;
}

$env = parse_ini_file(__DIR__ . '/../_inc/.env');
$STEAM_API_KEY = $env['STEAM_API_KEY'];

$steamid64 = $_SESSION['steamid'];

// 2. Conversion vers le format stocké en base de données
$steamid3 = steamID64ToSteamID3($steamid64);

// 3. Récupération des infos du joueur connecté via le Modèle
$user = getPlayerInfos($db, $steamid3);

// S'il n'existe pas, on l'ajoute
if ($user === false) {
    try {
        $insert = $db->prepare("INSERT INTO players_info (steamid, display_name) VALUES (?, ?)");
        $insert->execute([$steamid3, 'Nouveau Joueur']);
        
        // On recharge les données via le Modèle
        $user = getPlayerInfos($db, $steamid3);
    } catch (PDOException $e) {
        die("Erreur lors de l'insertion : " . $e->getMessage());
    }
}

// 4. Vérification de la synchronisation Steam Profil
$last_update = (int)($user['last_updated'] ?? 0);

if (empty($user['name']) || ($last_update < time() - 86400)) {
    // Appel de la fonction globale de synchro
    syncSteamProfile($steamid3, $db, $STEAM_API_KEY);
    
    // On recharge les données car elles ont changé en base
    $user = getPlayerInfos($db, $steamid3);
}

// 5. Préparation des variables pour l'affichage (la Vue)
$date_brute = $user['created_at'] ?? null;
$date_formatee = $date_brute ? date('d/m/Y', strtotime($date_brute)) : false;

$country = $user['country'] ?? null;
$isLocked = (int)($user['country_locked'] ?? 0);

$countries = [
    'fr' => 'France',
    'be' => 'Belgique',
    'sw' => 'Suisse',
    'lu' => 'Luxembourg',
    'uk' => 'Royaume-Uni',
    'eu' => 'Europe',
    'al' => 'Algérie',
    'mo' => 'Maroc',
    'tu' => 'Tunisie',
    'ca' => 'Canada',
    'breizh' => 'Bretagne',
];

$currentMode = '9v9';

// Appel aux fonctions du Modèle pour récupérer les statistiques
$matches        = getPlayerTotalMatches($db, $steamid3, $currentMode);
$topMaps        = getPlayerTopMaps($db, $steamid3, $currentMode);
$classesPlayed  = getPlayerClassesPlayed($db, $steamid3, $currentMode);
$recentMatches  = getPlayerRecentMatches($db, $steamid3, $currentMode);

// Configuration des badges rôles
$rolesConfig = [
    'is_founder'   => ['label' => 'Fondateur',   'class' => 'badge-founder'],
    'is_admin'     => ['label' => 'Admin',       'class' => 'badge-admin'],
    'is_moderator' => ['label' => 'Modérateur',  'class' => 'badge-moderator'],
    'is_mentor'    => ['label' => 'Mentor',      'class' => 'badge-mentor'],
    'is_mixer'     => ['label' => 'Mixer',       'class' => 'badge-mixer'],
];

// 6. Inclusion de la Vue (C'est elle qui va tout afficher !)
require_once __DIR__ . '/../views/profile.view.php';