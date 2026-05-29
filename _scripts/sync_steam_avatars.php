<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Initialisation du log d'audit
$logToken = logScriptExecution('sync_steam_avatars.php');

// Configuration du script pour éviter les coupures si la base est grande
set_time_limit(300); // 5 minutes max

echo "=== Début de la synchronisation des profils Steam ===\n\n";

// 2. Encapsulation globale pour traquer le comportement et le statut final
try {
    // 1. On cherche tous les joueurs qui ont besoin d'une mise à jour
    $stmt = $db->query("
        SELECT steamid 
        FROM players_info 
        WHERE name = 'Nouveau Joueur' 
           OR name IS NULL 
           OR name = '' 
           OR display_name IS 'Nouveau Joueur'
           OR avatar IS NULL 
           OR avatar = ''
    ");
    $players_to_sync = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($players_to_sync)) {
        echo "✓ Aucun joueur ne nécessite de synchronisation. Tous les profils sont à jour !\n";
        
        // Fin de script rapide et propre si tout est déjà OK
        logScriptExecution('sync_steam_avatars.php', $logToken, 'SUCCESS (Tous les profils étaient déjà à jour)');
        exit;
    }

    echo "Found " . count($players_to_sync) . " joueur(s) à synchroniser.\n";
    echo "--------------------------------------------------------\n";

    $success_count = 0;
    $error_count = 0;

    // 2. On boucle sur chaque joueur trouvé
    foreach ($players_to_sync as $player) {
        $steamid3 = $player['steamid'];
        
        // Conversion SteamID3 → SteamID64
        $steamid64 = steamID3ToSteamID64($steamid3);

        if (!$steamid64) {
            echo "[ERREUR] Impossible de convertir le SteamID3 '{$steamid3}' en SteamID64.\n";
            $error_count++;
            continue;
        }

        echo "Synchronisation de {$steamid3} (SteamID64: {$steamid64})... ";

        // Appel de la fonction de synchronisation native
        $sync_result = syncPlayerWithSteamAPI($steamid64, $db);

        if ($sync_result) {
            $checkName = $db->prepare("SELECT name FROM players_info WHERE steamid = ?");
            $checkName->execute([$steamid3]);
            $updated_name = $checkName->fetchColumn();

            echo "✓ SUCCÈS (Nouveau pseudo : '{$updated_name}')\n";
            $success_count++;
        } else {
            echo "❌ ÉCHEC (API Steam injoignable ou clé invalide)\n";
            $error_count++;
        }

        // 💡 SÉCURITÉ ANTI-BAN : Pause de 0.2 seconde entre chaque appel
        usleep(200000); 
    }

    echo "--------------------------------------------------------\n";
    echo "=== Fin de la synchronisation ===\n";
    echo "Joueurs mis à jour avec succès : {$success_count}\n";
    echo "Échecs ou erreurs : {$error_count}\n";

    // 3. SUCCÈS : Enregistrement du bilan dans cron_debug.log
    $statusMsg = "SUCCESS ({$success_count} synchronisés, {$error_count} échecs)";
    logScriptExecution('sync_steam_avatars.php', $logToken, $statusMsg);

} catch (Exception $e) {
    
    // 4. ÉCHEC : Capture de n'importe quelle erreur critique (PDO ou PHP)
    logScriptExecution('sync_steam_avatars.php', $logToken, 'FAILED: ' . $e->getMessage());
    
    echo "[ERREUR CRITIQUE] " . $e->getMessage() . "\n";
    die();
}