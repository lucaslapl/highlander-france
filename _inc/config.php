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
