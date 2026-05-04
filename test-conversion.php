<?php
// On charge la config
require_once __DIR__ . '/_inc/config.php';

// Fonction de conversion
function steamID64ToSteamID3($steamid64) {
    $steamid_constant = 76561197960265728;
    // On utilise bcsub si disponible, sinon on fait une soustraction directe (si PHP 64bits)
    if (function_exists('bcsub')) {
        $account_id = bcsub($steamid64, (string)$steamid_constant);
    } else {
        $account_id = $steamid64 - $steamid_constant;
    }
    return "[U:1:" . $account_id . "]";
}

// 1. Simulation du SteamID64 (Prenez le vôtre dans votre session)
if (!isset($_SESSION['steamid'])) {
    die("Erreur : Vous n'êtes pas connecté, je ne peux pas tester avec votre session.");
}
$mySteam64 = $_SESSION['steamid'];

// 2. Conversion
$mySteam3 = steamID64ToSteamID3($mySteam64);

// 3. Test de la requête
echo "SteamID64 d'origine : " . $mySteam64 . "<br>";
echo "SteamID3 converti : " . $mySteam3 . "<br><br>";

$stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
$stmt->execute([$mySteam3]);
$user = $stmt->fetch();

if ($user) {
    echo "✅ SUCCÈS : Joueur trouvé dans la base !<br>";
    echo "Nom associé : " . $user['display_name'];
} else {
    echo "❌ ÉCHEC : Aucun joueur trouvé avec le SteamID3 " . $mySteam3 . ".";
    echo "<br>Vérifiez que ce format (avec les crochets) est bien présent dans votre table sqlite.";
}
?>