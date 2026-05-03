<?php
require_once '_libs/openid.php'; 

try {
    $openid = new LightOpenID('reconnexion.tf'); // Mettez votre nom de domaine réel
    $openid->returnUrl = 'https://reconnexion.tf/test/highlander-france/callback.php';
    $openid->identity = 'https://steamcommunity.com/openid';
    
    // On redirige l'utilisateur vers Steam
    header('Location: ' . $openid->authUrl());
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}