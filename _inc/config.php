<?php
// 1. Démarrage sécurisé de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Définition du chemin vers la base de données
// __DIR__ désigne le dossier "includes".
// On remonte d'un niveau (..) pour accéder au dossier racine, puis stats.db
$db_path = __DIR__ . '/../_scripts/stats.db';

try {
    // 3. Connexion à la base via PDO
    $db = new PDO('sqlite:' . $db_path);
    // Configuration pour que PDO nous envoie des erreurs lisibles
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>