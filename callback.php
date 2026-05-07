<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once '_libs/openid.php';
require_once '_inc/config.php';
require_once '_inc/functions.php';
$openid = new LightOpenID('reconnexion.tf'); 

if ($openid->mode == 'cancel') {
    die("Connexion annulée par l'utilisateur.");
} elseif ($openid->validate()) {
    // La validation réussit !
    $id = $openid->identity; 
    $steamid64 = basename($id);
    
    session_start();
    $_SESSION['steamid'] = $steamid64;
    
    // IMPORTANT : On travaille avec le SteamID3 en base (format [U:1:XXXXX]) 
    // ou le SteamID64 selon ce que tu as choisi pour ta table players_info.
    // Si ta table utilise le format 17 chiffres, garde $steamid64.
    $search_id = $steamid64; 

    $steamid3 = steamID64ToSteamID3($steamid64);

    // 1. On vérifie si l'utilisateur existe
    $stmt = $db->prepare("SELECT steamid, created_at FROM players_info WHERE steamid = ?");
    $stmt->execute([$steamid3]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user === false) {
        // CAS 1 : Le joueur n'existe pas du tout -> INSERT avec la date actuelle
        $insert = $db->prepare("INSERT INTO players_info (steamid, display_name, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $insert->execute([$steamid3, 'Nouveau Joueur']);
    } else {
        // CAS 2 : Le joueur existe déjà
        // On vérifie s'il a déjà une date (pour les anciens membres)
        if (empty($user['created_at'])) {
            // S'il n'a pas de date, on met à jour created_at pour marquer sa "première" connexion aujourd'hui
            $update = $db->prepare("UPDATE players_info SET created_at = CURRENT_TIMESTAMP WHERE steamid = ?");
            $update->execute([$steamid3]);
        }
        // S'il a déjà une date, on ne touche à rien (on veut garder la date originale)
    }
    
    header('Location: index.php');
    exit();
} else {
    die("La validation a échoué.");
}