<?php
// 1. On charge la configuration
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// 2. On récupère l'ID passé dans l'URL
$steamid = $_GET['steamid'] ?? null;

// Sécurité : On vérifie le format du SteamID64
if (!$steamid || !preg_match('/^\d{17}$/', $steamid)) {
    http_response_code(400);
    header("Location: ../errors/400.php");
    exit();
}

// Mode par défaut au premier chargement de la page
$currentMode = '9v9';

// 3. On cherche le joueur dans la base de données
$steamid3 = steamID64ToSteamID3($steamid);
$stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
$stmt->execute([$steamid3]);
$player = $stmt->fetch();

// Sécurité : Si le joueur n'existe pas, on arrête tout de suite
if (!$player) {
    http_response_code(404);
    header("Location: ../errors/404.php");
    exit();
}

// Préparation des variables fixes du joueur
$date_brute = $player['created_at'] ?? null;
$date_formatee = !empty($date_brute) ? date("d/m/Y", strtotime($date_brute)) : false;
$country = $player['country'] ?? null;

/** RECUPERATION INITIALE DES STATS (Pour le premier affichage en 9v9) **/
$stmt_matches = $db->prepare("SELECT count as total_matches FROM player_stats WHERE steamid = ? AND game_mode = ?");
$stmt_matches->execute([$steamid3, $currentMode]);
$matches = $stmt_matches->fetch();

$stmtMaps = $db->prepare("SELECT map_name, COUNT(map_name) as total FROM player_matches WHERE steamid = ? AND game_mode = ? GROUP BY map_name ORDER BY total DESC LIMIT 3");
$stmtMaps->execute([$steamid3, $currentMode]);
$topMaps = $stmtMaps->fetchAll(PDO::FETCH_ASSOC);

$stmtClasses = $db->prepare("SELECT class_played, COUNT(class_played) as total FROM player_matches WHERE steamid = ? AND game_mode = ? GROUP BY class_played ORDER BY total DESC");
$stmtClasses->execute([$steamid3, $currentMode]);
$classesPlayed = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

$recentMatches = getRecentPlayerMatches($db, $steamid3, $currentMode); // ou $mode
$matchStats = getPlayerMatchStats($db, $steamid3, $currentMode);

// CONFIGURATION DES BADGES ROLES (Sans icônes)
$rolesConfig = [
    'is_founder'   => ['label' => 'Fondateur',   'class' => 'badge-founder'],
    'is_admin'     => ['label' => 'Admin',       'class' => 'badge-admin'],
    'is_moderator' => ['label' => 'Modérateur',  'class' => 'badge-moderator'],
    'is_mentor'    => ['label' => 'Mentor',      'class' => 'badge-mentor'],
    'is_mixer'     => ['label' => 'Mixer',       'class' => 'badge-mixer'],
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France - Profil de <?php echo htmlspecialchars($player['display_name'] ?? $player['name']); ?></title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - Communauté Compétitive de TF2">
    <meta property="og:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta property="og:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="highlanderfrance.tf">
    <meta property="twitter:url" content="https://highlanderfrance.tf/">
    <meta name="twitter:title" content="Highlander France - Communauté Compétitive de TF2">
    <meta name="twitter:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta name="twitter:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <link rel="shortcut icon" href="https://highlanderfrance.tf/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="https://highlanderfrance.tf/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://highlanderfrance.tf/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="https://highlanderfrance.tf/favicon.ico">

    <link rel="apple-touch-icon" href="https://highlanderfrance.tf/apple-touch-icon.png">

    <link rel="icon" type="image/png" sizes="192x192" href="https://highlanderfrance.tf/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="https://highlanderfrance.tf/android-chrome-512x512.png">

    <link rel="manifest" href="../site.webmanifest">

    <link rel="stylesheet" href="../_css/main.css">
    <link rel="stylesheet" href="_css/profile.css">

    <style>
        .staff-badges-container {
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .badge-staff {
            display: inline-block;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
            color: #fff;
            line-height: 1;
        }

        .badge-admin {
            background-color: #d9534f;
        }

        .badge-founder {
            background-color: #f0ad4e;
        }

        .badge-moderator {
            background-color: #5bc0de;
        }

        .badge-mentor {
            background-color: #5cb85c;
        }

        .badge-mixer {
            background-color: #9b59b6;
        }
    </style>

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-30553SX3GJ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'G-30553SX3GJ');
    </script>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "ProfilePage",
            "mainEntity": {
                "@type": "Person",
                "name": "<?php echo htmlspecialchars($player['display_name'] ?? $player['name']); ?>",
                "image": "<?php echo htmlspecialchars($player['avatar']); ?>",
                "description": "Profil de <?php echo htmlspecialchars($player['display_name'] ?? $player['name']); ?> sur Highlander France, communauté compétitive de Team Fortress 2.",
                "identifier": "<?php echo htmlspecialchars($steamid); ?>"
            }
        }
    </script>
