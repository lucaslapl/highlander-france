<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

if (php_sapi_name() !== 'cli' && !isset($bypassing_cli_security)) {
    checkAdminOrDie();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Configuration du script pour éviter les coupures si la base est grande
set_time_limit(300); // 5 minutes max
//header('Content-Type: text/plain; charset=utf-8');

echo "=== Début de la synchronisation des profils Steam ===\n\n";

try {
    // 1. On cherche tous les joueurs qui ont besoin d'une mise à jour
    // (Nom générique "Nouveau Joueur", nom vide, ou avatar manquant)
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
    }

    echo "Found " . count($players_to_sync) . " joueur(s) à synchroniser.\n";
    echo "--------------------------------------------------------\n";

    $success_count = 0;
    $error_count = 0;

    // 2. On boucle sur chaque joueur trouvé
    foreach ($players_to_sync as $player) {
        $steamid3 = $player['steamid'];
        
        // Ta base de données utilise les SteamID3 (ex: [U:1:XXXXXX])
        // Mais l'API Steam nécessite impérativement le SteamID64 (7656119XXXXXXXXXX)
        $steamid64 = steamID3ToSteamID64($steamid3);

        if (!$steamid64) {
            echo "[ERREUR] Impossible de convertir le SteamID3 '{$steamid3}' en SteamID64.\n";
            $error_count++;
            continue;
        }

        echo "Synchronisation de {$steamid3} (SteamID64: {$steamid64})... ";

        // On appelle la fonction magique que nous avons ajoutée dans ton functions.php
        $sync_result = syncPlayerWithSteamAPI($steamid64, $db);

        if ($sync_result) {
            // Optionnel : On récupère le nouveau nom pour l'afficher dans le terminal admin
            $checkName = $db->prepare("SELECT name FROM players_info WHERE steamid = ?");
            $checkName->execute([$steamid3]);
            $updated_name = $checkName->fetchColumn();

            echo "✓ SUCCÈS (Nouveau pseudo : '{$updated_name}')\n";
            $success_count++;
        } else {
            echo "❌ ÉCHEC (API Steam injoignable ou clé invalide)\n";
            $error_count++;
        }

        // 💡 SÉCURITÉ ANTI-BAN : On fait une micro-pause de 0.2 seconde entre chaque appel d'API
        // pour ne pas saturer et se faire bloquer notre clé API par Valve / Steam.
        usleep(200000); 
    }

    echo "--------------------------------------------------------\n";
    echo "=== Fin de la synchronisation ===\n";
    echo "Joueurs mis à jour avec succès : {$success_count}\n";
    echo "Échecs ou erreurs : {$error_count}\n";

} catch (PDOException $e) {
    echo "[ERREUR CRITIQUE BDD] " . $e->getMessage() . "\n";
}