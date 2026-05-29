<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Initialisation du log d'audit
$logToken = logScriptExecution('sync_etf2l.php');

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
    
    // Petite sécurité cURL pour le bloc try/catch général
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

// 2. Encapsulation globale pour intercepter les erreurs
try {
    // Configuration manuelle : IDs des équipes françaises sans drapeau "France"
    $whitelistedTeams = [
        37618, // Mets ici les IDs de tes équipes françaises d'exception
    ];

    $apiUrl = "https://api-v2.etf2l.org/matches?scheduled=1";
    $responseObj = getEtf2lJson($apiUrl);

    // Si cURL a retourné une erreur
    if (isset($responseObj['error'])) {
        throw new Exception("Erreur cURL API ETF2L : " . $responseObj['error']);
    }

    // Si l'API renvoie du vide ou un code d'erreur HTTP masqué
    if (!$responseObj || (isset($responseObj['status']) && $responseObj['status']['code'] !== 200)) {
        $msg = $responseObj['status']['message'] ?? 'Réponse invalide/inaccessible';
        throw new Exception("L'API ETF2L a répondu négativement : " . $msg);
    }

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

        // FILTRE 1 : On compare avec la chaîne "france"
        $isFr1 = (isset($t1['country']) && strtolower($t1['country']) === 'france');
        $isFr2 = (isset($t2['country']) && strtolower($t2['country']) === 'france');
        
        // FILTRE 2 : Ta liste d'IDs manuels
        $isWhitelisted1 = in_array($t1Id, $whitelistedTeams);
        $isWhitelisted2 = in_array($t2Id, $whitelistedTeams);

        // Si le match concerne la France d'une manière ou d'une autre
        if ($isFr1 || $isFr2 || $isWhitelisted1 || $isWhitelisted2) {
            
            $stmt = $db->prepare("
                INSERT OR REPLACE INTO etf2l_matches (match_id, team1_name, team2_name, match_date, competition_name, team1_country, team2_country)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $competitionName = $m['competition']['name'] ?? 'Compétition ETF2L';
            $matchTimestamp = (int)($m['time'] ?? time());
            
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

    // 3. SUCCÈS : Écriture de la réussite dans ton cron_debug.log
    $statusMsg = "SUCCESS (" . $insertedCount . " match(s) français synchronisé(s))";
    logScriptExecution('sync_etf2l.php', $logToken, $statusMsg);

    echo "Agenda synchronisé ! {$insertedCount} match(s) français ajouté(s) en base de données.";

} catch (Exception $e) {
    
    // 4. ÉCHEC : Log de l'erreur dans l'audit et blocage du script
    logScriptExecution('sync_etf2l.php', $logToken, 'FAILED: ' . $e->getMessage());
    
    die("Erreur lors de la synchronisation de l'agenda : " . $e->getMessage());
}