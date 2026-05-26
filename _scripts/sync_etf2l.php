<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);


function getEtf2lJson($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Highlander France Bot/1.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// =========================================================================
// CONFIGURATION MANUELLE : IDs des équipes françaises sans drapeau "France"
// =========================================================================
$whitelistedTeams = [
    37618, // Mets ici les IDs de tes équipes françaises d'exception
];

// 1. Appel de l'API avec le bon paramètre validé par ton debug
$apiUrl = "https://api-v2.etf2l.org/matches?scheduled=1";
$responseObj = getEtf2lJson($apiUrl);

$matches = $responseObj['results']['data'] ?? [];

// On vide la table locale pour rafraîchir l'agenda
$db->exec("DELETE FROM etf2l_matches");

$insertedCount = 0;

foreach ($matches as $m) {
    $t1 = $m['clan1'] ?? null;
    $t2 = $m['clan2'] ?? null;
    
    if (!$t1 || !$t2) {
        continue; 
    }

    $t1Id = (int)($t1['id'] ?? 0);
    $t2Id = (int)($t2['id'] ?? 0);

    // FILTRE 1 : On compare avec la chaîne "france" (en minuscule pour éviter les surprises)
    $isFr1 = (isset($t1['country']) && strtolower($t1['country']) === 'france');
    $isFr2 = (isset($t2['country']) && strtolower($t2['country']) === 'france');
    
    // FILTRE 2 : Ta liste d'IDs manuels
    $isWhitelisted1 = in_array($t1Id, $whitelistedTeams);
    $isWhitelisted2 = in_array($t2Id, $whitelistedTeams);

    // Si le match concerne la France d'une manière ou d'une info
    if ($isFr1 || $isFr2 || $isWhitelisted1 || $isWhitelisted2) {
        
        // Ajout des colonnes dans la requête
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO etf2l_matches (match_id, team1_name, team2_name, match_date, competition_name, team1_country, team2_country)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $competitionName = $m['competition']['name'] ?? 'Compétition ETF2L';
        $matchTimestamp = (int)($m['time'] ?? time());
        
        // On passe les pays en minuscules pour faciliter les comparaisons plus tard
        $country1 = isset($t1['country']) ? strtolower($t1['country']) : 'unknown';
        $country2 = isset($t2['country']) ? strtolower($t2['country']) : 'unknown';
        
        $stmt->execute([
            $m['id'] ?? null,
            $t1['name'] ?? 'TBD',
            $t2['name'] ?? 'TBD',
            $matchTimestamp,
            $competitionName,
            $country1,
            $country2
        ]);
        
        $insertedCount++;
    }
}

echo "Agenda synchronisé ! {$insertedCount} match(s) français ajouté(s) en base de données.";