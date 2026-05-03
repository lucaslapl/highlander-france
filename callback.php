<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once '_libs/openid.php';
$openid = new LightOpenID('reconnexion.tf'); // Mettez votre nom de domaine réel

if ($openid->mode == 'cancel') {
    die("Connexion annulée par l'utilisateur.");
} elseif ($openid->validate()) {
    // La validation réussit ! Steam nous confirme l'identité
    $id = $openid->identity; // Retourne quelque chose comme .../openid/id/76561197960435530
    
    // On extrait juste les 17 chiffres du SteamID64
    $steamid64 = basename($id);
    
    // --- ICI, VOUS AVEZ L'IDENTITÉ DE L'UTILISATEUR ---
    session_start();
    $_SESSION['steamid'] = $steamid64;
    
    // Optionnel : Enregistrer en base de données si c'est la première connexion
    // ... requête SQL pour vérifier si l'id existe, sinon INSERT ...
    // 1. On tente de récupérer l'utilisateur
    $db = new PDO('sqlite:' . __DIR__ . '/_scripts/stats.db');
    $stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
    $stmt->execute([$_SESSION['steamid']]);
    $user = $stmt->fetch();

    // 2. S'il n'existe pas, on l'ajoute automatiquement !
    if ($user === false) {
        $insert = $db->prepare("INSERT INTO players_info (steamid, display_name) VALUES (?, ?)");
        $insert->execute([$_SESSION['steamid'], 'Nouveau Joueur']); // Valeur par défaut
        
        // On recharge les données pour que $user soit rempli
        $stmt->execute([$_SESSION['steamid']]);
        $user = $stmt->fetch();
    }
    
    header('Location: index.php'); // Retour à l'accueil
} else {
    die("La validation a échoué.");
}