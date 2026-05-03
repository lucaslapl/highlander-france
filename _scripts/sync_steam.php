<?php
// Configuration et Connexion
error_reporting(E_ALL);
ini_set('display_errors', 1);
$env = parse_ini_file(__DIR__ . '/.env');
$db = new PDO('sqlite:' . __DIR__ . '/stats.db');
$STEAM_API_KEY = $env['STEAM_API_KEY'];

// Fonction de conversion SteamID3 vers SteamID64
function steamid3To64($steamid3) {
    if (preg_match('/\[U:1:(\d+)\]/', $steamid3, $matches)) {
        $account_id = $matches[1];
        // La constante pour convertir SteamID3 en 64 est 76561197960265728
        return bcadd('76561197960265728', $account_id);
    }
    return $steamid3;
}

// Fonction cURL robuste pour appeler Steam
function getSteamData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return false;
    }
    return json_decode($response, true);
}

// 1. Récupérer les IDs manquants (SteamID3 originaux)
$query = $db->query("SELECT DISTINCT s.steamid FROM player_stats s 
                     LEFT JOIN players_info p ON s.steamid = p.steamid 
                     WHERE p.steamid IS NULL");
$missing = $query->fetchAll(PDO::FETCH_COLUMN);

if (empty($missing)) {
    die("Aucun nouveau profil à traiter.");
}

echo "Nombre d'IDs à traiter : " . count($missing) . "<br>";

// 2. Traiter par paquets de 100
$chunks = array_chunk($missing, 100);

foreach ($chunks as $chunk) {
    // Conversion des IDs pour l'appel API
    $ids64 = array_map('steamid3To64', $chunk);
    $idsParam = implode(',', $ids64);
    
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$idsParam";
    
    $data = getSteamData($url);
    
    if (isset($data['response']['players'])) {
        foreach ($data['response']['players'] as $p) {
            // ATTENTION : L'API Steam renvoie le steamid en format 64
            // Mais pour que votre base de données fasse le lien avec vos matchs, 
            // il faut ré-insérer avec l'ID d'origine (SteamID3) dans players_info
            
            // On retrouve l'ID d'origine grâce au compte Steam (ID64 - 76561197960265728)
            $originalId = "[U:1:" . bcsub($p['steamid'], '76561197960265728') . "]";
            
            $stmt = $db->prepare("INSERT INTO players_info (steamid, name, avatar, last_updated) VALUES (?, ?, ?, ?)");
            $stmt->execute([$originalId, $p['personaname'], $p['avatarfull'], time()]);
            
            echo "Ajouté : " . $p['personaname'] . "<br>";
        }
    }
    // Petite pause pour être poli avec l'API
    sleep(1);
}

echo "Synchronisation terminée avec succès.";
?>