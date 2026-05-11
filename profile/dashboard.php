<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
// On charge la configuration et les fonctions
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// 1. Protection : Si pas de session, on renvoie à la connexion
if (!isset($_SESSION['steamid'])) {
    header('Location: ../login.php');
    exit;
}

$env = parse_ini_file(__DIR__ . '/../_inc/.env');
$STEAM_API_KEY = $env['STEAM_API_KEY'];

$steamid64 = $_SESSION['steamid']; // ou $_GET['steamid']

// 2. Conversion vers le format stocké en base de données
$steamid3 = steamID64ToSteamID3($steamid64);

// 3. Récupération des infos du joueur connecté
$stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
$stmt->execute([$steamid3]);
$user = $stmt->fetch();
//S'il n'existe pas, on l'ajoute
if ($user === false) {
    try {
        $insert = $db->prepare("INSERT INTO players_info (steamid, display_name) VALUES (?, ?)");
        $insert->execute([$steamid3, 'Nouveau Joueur']);
        
        // On recharge les données
        $stmt->execute([$steamid3]);
        $user = $stmt->fetch();
        echo "";
    } catch (PDOException $e) {
        die("Erreur lors de l'insertion : " . $e->getMessage());
    }
}

// 4. Maintenant que $user est défini, on vérifie si on doit synchroniser
// Note : on convertit last_updated en entier pour être sûr
$last_update = (int)($user['last_updated'] ?? 0);

if (empty($user['name']) || ($last_update < time() - 86400)) {
    // Appel de la fonction de synchro
    syncSteamProfile($steamid3, $db, $STEAM_API_KEY);
    
    // On recharge les données car elles ont changé en base
    $stmt->execute([$steamid3]);
    $user = $stmt->fetch();
}

// 5. Affichage
$date_brute = $user['created_at'];
$date_formatee = $date_brute ? date('d/m/Y', strtotime($date_brute)) : "n/c";

$stmt_matches = $db->prepare("SELECT count as total_matches FROM player_stats WHERE steamid = ?");
$stmt_matches->execute([$steamid3]);
$matches = $stmt_matches->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France - Mon profil</title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - Communauté Compétitive de TF2">
    <meta property="og:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta property="og:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="highlanderfrance.tf">
    <meta property="twitter:url" content="https://highlanderfrance.tf/">
    <meta name="twitter:title" content="Highlander France - Communauté Compétitive de TF2">
    <meta name="twitter:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta name="twitter:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <!-- Favicon standard -->
    <link rel="shortcut icon" href="https://highlanderfrance.tf/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="https://highlanderfrance.tf/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://highlanderfrance.tf/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="https://highlanderfrance.tf/favicon.ico">

    <!-- Apple Touch Icon (iPhone/iPad) -->
    <link rel="apple-touch-icon" href="https://highlanderfrance.tf/apple-touch-icon.png">

    <!-- Android Chrome -->
    <link rel="icon" type="image/png" sizes="192x192" href="https://highlanderfrance.tf/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="https://highlanderfrance.tf/android-chrome-512x512.png">

    <!-- Web App Manifest -->
    <link rel="manifest" href="/site.webmanifest">

    <link rel="stylesheet" href="../_css/main.css">
    <style>
        body #header {
            height: 425px;
            position: relative;
        }

        body #main {
            min-height: 500px;
            position: relative;
        }
        body #main #content .personnal-info .profile-header {
            gap: 20px;
        }
        body #main #content .personnal-info .profile-header img {
            width: 125px;
            border-radius: 50%;
        }
        body #main #content .personnal-info .profile-header h2 {
            font-size: 3em;
        }
        body #main #content .personnal-info .profile-header h2 span {
            font-size: 0.25em;
            font-weight: normal;
        }
    </style>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-30553SX3GJ"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-30553SX3GJ');
    </script>
</head>
<body>

    <?php include("../_inc/header.php"); ?>

    <div id="main">
        <section id="content">
            <div class="personnal-info">
                <div class="profile-header flex align-center">
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar de <?php echo htmlspecialchars($user['display_name']); ?>" class="profile-avatar">
                    <h2><?php echo htmlspecialchars($user['display_name'] ?? 'Joueur'); ?> <span>inscrit le <?php echo $date_formatee; ?></span></h2>
                </div>
                <h3>Informations personnelles</h3>
                <p>SteamID : <?php echo $steamid3; ?></p>
            </div>

            <br>

            <div class="player-stats">
                <h3>Vos stats</h3>
                <p><b><?php echo $matches['total_matches'] ?? 0; ?></b> matchs joués</p>
                <br>
                <p><b>D'autres stats à venir !</b></p>
            </div>
            <!--
            <?php if (isset($_GET['success'])): ?>
                <div style="color: green; margin-bottom: 10px;">
                    Votre pseudo a bien été mis à jour !
                </div>
            <?php endif; ?>

            <form action="update_profile.php" method="POST">
                <label>Mon nom d'affichage :</label>
                <?php
                // On sécurise : si $user n'est pas un tableau (donc false), on met une chaîne vide
                $valeur_nom = ($user !== false && isset($user['display_name'])) ? $user['display_name'] : '';
                ?>

                <input type="text" name="display_name" value="<?php echo htmlspecialchars($valeur_nom); ?>">
                <button type="submit">Enregistrer</button>
            </form>
            -->
        </section>
    </div>

    <?php include("../_inc/footer.php"); ?>

</body>
</html>