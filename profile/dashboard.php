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
$date_brute = $user['created_at'] ?? null;
$date_formatee = $date_brute ? date('d/m/Y', strtotime($date_brute)) : false;

$country = $user['country'] ?? null;
$isLocked = (int)($user['country_locked'] ?? 0);

$countries = [
    'fr' => 'France',
    'be' => 'Belgique',
    'sw' => 'Suisse',
    'lu' => 'Luxembourg',
    'uk' => 'Royaume-Uni',
    'eu' => 'Europe',
    'al' => 'Algérie',
    'mo' => 'Maroc',
    'tu' => 'Tunisie',
    'ca' => 'Canada',
    'breizh' => 'Bretagne',
];

// GET STATS

$currentMode = '9v9';

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

$stmtRecent = $db->prepare("SELECT match_id, map_name, class_played FROM player_matches WHERE steamid = ? AND game_mode = ? ORDER BY match_id DESC LIMIT 5");
$stmtRecent->execute([$steamid3, $currentMode]);
$recentMatches = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="_css/profile.css">

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

                <!-- Affichage des messages de succès ou d'erreur -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div style="background: #4CAF50; color: white; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div style="background: #f44336; color: white; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php 
                // Si le visiteur actuel est admin, on lui affiche les outils d'administration
                if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): 
                ?>
                    <div class="admin-profile-box" style="background: #2c1a1a; border: 1px solid #ff4444; padding: 15px; margin: 15px 0 15px 0; border-radius: 5px;">             
                        <a href="/admin/dashboard.php" class="btn-admin" style="background: #ff4444; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; display: inline-block;">
                            <i class="fa-solid fa-user-gear"></i> Panel d'administration
                        </a>
                    </div>
                <?php endif; ?>

                <div class="profile-header flex align-center">
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar de <?php echo htmlspecialchars($user['display_name']); ?>" class="profile-avatar">
                    <h2><?php echo htmlspecialchars($user['display_name'] ?? 'Joueur'); ?></h2>
                    <?php if ($date_formatee): ?>
                        <span>inscrit le <?= $date_formatee; ?></span>
                    <?php endif; ?>
                </div>
                <h3>Informations personnelles</h3>
                <p>SteamID : <?php echo $steamid3; ?></p>

                <br>

                <div class="dashboard-box">
                    <h3>Votre pseudo</h3>
                    
                    <?php if (isset($user['name_changed']) && (int)$user['name_changed'] === 1): ?>
                        <!--
                        <div class="alert alert-info">
                            <i class="fa-solid fa-lock"></i> Vous avez déjà personnalisé votre nom d'affichage. Ce changement est définitif et ne peut plus être modifié.
                        </div>
                        -->
                        <p>Pseudo enregistré : <strong><?= htmlspecialchars($user['display_name']) ?></strong></p>
                        
                    <?php else: ?>
                        <p class="info-text"><strong>Attention :</strong> Ce changement est <strong>unique et définitif</strong>. Vous ne pourrez plus le modifier par la suite.</p>
                        
                        <form action="update_profile.php" method="POST" class="flex flex-column gap-10">
                            <div class="form-group">
                                <label for="display_name">Nouveau pseudo :</label>
                                <input 
                                    type="text" 
                                    id="display_name" 
                                    name="display_name" 
                                    value="<?= htmlspecialchars($user['display_name'] ?? $user['name']) ?>" 
                                    maxlength="32" 
                                    required 
                                    class="form-control"
                                >
                            </div>
                            
                            <button type="submit" name="action" value="update_name" class="btn-submit" onclick="return confirm('Êtes-vous sûr ? Ce changement est définitif et unique !');" style="background: #525252; border: 1px solid #333; color: white; padding: 8px; border-radius: 4px;width: 190px;">
                                <i class="fa-solid fa-floppy-disk"></i> Confirmer définitivement
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <h3>Nationalité</h3>
    
                <?php if ($isLocked && !empty($country)): ?>
                    <div class="flex align-center gap-10">
                        <img src="/_img/flags/<?= htmlspecialchars($country) ?>.gif" alt="<?= $countries[$country] ?? $country ?>" class="flag-icon">
                        <span>Nationalité enregistrée : <strong><?= $countries[$country] ?? strtoupper($country) ?></strong></span>
                    </div>
                    <!--
                    <p class="text-muted"><i class="fa-solid fa-lock"></i> Cette option est verrouillée. Contactez un administrateur pour la modifier.</p>-->
                    
                <?php else: ?>
                    <form action="update_country.php" method="POST" class="country-form">
                        <p>Sélectionnez votre nationalité (ce choix sera <strong>définitif</strong>) :</p>
                        
                        <div class="flex align-center gap-10">
                            <select name="country" required class="select-country">
                                <option value="" disabled selected>Choisir un pays...</option>
                                <?php foreach ($countries as $code => $name): ?>
                                    <option value="<?= $code ?>"><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="btn-submit-country">Confirmer</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <br>

            <div class="profile-tabs">
                <button type="button" class="profile-tab-btn active" onclick="switchProfileMode(this, '9v9', '<?= $steamid64 ?>')">Highlander (9v9)</button>
                <button type="button" class="profile-tab-btn" onclick="switchProfileMode(this, '6s', '<?= $steamid64 ?>')">Sixes (6v6)</button>
            </div>

            <div class="player-stats">
                <h3 id="stats-title">Stats - Highlander</h3>

                <div class="box-stats matches-played">
                    <p><b id="stat-total-matches"><?php echo $matches['total_matches'] ?? 0; ?></b> matchs joués</p>
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