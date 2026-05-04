<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// 1. Sécurité : Vérifier que l'utilisateur est bien connecté
if (!isset($_SESSION['steamid'])) {
    die("Accès refusé. Veuillez vous connecter.");
}

// 3. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['display_name'])) {
    
    // Nettoyage rapide (trim supprime les espaces inutiles)
    $newName = trim($_POST['display_name']);

    $steamid3 = steamID64ToSteamID3($_SESSION['steamid']);
    
    // Sécurité : On utilise une requête préparée pour empêcher les injections SQL
    $stmt = $db->prepare("UPDATE players_info SET display_name = ? WHERE steamid = ?");
    $stmt->execute([$newName, $steamid3]);
    
    // 4. Redirection vers le dashboard avec un message de succès
    if ($stmt->rowCount() > 0) {
        header('Location: dashboard.php?success=1');
        exit;
    } else {
        // Cela signifie que le SteamID3 ne correspond à rien dans la base
        echo "Erreur : Aucune ligne mise à jour. Le SteamID cherché était : " . htmlspecialchars($steamid3);
        echo "<br><a href='dashboard.php'>Retour</a>";
    }
}
?>