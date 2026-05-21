<?php
$durations_session = 30 * 24 * 60; // 30 jours en secondes
session_set_cookie_params([
    'lifetime' => $durations_session,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]); 
ini_set('session.gc_maxlifetime', $durations_session);

if (php_sapi_name() !== 'cli') {
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
?>