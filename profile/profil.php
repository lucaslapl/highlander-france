<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
// 1. On charge la configuration
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// 2. On récupère l'ID passé dans l'URL (ex: profil.php?steamid=7656...)
$steamid = $_GET['steamid'] ?? null;
// Sécurité : On vérifie que c'est bien une chaîne de chiffres
// Le SteamID64 fait toujours 17 caractères chez Steam.
if (!preg_match('/^\d{17}$/', $steamid)) {
    die("Format de SteamID invalide.");
}

// 3. Si aucun ID n'est fourni, on redirige vers l'accueil
if (!$steamid) {
    die("Aucun profil sélectionné.");
}

// 4. On cherche le joueur dans la base de données
$steamid3 = steamID64ToSteamID3($steamid);
$stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
$stmt->execute([$steamid3]);
$player = $stmt->fetch();

// 5. Si le joueur n'existe pas en base
if (!$player) {
    die("Profil introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France - Profil de <?php echo htmlspecialchars($player['display_name']); ?></title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - Communauté Compétitive de TF2">
    <meta property="og:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta property="og:image" content="https://highlanderfrance.tf/_img/hf.webp">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="highlanderfrance.tf">
    <meta property="twitter:url" content="https://highlanderfrance.tf/">
    <meta name="twitter:title" content="Highlander France - Communauté Compétitive de TF2">
    <meta name="twitter:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta name="twitter:image" content="https://highlanderfrance.tf/_img/hf.webp">

    <!-- Favicon standard -->
    <link rel="shortcut icon" href="https://highlanderfrance.tf/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="https://highlanderfrance.tf/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://highlanderfrance.tf/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="https://highlanderfrance.tf/favicon.ico">

    <!-- Apple Touch Icon (iPhone/iPad) -->
    <link rel="apple-touch-icon" href="https://highlanderfrance.tf/apple-touch-icon.png">

    <!-- Android Chrome -->
    <link rel="icon" type="image/png" sizes="192x192" href="https://highlanderfrance.tf/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="https://highlanderfrance.tf/android-chrome-512x512.png">

    <!-- Web App Manifest -->
    <link rel="manifest" href="../site.webmanifest">

    <link rel="stylesheet" href="../_css/main.css">
</head>
<body>

    <h1>Profil de <?php echo htmlspecialchars($player['display_name']); ?></h1>
    
    <p>SteamID : <?php echo htmlspecialchars($player['steamid']); ?></p>

    <div class="stats-container">
        <h2>Statistiques</h2>
        <p>Prochainement !</p>
    </div>

    <br>
    <a href="index.php">Retour à l'accueil</a>

</body>
</html>