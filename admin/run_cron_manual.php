<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . "/../_inc/config.php";
require_once __DIR__ . "/../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : Si le visiteur n'est pas admin, le script meurt ici immédiatement.
checkAdminOrDie();

// 1. Définition de la liste blanche des scripts exécutables
$available_scripts = [
    'etf2l_matches' => 'sync_etf2l.php',
    'index_stats'   => 'update_index_stats.php',
    'match_stats' => 'update_stats.php',
    'sync_with_steam' => 'sync_steam.php',
    'generate_json' => 'generate_json.php',
    'sync_steam_avatars' => 'sync_steam_avatars.php',
    'backfill_log_dates' => 'backfill_log_dates.php',
    'migrate_player_match_stats' => 'migrate_player_match_stats.php',
    'backfill_player_match_stats' => 'backfill_player_match_stats.php',
    'backfill_match_teams' => 'backfill_match_teams.php',
];

$output = "";
$executed = false;
$selected_action = "";
$return_status = 0; // Par défaut, on part du principe que ça réussit

// 2. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_cron'])) {
    $selected_action = $_POST['cron_action'] ?? '';

    // 🔥 SÉCURITÉ : On vérifie STRICTEMENT que l'action demandée fait partie de notre liste blanche
    if (array_key_exists($selected_action, $available_scripts)) {

        $script_name = $available_scripts[$selected_action];
        $cron_script_path = __DIR__ . '/../_scripts/' . $script_name;

        if (file_exists($cron_script_path)) {

            // 💡 ASTUCE DE CONTOURNEMENT : On démarre un "tampon de sortie" (Output Buffering)
            // Cela va capturer tous les "echo" ou textes que le script va générer
            ob_start();

            try {
                $bypassing_cli_security = true; // Permet de contourner la vérification CLI dans les scripts
                // On exécute le script directement dans le même processus PHP
                // (Pas besoin d'avoir les droits système exec() !)
                include $cron_script_path;

                // On récupère tout ce que le script a affiché et on ferme le tampon
                $output = ob_get_clean();
                $return_status = 0; // Tout s'est bien passé
            } catch (Throwable $e) {
                // Si le script plante (Erreur SQL, etc.), on attrape l'erreur proprement
                ob_end_clean();
                $output = "[ERREUR FATALE LORS DE L'EXÉCUTION] :\n" . $e->getMessage();
                $return_status = 1;
            }

            if (empty($output)) {
                $output = "Le script s'est exécuté avec succès mais n'a renvoyé aucun texte.";
            }
            $executed = true;
        } else {
            $output = "Erreur critique : Le fichier à exécuter est introuvable.\nChemin : " . $cron_script_path;
            $return_status = 1;
            $executed = true;
        }
    } else {
        $output = "Erreur de sécurité : Action non autorisée.";
        $return_status = 1;
        $executed = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tâches Multi-CRON</title>
    <link rel="stylesheet" href="../_css/main.css">
    <link rel="stylesheet" href="_css/admin.css">
</head>

<body>

    <?php include("../_inc/header.php"); ?>

    <main id="main" class="admin-main">

        <div class="admin-back">
            <a href="dashboard">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
        </div>

        <div class="admin-header" style="--accent: #f39c12;">
            <h2><i class="fa-solid fa-gears"></i> Console d'Exécution des Tâches CRON</h2>
            <p>Sélectionnez et forcez l'exécution de l'un des scripts automatisés du serveur.</p>
            <p><b>NE PAS UTILISER SAUF URGENCE OU SANS Y AVOIR ÉTÉ INVITÉ.</b></p>
        </div>

        <div class="admin-card">
            <form action="" method="POST" class="flex flex-column">

                <label for="cron_action" class="admin-form-label">
                    Sélectionner l'opération à lancer :
                </label>

                <select name="cron_action" id="cron_action" class="cron-select" required>
                    <option value="" disabled selected>-- Choisir un script --</option>
                    <option value="etf2l_matches" <?= $selected_action === 'etf2l_matches' ? 'selected' : '' ?>>Récupération des matchs ETF2L FR (sync_etf2l.php)</option>
                    <option value="index_stats" <?= $selected_action === 'index_stats' ? 'selected' : '' ?>>Mise à jour des stats de la page d'accueil (update_index_stats.php)</option>
                    <option value="match_stats" <?= $selected_action === 'match_stats' ? 'selected' : '' ?>>Mise à jour des stats de match pour les joueurs (update_stats.php)</option>
                    <option value="sync_with_steam" <?= $selected_action === 'sync_with_steam' ? 'selected' : '' ?>>Synchronisation avec Steam (sync_steam.php)</option>
                    <option value="generate_json" <?= $selected_action === 'generate_json' ? 'selected' : '' ?>>Génération du fichier JSON (leaderboard) (generate_json.php)</option>
                    <option value="sync_steam_avatars" <?= $selected_action === 'sync_steam_avatars' ? 'selected' : '' ?>>Synchronisation avec Steam (en cas de profils cassés) (sync_steam_avatars.php)</option>
                    <option value="backfill_log_dates" <?= $selected_action === 'backfill_log_dates' ? 'selected' : '' ?>>Backfill des dates de matchs (backfill_log_dates.php)</option>
                    <option value="migrate_player_match_stats" <?= $selected_action === 'migrate_player_match_stats' ? 'selected' : '' ?>>Migration des stats de match (migrate_player_match_stats.php)</option>
                    <option value="backfill_player_match_stats" <?= $selected_action === 'backfill_player_match_stats' ? 'selected' : '' ?>>Backfill des stats de match (backfill_player_match_stats.php)</option>
                    <option value="backfill_match_teams" <?= $selected_action === 'backfill_match_teams' ? 'selected' : '' ?>>Backfill des équipes et scores de match (backfill_match_teams.php)</option>
                </select>

                <div>
                    <button type="submit" name="trigger_cron" class="admin-btn admin-btn--primary" style="--accent: #f39c12;">
                        <i class="fa-solid fa-play"></i> Lancer le script sélectionné
                    </button>
                </div>
            </form>

            <?php if ($executed): ?>
                <hr style="border: 0; border-top: 1px solid #333; margin: 30px 0 20px 0;">

                <h3>Résultat d'exécution : <span style="font-family: monospace; color: #f39c12;"><?= htmlspecialchars($available_scripts[$selected_action] ?? '') ?></span></h3>

                <?php if ($return_status === 0): ?>
                    <span class="status-badge status-success"><i class="fa-solid fa-check"></i> SUCCÈS (Code 0)</span>
                <?php else: ?>
                    <span class="status-badge status-error"><i class="fa-solid fa-triangle-exclamation"></i> ÉCHEC (Code <?= $return_status ?>)</span>
                <?php endif; ?>

                <div class="terminal-box"><?= htmlspecialchars($output) ?></div>
            <?php endif; ?>
        </div>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
</body>

</html>