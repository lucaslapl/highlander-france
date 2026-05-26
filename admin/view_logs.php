<?php
require_once __DIR__ . "/../_inc/config.php";
require_once __DIR__ . "/../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : Si le visiteur n'est pas admin, le script meurt ici immédiatement.
checkAdminOrDie();

// Chemin vers ton fichier de log (à adapter si nécessaire)
$log_file_path = __DIR__ . '/../cron_debug.log';
$log_content = "";
$file_exists = false;
$file_size = "0 Octets";
$bytes = 0;

if (file_exists($log_file_path)) {
    $file_exists = true;
    
    // Calcul de la taille du fichier pour le résumé
    $bytes = filesize($log_file_path);
    if ($bytes >= 1048576) {
        $file_size = number_format($bytes / 1048576, 2) . ' Mo';
    } elseif ($bytes >= 1024) {
        $file_size = number_format($bytes / 1024, 2) . ' Ko';
    } else {
        $file_size = $bytes . ' Octets';
    }

    // Lecture sécurisée du fichier
    $file_lines = file($log_file_path);
    
    if ($file_lines !== false) {
        // On récupère uniquement les 100 dernières lignes pour la performance
        $last_lines = array_slice($file_lines, -100);
        
        // On les inverse pour afficher le PLUS RÉCENT en tout premier (haut de page)
        $last_lines = array_reverse($last_lines);
        
        $log_content = implode("", $last_lines);
    }
    
    if (empty($log_content)) {
        $log_content = "Le fichier de log existe mais il est actuellement vide.";
    }
} else {
    $log_content = "Aucun enregistrement trouvé.\nLe fichier 'cron_debug.log' n'a pas encore été généré à la racine du site.";
}

// Gestion de la réinitialisation (vidage) du fichier de log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    if ($file_exists) {
        // On écrase le fichier avec du contenu vide
        file_put_contents($log_file_path, "");
        $_SESSION['success'] = "Le journal d'erreurs a été réinitialisé avec succès !";
    }
    header("Location: view_logs.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Journaux Système</title>
    <link rel="stylesheet" href="../_css/main.css">
    <link rel="stylesheet" href="_css/admin.css">
    <style>
        .log-meta-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1a1a1a;
            border: 1px solid #2b2b2b;
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .log-viewer {
            background-color: #09090b;
            border: 1px solid #222;
            border-top: 4px solid #3498db;
            color: #e4e4e7;
            font-family: 'Consolas', 'Courier New', monospace;
            padding: 20px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 600px;
            overflow-y: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .btn-clear {
            background-color: #2b2b2b;
            border: 1px solid #444;
            color: #f35f5f;
            padding: 8px 15px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-clear:hover {
            background-color: #381e1e;
            border-color: #f35f5f;
        }
    </style>
</head>
<body>

    <?php include("../_inc/header.php"); ?>

    <main id="main" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 20px;">
            <a href="dashboard" style="color: #aaa; text-decoration: none; font-size: 14px;">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="background: #1c3d27; color: #2ecc71; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                <i class="fa-solid fa-circle-check"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="admin-header" style="border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 30px;">
            <h2 style="color: #3498db; margin: 0;"><i class="fa-solid fa-database"></i> Inspecteur de Journaux (Logs)</h2>
            <p style="margin: 5px 0 0 0; color: #aaa;">Analyse en direct des rapports d'exécution de l'API et détection des pannes des scripts CRON.</p>
        </div>

        <div class="log-meta-box">
            <div style="font-size: 14px; color: #ccc;">
                Fichier ciblé : <strong style="color: #fff; font-family: monospace;">cron_debug.log</strong> 
                <span style="color: #555; margin: 0 10px;">|</span> 
                Taille actuelle : <strong style="color: #3498db;"><?= $file_size ?></strong>
            </div>
            
            <?php if ($file_exists && $bytes > 0): ?>
                <form action="" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir vider l\'intégralité des logs ? Cette action est irréversible.');">
                    <button type="submit" name="clear_logs" class="btn-clear">
                        <i class="fa-solid fa-trash-can"></i> Nettoyer le journal
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <h3 style="font-size: 15px; color: #aaa; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px;">
            <i class="fa-solid fa-terminal"></i> 100 derniers événements (du plus récent au plus ancien)
        </h3>
        
        <div class="log-viewer"><?= htmlspecialchars($log_content) ?></div>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
</body>
</html>