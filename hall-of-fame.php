<?php
require_once "_inc/config.php";
require_once "_inc/functions.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <!-- HTML Meta Tags -->
    <title>Highlander France - Hall of Fame</title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - Hall of Fame">
    <meta property="og:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta property="og:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="highlanderfrance.tf">
    <meta property="twitter:url" content="https://highlanderfrance.tf/">
    <meta name="twitter:title" content="Highlander France - Hall of Fame">
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
    <link rel="stylesheet" href="_css/main.css">

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

    

    <?php include("_inc/header.php"); ?>

    <main id="main">
        <section id="content">
            
            <div class="leaderboard-filter flex space-around align-center">
                <div class="leaderboard-tabs" id="leaderboard-mode-tabs">
                    <button class="tab-btn active" onclick="switchLeaderboard(this, '9v9')">Highlander (9v9)</button>
                    <button class="tab-btn" onclick="switchLeaderboard(this, '6s')">Sixes (6v6)</button>
                </div>

                <div class="search-container">
                    <input type="text" id="player-search-input" placeholder="Rechercher un joueur..." autocomplete="off">
                    <div id="search-results-dropdown" class="search-dropdown" style="display: none;"></div>
                </div>
            </div>

            <div class="leaderboard-filter flex space-around align-center">
                <div class="leaderboard-tabs" id="leaderboard-category-tabs">
                    <button class="tab-btn active" onclick="switchCategory(this, 'matches')">Matchs</button>
                    <button class="tab-btn" onclick="switchCategory(this, 'kills')">Kills</button>
                    <button class="tab-btn" onclick="switchCategory(this, 'heal')">Heal</button>
                    <button class="tab-btn" onclick="switchCategory(this, 'dpm')">DPM</button>
                </div>
            </div>
            
    
            <div class="leaderboard-note">
                Tu ne te vois pas dans le classement ? <a href="login.php">Connecte-toi avec ton compte Steam</a> pour apparaître dans les statistiques de la communauté.
            </div>

            <div class="leaderboard-container">
                <table id="leaderboard-table">
                    <thead id="leaderboard-thead">
                        <tr>
                            <th>Rang</th>
                            <th>Joueur</th>
                            <th>Matchs</th>
                        </tr>
                    </thead>
                    <tbody id="leaderboard-body">
                        </tbody>
                </table>
            </div>

        </section>

    </main>

    <?php include("_inc/footer.php"); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
<script src="_js/main.js"></script>
<script src="_js/leaderboard.js"></script>
<script src="_js/search_players.js"></script>
<script>
    window.addEventListener("load", function () {

    const content = document.querySelector("#content");
    const offset = -115; // ajuste comme tu veux

    if (!content) return;

    // Attendre 1 seconde avant de démarrer l'animation
    setTimeout(() => {

        const target = content.getBoundingClientRect().top + window.scrollY + offset;
        const duration = 1000; // durée de l'animation
        const start = window.scrollY;
        const distance = target - start;
        const startTime = performance.now();

        function easeOutQuad(t) {
            return t * (2 - t);
        }

        function animateScroll(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = easeOutQuad(progress);

            window.scrollTo(0, start + distance * eased);

            if (progress < 1) {
                requestAnimationFrame(animateScroll);
            }
        }

        requestAnimationFrame(animateScroll);

    }, 300);
});

// Lancement au chargement de la page
loadLeaderboard('9v9');
</script>
</body>
</html>