</head>

<body>

    <?php include("../_inc/header.php"); ?>

    <div id="main">
        <section id="content">
            <div class="personnal-info">
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                    <div class="admin-profile-box" style="background: #2c1a1a; border: 1px solid #ff4444; padding: 15px; margin: 15px 0 15px 0; border-radius: 5px;">
                        <h4 style="color: #ff4444; margin-top: 0;"><i class="fa-solid fa-screwdriver-wrench"></i> Outils d'administration</h4>
                        <p>Vous visualisez le profil de : <strong><?= htmlspecialchars($player['display_name'] ?? $player['name']) ?></strong></p>
                        <p>SteamID64 : <code><?= htmlspecialchars($steamid) ?></code></p>
                        <p>SteamID3 : <code><?= htmlspecialchars($steamid3) ?></code></p>

                        <a href="/admin/manage_player.php?steamid=<?= htmlspecialchars($steamid) ?>" class="btn-admin" style="background: #ff4444; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; display: inline-block;">
                            <i class="fa-solid fa-user-gear"></i> Gérer ce joueur
                        </a>
                    </div>
                <?php endif; ?>

                <div class="profile-header flex align-center">
                    <img src="<?php echo htmlspecialchars($player['avatar']); ?>" alt="Avatar de <?php echo htmlspecialchars($player['display_name'] ?? $player['name']); ?>" class="profile-avatar">

                    <div class="flex flex-column justify-center gap-5" style="align-items: flex-start;">
                        <div class="flex align-center gap-10">
                            <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <?php echo htmlspecialchars($player['display_name'] ?? $player['name']); ?>
                                <?php if (!empty($country)): ?>
                                    <img src="/_img/flags/<?= htmlspecialchars($country) ?>.gif" alt="<?= $countries[$country] ?? $country ?>" class="flag-icon">
                                <?php endif; ?>
                            </h2>
                            <?php if ($date_formatee): ?>
                                <span style="font-size: 0.85rem; color: #888;">inscrit le <?= $date_formatee; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="staff-badges-container">
                            <?php foreach ($rolesConfig as $dbKey => $badgeInfo): ?>
                                <?php if (isset($player[$dbKey]) && ($player[$dbKey] == 1 || $player[$dbKey] === true)): ?>
                                    <span class="badge-staff <?= $badgeInfo['class'] ?>">
                                        <?= htmlspecialchars($badgeInfo['label']) ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <a href="https://steamcommunity.com/profiles/<?php echo $steamid; ?>" target="_blank" class="steam-profile-link" style="margin-top: 15px; display: inline-block;">
                    <i class="fab fa-steam"></i> Profil Steam
                </a>
            </div>

            <div class="profile-tabs">
                <button type="button" class="profile-tab-btn active" onclick="switchProfileMode(this, '9v9')">Highlander (9v9)</button>
                <button type="button" class="profile-tab-btn" onclick="switchProfileMode(this, '6s')">Sixes (6v6)</button>
            </div>

            <br>

            <div class="player-stats">
                <h3 id="stats-title">Stats - Highlander</h3>

                <div class="box-stats matches-played">
                    <p><b id="stat-total-matches"><?php echo $matches['total_matches'] ?? 0; ?></b> matchs joués</p>
                </div>

                <div class="box-stats damage-dealt">
                    <p><b>Dégâts moyens par match :</b> <span id="stat-total-damage"><?= number_format($matchStats['average_damage'], 0, ',', ' ') ?></span></p>
                </div>
                <div class="box-stats kills">
                    <p><b>Kills :</b> <span id="stat-total-kills"><?= $matchStats['total_kills'] ?></span></p>
                </div>
                <div class="box-stats deaths">
                    <p><b>Morts :</b> <span id="stat-total-deaths"><?= $matchStats['total_deaths'] ?></span></p>
                </div>
                <div class="box-stats kd-ratio">
                    <p><b>Ratio K/D :</b> <span id="stat-kd-ratio"><?= $matchStats['kd_ratio'] ?></span></p>
                </div>

                <div class="box-stats classes-killed">
                    <p><b>Classes tuées :</b></p>
                    <div id="classes-killed-container">
                        <?php if (empty($matchStats['classes_killed'])): ?>
                            <p class="no-data">Aucune donnée de classe tuée pour le moment.</p>
                        <?php else: ?>
                            <ul class="stats-list">
                                <?php foreach ($matchStats['classes_killed'] as $class => $count): ?>
                                    <?php $classSafe = htmlspecialchars($class); ?>
                                    <li class="flex space-between align-center">
                                        <div class="flex align-center gap-10">
                                            <img src="/_img/classes/<?= $classSafe ?>.png" alt="<?= ucfirst($classSafe) ?>" class="class-icon" title="<?= ucfirst($classSafe) ?>">
                                        </div>
                                        <span class="stat-value"><?= $count ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="box-stats maps-played">
                    <p><b>Top 3 des maps jouées :</b></p>
                    <div id="maps-container">
                        <?php if (empty($topMaps)): ?>
                            <p class="no-data">Aucune donnée de map pour le moment.</p>
                        <?php else: ?>
                            <ul class="stats-list">
                                <?php foreach ($topMaps as $map): ?>
                                    <li class="flex space-between align-center">
                                        <span class="stat-label"><?= htmlspecialchars($map['map_name']) ?></span>
                                        <span class="stat-value"><?= $map['total'] ?> match(s)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="box-stats classes-played">
                    <p><b>Classes jouées :</b></p>
                    <div id="classes-container">
                        <?php if (empty($classesPlayed)): ?>
                            <p class="no-data">Aucune donnée de classe pour le moment.</p>
                        <?php else: ?>
                            <ul class="stats-list">
                                <?php foreach ($classesPlayed as $class): ?>
                                    <?php
                                    $classNameBrut = htmlspecialchars($class['class_played']);
                                    $iconPath = "/_img/classes/" . $classNameBrut . ".png";
                                    ?>
                                    <li class="flex space-between align-center">
                                        <div class="flex align-center gap-10">
                                            <img src="<?= $iconPath ?>" alt="<?= ucfirst($classNameBrut) ?>" class="class-icon" title="<?= ucfirst($classNameBrut) ?>">
                                        </div>
                                        <span class="stat-value"><?= $class['total'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="recent-matches">
                    <h3 id="recent-title">Matchs Récents (9v9)</h3>
                    <div id="recent-container">
                        <?php if (empty($recentMatches)): ?>
                            <p class="no-data">Aucun match enregistré pour le moment.</p>
                        <?php else: ?>
                            <ul class="matches-list">
                                <?php foreach ($recentMatches as $match): ?>
                                    <?php
                                    $mId = htmlspecialchars($match['match_id']);
                                    $cPlayed = htmlspecialchars($match['class_played']);
                                    ?>
                                    <li class="flex space-between align-center match-item">
                                        <div class="flex align-center gap-15">
                                            <img src="/_img/classes/<?= $cPlayed ?>.png" alt="<?= ucfirst($cPlayed) ?>" class="class-icon" title="Joué en <?= ucfirst($cPlayed) ?>">
                                            <span class="match-map"><?= htmlspecialchars($match['map_name']) ?></span>
                                            <span class="stat-value"><?= (int)$match['kills'] ?>K / <?= (int)$match['deaths'] ?>D / <?= number_format((int)$match['dmg'], 0, ',', ' ') ?> dmg</span>
                                        </div>
                                        <a href="https://logs.tf/<?= $mId ?>" target="_blank" class="btn-log">
                                            <i class="fa-solid fa-file-lines"></i> Log #<?= $mId ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </section>
    </div>
    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
    <script src="../_js/main.js"></script>
    <script src="../_js/profil.js"></script>
</body>

</html>