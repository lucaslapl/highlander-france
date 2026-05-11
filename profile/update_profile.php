<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';
session_start();
if (!isset($_SESSION['steamid'])) {
    die("Accès refusé. Veuillez vous connecter.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['display_name'])) {
    
    $newName = trim($_POST['display_name']);

    $steamid3 = steamID64ToSteamID3($_SESSION['steamid']);
    
    $stmt = $db->prepare("UPDATE players_info SET display_name = ? WHERE steamid = ?");
    $stmt->execute([$newName, $steamid3]);
    
    if ($stmt->rowCount() > 0) {
        header('Location: dashboard.php?success=1');
        exit;
    } else {
        echo "Erreur : Aucune ligne mise à jour. Le SteamID cherché était : " . htmlspecialchars($steamid3);
        echo "<br><a href='dashboard.php'>Retour</a>";
    }
}
?>