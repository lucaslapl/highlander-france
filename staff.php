<?php
session_start();
require_once "_inc/config.php";
require_once "_inc/functions.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France</title>
    <link rel="stylesheet" href="_css/main.css">
</head>
<body>

    

    <?php include("_inc/header.php"); ?>

    <main id="main">
        <section id="content">
            <h3>L'équipe Highlander France</h3>
            <div id="staff">
                <h3>Fondateurs</h3>
                <hr>
                <p>Les joueurs passionnés à l'initiative de ce projet !</p>
                <div class="staff-role flex space-around align-center wrap">
                    <div class="staff-member">
                        <img src="_img/kaylus.jpg" alt="Kaylus">
                        <h4>Kaylus</h4>
                        <div class="staff-links flex justify-center align-center">
                            <a href="http://steamcommunity.com/profiles/76561198051084840" title="Profil Steam Kaylus" target="_blank">
                                <img class="steam-img" src="_img/steam-icon.svg">
                            </a>
                            <a href="https://etf2l.org/forum/user/58470/" title="Profil ETF2L Kaylus" target="_blank">
                                <img class="etf2l-img" src="_img/etf2l-logo.png">
                            </a>
                        </div>
                    </div>
                    <div class="staff-member">
                        <img src="_img/schmit.jpg" alt="Schmit">
                        <h4>SchmitShot</h4>
                        <div class="staff-links flex justify-center align-center">
                            <a href="https://steamcommunity.com/id/DivinSchmitShot/" title="Profil Steam Schmit" target="_blank">
                                <img class="steam-img" src="_img/steam-icon.svg">
                            </a>
                            <a href="https://etf2l.org/forum/user/107814/" title="Profil ETF2L Schmit" target="_blank">
                                <img class="etf2l-img" src="_img/etf2l-logo.png">
                            </a>
                        </div>
                    </div>
                    <div class="staff-member">
                        <img src="_img/zen.jpg" alt="Zen">
                        <h4>zen</h4>
                        <div class="staff-links flex justify-center align-center">
                            <a href="https://steamcommunity.com/id/azazah/" title="Profil Steam Zen" target="_blank">
                                <img class="steam-img" src="_img/steam-icon.svg">
                            </a>
                            <a href="https://etf2l.org/forum/user/137263/" title="Profil ETF2L Zen" target="_blank">
                                <img class="etf2l-img" src="_img/etf2l-logo.png">
                            </a>
                        </div>
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
                                <img class="staff-pic" src="_img/kaylus.jpg" alt="Kaylus">
                                <h4>Kaylus</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="http://steamcommunity.com/profiles/76561198051084840" title="Profil Steam Kaylus" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/58470/" title="Profil ETF2L Kaylus" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/mathis.jpg" alt="Mathis">
                                <h4>Mathis</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="http://steamcommunity.com/profiles/76561199353050656" title="Profil Steam Mathis" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/147473/" title="Profil ETF2L Mathis" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/nepal.jpg" alt="Nepal">
                                <h4>Nepal</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="http://steamcommunity.com/profiles/76561198239974294" title="Profil Steam Nepal" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/125728/" title="Profil ETF2L Nepal" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/schmit.jpg" alt="SchmitShot">
                                <h4>SchmitShot</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="https://steamcommunity.com/id/DivinSchmitShot/" title="Profil Steam Schmit" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/107814/" title="Profil ETF2L Schmit" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="mixers">
                        <h3>Lanceurs de mix</h3>
                        <hr>
                        <p>Les joueurs qui organisent les mixs pour permettre à tous de jouer en compétitif dans une ambiance conviviale !</p>
                        <div class="staff-role">
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/astrya.jpg" alt="Astrya">
                                <h4>Astrya</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="http://steamcommunity.com/profiles/76561198091242337" title="Profil Steam Astrya" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/136300/" title="Profil ETF2L Astrya" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/kaylus.jpg" alt="Kaylus">
                                <h4>Kaylus</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="http://steamcommunity.com/profiles/76561198051084840" title="Profil Steam Kaylus" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/58470/" title="Profil ETF2L Kaylus" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/schmit.jpg" alt="SchmitShot">
                                <h4>SchmitShot</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="https://steamcommunity.com/id/DivinSchmitShot/" title="Profil Steam Schmit" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/107814/" title="Profil ETF2L Schmit" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/sossok.jpg" alt="Sossok">
                                <h4>Sossok</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="http://steamcommunity.com/profiles/76561198253350195" title="Profil Steam Sossok" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/136916/" title="Profil ETF2L Sossok" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
                            </div>
                            <div class="staff-member flex align-center">
                                <img class="staff-pic" src="_img/zen.jpg" alt="Zen">
                                <h4>zen</h4>
                                <div class="staff-links flex justify-center align-center">
                                    <a href="https://steamcommunity.com/id/azazah/" title="Profil Steam Zen" target="_blank">
                                        <img class="steam-img" src="_img/steam-icon.svg">
                                    </a>
                                    <a href="https://etf2l.org/forum/user/137263/" title="Profil ETF2L Zen" target="_blank">
                                        <img class="etf2l-img" src="_img/etf2l-logo.png">
                                    </a>
                                </div>
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
    const offset = -92; // ajuste comme tu veux

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