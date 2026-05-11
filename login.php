<?php
require_once '_libs/openid.php'; 

try {
    $openid = new LightOpenID('highlanderfrance.tf');
    $openid->returnUrl = 'https://highlanderfrance.tf/callback.php';
    $openid->identity = 'https://steamcommunity.com/openid';

    header('Location: ' . $openid->authUrl());
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}