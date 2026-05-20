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
    <title>Highlander France - L'équipe</title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - L'équipe">
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
            <h2>L'équipe Highlander France</h2>
            <div id="staff">
                <h3>Fondateurs</h3>
                <hr>
                <p>Les joueurs passionnés à l'initiative de ce projet !</p>
                <div class="staff-role flex space-around align-center wrap">
                    <div class="staff-member">
                        <img src="_img/kaylus.jpg" alt="Avatar de Kaylus">
                        <a href="/profile/profil?steamid=76561198051084840">
                            <h4>Kaylus</h4>
                        </a>
                    </div>
                    <div class="staff-member">
                        <img src="_img/schmit.jpg" alt="Avatar de SchmitShot">
                        <a href="/profile/profil?steamid=76561197974486633">
                            <h4>SchmitShot</h4>
                        </a>
                    </div>
                    <div class="staff-member">
                        <img src="_img/zen.jpg" alt="Avatar de zen">
                        <a href="/profile/profil?steamid=76561198158964214">
                            <h4>zen</h4>
                        </a>
                    </div>
                </div>
                <!--
                <h3>Modération</h3>
                <div class="staff-role flex space-around align-center wrap">
                    (prévisionnel)
                </div>
                -->
                <div id="sous-staff" class="flex space-around">
                    <div id="mentors">
                        <h3>Mentors</h3>
                        <hr>
                        <p>Les joueurs expérimentés qui accompagnent les nouveaux venus dans leur progression en compétitif !</p>
                        <div class="staff-role">
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/kaylus.jpg" alt="Avatar de Kaylus">
                                <a href="/profile/profil?steamid=76561198051084840">
                                    <h4>Kaylus</h4>
                                </a>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/mathis.jpg" alt="Avatar de Mathis">
                                <a href="/profile/profil?steamid=76561199353050656">
                                    <h4>Mathis</h4>
                                </a>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/nepal.jpg" alt="Avatar de Nepal">
                                <a href="/profile/profil?steamid=76561198239974294">
                                    <h4>Nepal</h4>
                                </a>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/schmit.jpg" alt="Avatar de SchmitShot">
                                <a href="/profile/profil?steamid=76561197974486633">
                                    <h4>SchmitShot</h4>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div id="mixers">
                        <h3>Lanceurs de mix</h3>
                        <hr>
                        <p>Les joueurs qui organisent les mixs pour permettre à tous de jouer en compétitif dans une ambiance conviviale !</p>
                        <div class="staff-role">
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/astrya.jpg" alt="Avatar de Astrya">
                                <a href="/profile/profil?steamid=76561198091242337">
                                    <h4>Astrya</h4>
                                </a>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/ca$h.jpg" alt="Avatar de Ca$h">
                                <a href="/profile/profil?steamid=76561199236525199">
                                    <h4>Ca$h</h4>
                                </a>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/kaylus.jpg" alt="Avatar de Kaylus">
                                <a href="/profile/profil?steamid=76561198051084840">
                                    <h4>Kaylus</h4>
                                </a>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/schmit.jpg" alt="Avatar de SchmitShot">
                                <a href="/profile/profil?steamid=76561197974486633">
                                    <h4>SchmitShot</h4>
                                </a>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/sossok.jpg" alt="Avatar de Sossok">
                                <a href="/profile/profil?steamid=76561198253350195">
                                    <h4>Sossok</h4>
                                </a>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/zen.jpg" alt="Avatar de zen">
                                <a href="/profile/profil?steamid=76561198158964214">
                                    <h4>zen</h4>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php include("_inc/footer.php"); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
<script src="_js/main.js"></script>
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

</script>
</body>
</html>