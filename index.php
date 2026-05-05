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
    <!-- HTML Meta Tags -->
    <title>Highlander France - Communauté Compétitive de TF2</title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - Communauté Compétitive de TF2">
    <meta property="og:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta property="og:image" content="https://highlanderfrance.tf/_img/hf.webp">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="highlanderfrance.tf">
    <meta property="twitter:url" content="https://highlanderfrance.tf/">
    <meta name="twitter:title" content="Highlander France - Communauté Compétitive de TF2">
    <meta name="twitter:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta name="twitter:image" content="https://highlanderfrance.tf/_img/hf.webp">

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
</head>
<body>

    

    <?php include("_inc/header.php"); ?>

    

    <main id="main">
        <section id="content">
            <div id="questions">
                <ul>
                    <li>Tu joues à Team Fortress 2 et tu parles français ?</li>
                    <li>Tu t'ennuies sur le mode casual et tu as envie de plus de challenge ?</li>
                    <li>Tu te demandes si tu es capable de jouer en compétitif ?</li>
                </ul>
                <p><b>Alors tu es au bon endroit, bienvenue sur Highlander France !</b></p>
            </div>
            
            <div id="about">
                <h3><b>Qui sommes-nous ?</b></h3>
                <p>Créée en Février 2026 à l'initiative de joueurs expérimentés au plus haut niveau et des joueurs membre de l'Équipe de France TF2, la communauté Highlander France a vu le jour avec l'objectif de <b>faire découvrir et de réunir</b> les joueurs et joueuses francophones pratiquants ou intéressés par le mode 9v9 et de leur offrir un lieu unique pour <b>échanger, apprendre, jouer ensemble.</b><br> 
                Nous mettons un point d'honneur à faire de notre communauté un <b>lieu sûr pour tous.</b></p>
                <div class="vid-container">
                    <div class="pres-video">
                        <video autoplay muted loop>
                            <source src="https://i.imgur.com/We4yrzC.mp4" type="video/mp4">
                            Votre navigateur ne supporte pas la lecture de vidéos.
                        </video>
                        <p class="vid-desc">Matchs amicaux streamés en direct sur Twitch!</p>
                    </div>
                </div>
                
                <h3><b>Comment ça fonctionne ?</b></h3>
                <p>Fort de notre expérience <b>nous aidons les débutants et débutantes à appréhender le compétitif</b>, les règles, les tournois, les ligues, les méthodes pour progresser rapidement. Nous organisons régulièrement des matchs de tout niveau pour permettre à tout le monde de développer leur connaissance et mettre en action leur apprentissage ainsi que des demoreview (visionnage de match avec explications) et des maptalks (explications sur comment jouer les maps) et bien plus encore...</p>
            </div>

            <div id="numbers">
                <p>Highlander France, c'est :</p>
                <ul>
                    <li><span><b>+110</b></span> membres actifs</li>
                    <li><b><span id="matchCount"><img class="stat-load" src="_img/loading.gif" alt="Chargement..."></span></b> matchs organisés</li>
                    <li><b><span>+</span><span id="hoursPlayed"><img class="stat-load" src="_img/loading.gif" alt="Chargement..."></span></b> heures de matchs jouées au total</li>
                </ul>
                
            </div>
            <div id="join">
                <p>Alors qu'attends-tu pour nous rejoindre ? <b>Cela ne t'engage en rien</b>, tu es libre de participer ou simplement observer et lorsque tu te sens prêt, tu te lances et nous t'aiderons !</p>
                <a href="https://discord.gg/highlanderfrance" class="join-btn">Rejoindre la communauté !</a>
            </div>
            
        </section>

    </main>

    <?php include("_inc/footer.php"); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
<!--<script src="https://embed.twitch.tv/embed/v1.js"></script>-->
<script>
$.getJSON("_scripts/get_index_stats.php", function(stats) {
    if (stats.data) {
        $("#matchCount").text(stats.data.matches);
        $("#hoursPlayed").text(stats.data.hours);
    } else {
        console.error("Structure JSON inattendue :", stats);
    }
});
    /** 
    new Twitch.Embed("twitch-embed", {
        width: 540,
        height: 304,
        channel: "reconnexionTF",
        autoplay: true,
        muted: true,
        layout: "video",
        // Only needed if this page is going to be embedded on other websites
        // parent: ["embed.example.com", "othersite.example.com"]
      });
      **/
</script>
</body>
</html>