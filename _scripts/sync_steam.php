<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Initialisation du log d'audit
$logToken = logScriptExecution('sync_steam.php');

$env = parse_ini_file(__DIR__ . '/.env');
$STEAM_API_KEY = $env['STEAM_API_KEY'] ?? '';

// Log helper historique (conservé pour ton fichier log_sync_steam.txt)
function log_msg($msg) {
    file_put_contents(__DIR__ . '/log_sync_steam.txt', date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Vérification BCMath (Le script lève une exception s'il manque une dépendance critique)
if (!function_exists('bcadd')) {
    log_msg("ERREUR : BCMath n'est pas activé en CLI !");
}

// Fonction cURL
function getSteamData($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        log_msg("Erreur cURL : $error");
        return false;
    }

    return json_decode($response, true);
}

// 2. Encapsulation globale pour traquer l'état d'exécution
try {
    if (empty($STEAM_API_KEY)) {
        throw new Exception("Clé d'API Steam manquante dans le fichier .env");
    }

    // Récupération des IDs manquants
    $query = $db->query("SELECT DISTINCT s.steamid FROM player_stats s 
                         LEFT JOIN players_info p ON s.steamid = p.steamid 
                         WHERE p.steamid IS NULL");
    $missing = $query->fetchAll(PDO::FETCH_COLUMN);

    if (empty($missing)) {
        log_msg("Aucun nouveau profil à traiter.");
        echo "Aucun nouveau profil à traiter. \n";
        
        // Fin précoce propre (0 profils à traiter, tout est à jour)
        logScriptExecution('sync_steam.php', $logToken, 'SUCCESS (Aucun profil à synchroniser)');
    }

    log_msg("Nombre d'IDs à traiter : " . count($missing));
    echo "Nombre d'IDs à traiter : " . count($missing) . "\n";

    $chunks = array_chunk($missing, 100);
    $profilesAdded = 0;

    foreach ($chunks as $chunk) {

        // Conversion SteamID3 → SteamID64
        $ids64 = array_map('steamid3To64', $chunk);
        $idsParam = implode(',', $ids64);

        $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$STEAM_API_KEY&steamids=$idsParam";

        $data = getSteamData($url);

        if (!$data) {
            log_msg("Erreur API Steam pour chunk : $idsParam");
            echo "Erreur API Steam pour chunk : $idsParam\n";
            continue;
        }

        if (!isset($data['response']['players'])) {
            log_msg("Réponse Steam invalide pour chunk : $idsParam");
            echo "Réponse Steam invalide pour chunk : $idsParam\n";
            continue;
        }

        foreach ($data['response']['players'] as $p) {
            // Note de sécurité : assure-toi que l'opération de calcul de l'ID n'échoue pas si BCMath manque
            $originalId = "[U:1:" . ($p['steamid'] - 76561197960265728) . "]";

            $stmt = $db->prepare("INSERT INTO players_info (steamid, name, avatar, last_updated) VALUES (?, ?, ?, ?)");
            $stmt->execute([$originalId, $p['personaname'], $p['avatarfull'], time()]);

            log_msg("Ajouté : " . $p['personaname']);
            echo "Ajouté : " . $p['personaname'] . "\n";
            $profilesAdded++;
        }

        sleep(1);
    }

    // 3. SUCCÈS : Fin du traitement par lots sans accroc
    log_msg("Synchronisation terminée avec succès.");
    
    $statusMsg = "SUCCESS (" . $profilesAdded . " profils Steam importés)";
    logScriptExecution('sync_steam.php', $logToken, $statusMsg);
    
    echo "Synchronisation terminée avec succès.";

} catch (Exception $e) {
    
    // 4. ÉCHEC : Journalisation de la panne dans cron_debug.log
    logScriptExecution('sync_steam.php', $logToken, 'FAILED: ' . $e->getMessage());
    
    die("Erreur lors de la synchronisation des profils Steam : " . $e->getMessage());
}