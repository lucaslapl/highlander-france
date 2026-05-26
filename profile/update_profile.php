<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (!isset($_SESSION['steamid'])) { 
    
    // On stocke le message d'erreur en session pour qu'il survive à la redirection
    $_SESSION['error'] = "Action refusée : vous devez être connecté pour modifier votre nationalité.";
    
    // On redirige immédiatement vers la page d'accueil
    header("Location: /index.php");
    exit(); // Très important pour stopper l'exécution du reste du script PHP
}

$steamid3 = steamID64ToSteamID3($_SESSION['steamid']);
$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_name') {
    
    $new_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';

    // 1. SÉCURITÉ CRITIQUE : On vérifie si le joueur n'a pas déjà fait son changement
    $checkStmt = $db->prepare("SELECT name_changed FROM players_info WHERE steamid = ?");
    $checkStmt->execute([$steamid3]);
    $playerCheck = $checkStmt->fetch();

    if ($playerCheck && (int)$playerCheck['name_changed'] === 1) {
        $_SESSION['error'] = "Vous avez déjà modifié votre nom d'affichage une fois. Action impossible.";
        header("Location: /profile/dashboard");
        exit();
    }

    // 2. Validations standards
    if (empty($new_name)) {
        $_SESSION['error'] = "Le nom d'affichage ne peut pas être vide.";
        header("Location: /profile/dashboard");
        exit();
    }

    if (mb_strlen($new_name) > 32) {
        $_SESSION['error'] = "Le nom d'affichage ne doit pas dépasser 32 caractères.";
        header("Location: /profile/dashboard");
        exit();
    }

    $new_name = strip_tags($new_name);

    try {
        // 3. Mise à jour du nom ET passage de name_changed à 1
        $stmt = $db->prepare("
            UPDATE players_info 
            SET display_name = ?, name_changed = 1 
            WHERE steamid = ?
        ");
        $stmt->execute([$new_name, $steamid3]);

        // 4. Rafraîchissement de la session si nécessaire
        if (isset($_SESSION['player'])) {
            $_SESSION['player']['display_name'] = $new_name;
            $_SESSION['player']['name_changed'] = 1;
        }

        $_SESSION['success'] = "Votre nom d'affichage a été définitivement enregistré !";
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Une erreur est survenue lors de l'enregistrement.";
    }

    header("Location: /profile/dashboard");
    exit();
}

header("Location: /profile/dashboard");
exit();