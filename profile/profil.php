<?php
// 1. On charge la configuration
require_once __DIR__ . '/../_inc/config.php';

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
    <title>Profil de <?php echo htmlspecialchars($player['display_name']); ?></title>
</head>
<body>

    <h1>Profil de <?php echo htmlspecialchars($player['display_name']); ?></h1>
    
    <p>SteamID : <?php echo htmlspecialchars($player['steamid']); ?></p>

    <div class="stats-container">
        <h2>Statistiques</h2>
        <p>Section en construction...</p>
    </div>

    <br>
    <a href="index.php">Retour à l'accueil</a>

</body>
</html>