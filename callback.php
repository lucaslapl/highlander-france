<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once __DIR__ . '/_libs/openid.php';
require_once __DIR__ . '/_inc/config.php';
require_once __DIR__ . '/_inc/functions.php';
$openid = new LightOpenID('highlanderfrance.tf'); 

if ($openid->mode == 'cancel') {
    die("Connexion annulée par l'utilisateur.");
} elseif ($openid->validate()) {
    $id = $openid->identity; 
    $steamid64 = basename($id);
    
    session_start();
    session_regenerate_id(true);
    $_SESSION['steamid'] = $steamid64;

    $search_id = $steamid64; 

    $steamid3 = steamID64ToSteamID3($steamid64);

    // 🔥 MODIFICATION : On ajoute "is_admin" dans le SELECT pour récupérer l'information
    $stmt = $db->prepare("SELECT steamid, created_at, is_admin FROM players_info WHERE steamid = ?");
    $stmt->execute([$steamid3]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user === false) {
        // if user doesn't exist, create it
        $insert = $db->prepare("INSERT INTO players_info (steamid, display_name, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $insert->execute([$steamid3, 'Nouveau Joueur']);

        syncPlayerWithSteamAPI($steamid64, $db);
        
        // Un nouvel inscrit n'est jamais admin par défaut
        $_SESSION['is_admin'] = false;
    } else {
        // if user exists but has no created_at, set it to now (first login)
        if (empty($user['created_at'])) {
            $update = $db->prepare("UPDATE players_info SET created_at = CURRENT_TIMESTAMP WHERE steamid = ?");
            $update->execute([$steamid3]);
        }

        // Dans le cas où un joueur s'est inscrit sans avoir été synchronisé
        if (empty($user['name']) || $user['name'] === 'Nouveau Joueur') {
            syncPlayerWithSteamAPI($steamid64, $db);
        }

        // 🔥 INJECTION SÉCURITÉ : On lit la valeur en BDD et on l'injecte de manière stricte (true/false)
        $_SESSION['is_admin'] = (isset($user['is_admin']) && (int)$user['is_admin'] === 1);
    }
    
    header('Location: index.php');
    exit();
} else {
    die("La validation a échoué.");
}