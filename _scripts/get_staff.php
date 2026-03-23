<?php
$env = parse_ini_file(__DIR__ . '/.env');
header('Content-Type: application/json');

$apiKey = $env['STEAM_API_KEY'];
$staff = [
    "76561198051084840", // Kaylus
    "76561197974486633", // Schmit
    "76561198158964214"  // Zen
];

$steamids = implode(",", $staff);

$url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/?key=$apiKey&steamids=$steamids";
$data = json_decode(file_get_contents($url), true);

echo json_encode($data["response"]["players"]);
