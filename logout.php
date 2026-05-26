<?php
require_once __DIR__ . "/_inc/config.php";

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000, // Date d'expiration dans le passé = destruction immédiate
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

session_destroy();
header('Location: index.php');
exit();