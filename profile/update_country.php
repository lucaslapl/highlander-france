<?php
// Sécurité CRON/CLI non requise ici car c'est une action utilisateur (via navigateur)
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../_inc/config.php'; // Ajuste le chemin selon ton projet
require_once __DIR__ . '/../_inc/functions.php';

// 1. Vérification de la session
if (!isset($_SESSION['steamid'])) {
    header('HTTP/1.0 403 Forbidden');
    die("Vous devez être connecté.");
}

$steamid64 = $_SESSION['steamid'];
$steamid3 = steamID64ToSteamID3($steamid64);
$chosenCountry = isset($_POST['country']) ? trim(strtolower($_POST['country'])) : '';

// Liste des pays autorisés pour bloquer les injections de texte farfelu
$allowedCountries = ['fr', 'be', 'sw', 'lu', 'uk', 'eu', 'al', 'mo', 'tu', 'ca', 'breizh']; // Ajoute les codes de pays autorisés

if (empty($chosenCountry) || !in_array($chosenCountry, $allowedCountries)) {
    die("Pays invalide.");
}

// 2. Vérifier si le profil n'est pas déjà verrouillé
$stmtCheck = $db->prepare("SELECT country_locked FROM players_info WHERE steamid = ?");
$stmtCheck->execute([$steamid3]);
$playerInfo = $stmtCheck->fetch();

if ($playerInfo && (int)$playerInfo['country_locked'] === 1) {
    die("Votre nationalité a déjà été définie et est verrouillée.");
}

// 3. Mise à jour et Verrouillage permanent
$stmtUpdate = $db->prepare("
    UPDATE players_info 
    SET country = :country, country_locked = 1 
    WHERE steamid = :steamid
");

$success = $stmtUpdate->execute([
    ':country' => $chosenCountry,
    ':steamid' => $steamid3
]);

// 4. Redirection vers le dashboard avec un message de succès
if ($success) {
    $_SESSION['flash_success'] = "Votre nationalité a été enregistrée avec succès !";
} else {
    $_SESSION['flash_error'] = "Une erreur est survenue lors de l'enregistrement.";
}

header("Location: /profile/dashboard");
exit;