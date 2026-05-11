<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once '_libs/openid.php';
require_once '_inc/config.php';
require_once '_inc/functions.php';
$openid = new LightOpenID('highlanderfrance.tf'); 

if ($openid->mode == 'cancel') {
    die("Connexion annulée par l'utilisateur.");
} elseif ($openid->validate()) {
    $id = $openid->identity; 
    $steamid64 = basename($id);
    
    session_start();
    $_SESSION['steamid'] = $steamid64;

    $search_id = $steamid64; 

    $steamid3 = steamID64ToSteamID3($steamid64);

    $stmt = $db->prepare("SELECT steamid, created_at FROM players_info WHERE steamid = ?");
    $stmt->execute([$steamid3]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user === false) {
        // if user doesn't exist, create it
        $insert = $db->prepare("INSERT INTO players_info (steamid, display_name, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $insert->execute([$steamid3, 'Nouveau Joueur']);
    } else {
        // if user exists but has no created_at, set it to now (first login)
        if (empty($user['created_at'])) {
            $update = $db->prepare("UPDATE players_info SET created_at = CURRENT_TIMESTAMP WHERE steamid = ?");
            $update->execute([$steamid3]);
        }
    }
    
    header('Location: index.php');
    exit();
} else {
    die("La validation a échoué.");
}