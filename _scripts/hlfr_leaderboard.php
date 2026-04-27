<?php
header('Content-Type: application/json');
$env = parse_ini_file(__DIR__ . '/.env');

// -------------------------------
// CONFIG
// -------------------------------
$steamApiKey = $env['STEAM_API_KEY']; // ← Mets ta clé Steam ici
$cacheFile = __DIR__ . '/cache_leaderboard.json';
$cacheDuration = 3600; // 1 heure

// -------------------------------
// 1. Vérifier le cache
// -------------------------------
if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);

    if (time() - $cache['timestamp'] < $cacheDuration) {
        echo json_encode($cache['data']);
        exit;
    }
}

// -------------------------------
// 2. Récupérer les logs HLFR
// -------------------------------
$url = "https://logs.tf/api/v1/log?title=Highlander%20France&limit=500";
$data = json_decode(file_get_contents($url), true);

// Logs à exclure
$blacklist = [
    4040598
];

$playerCount = [];

// -------------------------------
// 3. Parcourir les logs
// -------------------------------
foreach ($data["logs"] as $log) {

    if (in_array($log["id"], $blacklist)) {
        continue;
    }

    $logId = $log["id"];
    $detailsUrl = "https://logs.tf/api/v1/log/$logId";
    $details = @json_decode(file_get_contents($detailsUrl), true);

    if (!$details || !isset($details["players"])) {
        continue;
    }

    foreach ($details["players"] as $steam3 => $playerData) {

        if (!isset($playerCount[$steam3])) {
            $playerCount[$steam3] = 0;
        }

        $playerCount[$steam3]++;
    }
}

// -------------------------------
// 4. Convertir SteamID3 → SteamID64
// -------------------------------
function steam3ToSteam64($steam3) {
    // Format : [U:1:198698486]
    preg_match('/

\[U:1:(\d+)\]

/', $steam3, $matches);
    if (!isset($matches[1])) return null;

    return 76561197960265728 + intval($matches[1]);
}

// -------------------------------
// 5. Récupérer pseudos + avatars
// -------------------------------
$leaderboard = [];

foreach ($playerCount as $steam3 => $count) {

    $steam64 = steam3ToSteam64($steam3);

    if (!$steam64) continue;

    // Appel API Steam
    $steamUrl = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=$steamApiKey&steamids=$steam64";
    $steamData = json_decode(file_get_contents($steamUrl), true);

    $player = $steamData["response"]["players"][0] ?? null;

    $leaderboard[] = [
        "steamid3" => $steam3,
        "steamid64" => $steam64,
        "name" => $player["personaname"] ?? "Unknown",
        "avatar" => $player["avatarfull"] ?? "",
        "matches" => $count
    ];
}

// -------------------------------
// 6. Trier par nombre de matchs
// -------------------------------
usort($leaderboard, function($a, $b) {
    return $b["matches"] - $a["matches"];
});

// -------------------------------
// 7. Sauvegarde cache
// -------------------------------
file_put_contents($cacheFile, json_encode([
    "timestamp" => time(),
    "data" => $leaderboard
], JSON_PRETTY_PRINT));

// -------------------------------
// 8. Retour JSON
// -------------------------------
echo json_encode($leaderboard);
