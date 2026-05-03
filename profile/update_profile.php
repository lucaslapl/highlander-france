<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
session_start();

// 1. Sécurité : Vérifier que l'utilisateur est bien connecté
if (!isset($_SESSION['steamid'])) {
    die("Accès refusé. Veuillez vous connecter.");
}

// 2. Connexion à la base de données
// Ajustez le chemin vers stats.db si nécessaire
$db = new PDO('sqlite:' . __DIR__ . '/../_scripts/stats.db');

// 3. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['display_name'])) {
    
    // Nettoyage rapide (trim supprime les espaces inutiles)
    $newName = trim($_POST['display_name']);
    
    // Sécurité : On utilise une requête préparée pour empêcher les injections SQL
    $stmt = $db->prepare("UPDATE players_info SET display_name = ? WHERE steamid = ?");
    $stmt->execute([$newName, $_SESSION['steamid']]);
    
    // 4. Redirection vers le dashboard avec un message de succès
    header('Location: dashboard.php?success=1');
    exit;
} else {
    // Si quelqu'un essaie d'accéder à ce fichier sans envoyer de formulaire
    header('Location: dashboard.php');
    exit;
}
?>