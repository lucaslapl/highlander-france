<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
// 1. On charge la configuration
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// 2. On récupère l'ID passé dans l'URL (ex: profil.php?steamid=7656...)
$steamid = $_GET['steamid'] ?? null;
// Sécurité : On vérifie que c'est bien une chaîne de chiffres
// Le SteamID64 fait toujours 17 caractères chez Steam.
if (!preg_match('/^\d{17}$/', $steamid)) {
    die("Format de SteamID invalide.");
}

// 3. Si aucun ID n'est fourni, on redirige vers l'accueil
if (!$steamid) {
    die("Aucun profil sélectionné.");
}

// 4. On cherche le joueur dans la base de données
$steamid3 = steamID64ToSteamID3($steamid);
$stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
$stmt->execute([$steamid3]);
$player = $stmt->fetch();

$date_brute = $player['created_at'];
$date_formatee = $date_brute ? date('d/m/Y', strtotime($date_brute)) : "n/c";

$stmt_matches = $db->prepare("SELECT count as total_matches FROM player_stats WHERE steamid = ?");
$stmt_matches->execute([$steamid3]);
$matches = $stmt_matches->fetch();

// 5. Si le joueur n'existe pas en base
if (!$player) {
    die("Profil introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France - Profil de <?php echo htmlspecialchars($player['display_name']); ?></title>
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
    <link rel="manifest" href="../site.webmanifest">

    <link rel="stylesheet" href="../_css/main.css">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-30553SX3GJ"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-30553SX3GJ');
    </script>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ProfilePage",
        "mainEntity": {
            "@type": "Person",
            "name": "<?php echo htmlspecialchars($player['display_name']); ?>",
            "image": "<?php echo htmlspecialchars($player['avatar']); ?>",
            "description": "Profil de <?php echo htmlspecialchars($player['display_name']); ?> sur Highlander France, communauté compétitive de Team Fortress 2."
            "identifier": "<?php echo htmlspecialchars($steamid); ?>",
        }
    }
    </script>
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
        body #main #content .personnal-info .steam-profile-link {
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <?php include("../_inc/header.php"); ?>

    <div id="main">
        <section id="content">
            <div class="personnal-info">
                <div class="profile-header flex align-center">
                    <img src="<?php echo htmlspecialchars($player['avatar']); ?>" alt="Avatar de <?php echo htmlspecialchars($player['display_name']); ?>" class="profile-avatar">
                    <h2><?php echo htmlspecialchars($player['display_name'] ?? 'Joueur'); ?> <span>inscrit le <?php echo $date_formatee; ?></span></h2>
                </div>
                <p>SteamID : <?php echo $steamid3; ?></p>
                <a href="https://steamcommunity.com/profiles/<?php echo $steamid; ?>" target="_blank" class="steam-profile-link">
                    <i class="fab fa-steam"></i> Profil Steam
                </a>
            </div>

            <br>

            <div class="player-stats">
                <h3>Stats</h3>
                <p><b><?php echo $matches['total_matches'] ?? 0; ?></b> matchs joués</p>
                <br>
                <p><b>D'autres stats à venir !</b></p>
            </div>

        </section>
    </div>
    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
</body>
</html>