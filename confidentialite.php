<?php
require_once "_inc/config.php";
require_once "_inc/functions.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France - Politique de Confidentialité</title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - Politique de Confidentialité">
    <meta property="og:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta property="og:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="highlanderfrance.tf">
    <meta property="twitter:url" content="https://highlanderfrance.tf/">
    <meta name="twitter:title" content="Highlander France - Politique de Confidentialité">
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

    <?php include("_inc/header.php"); ?>

    <div id="main">
        <section id="content">
            <h2>Politique de Confidentialité</h2>
            <h3>1. Introduction</h3>
            <p>Chez Highlander France, nous nous engageons à protéger votre vie privée. Cette politique de confidentialité explique comment nous collectons, utilisons et protégeons vos informations personnelles lorsque vous utilisez notre site web.</p>
            <h3>2. Collecte d'Informations</h3>
            <p>Notre site utilise l'API Steam OpenID pour vous permettre de vous connecter avec votre compte Steam.</p>
            <ul>
                <li><b>Ce que nous collectons :</b> Lorsque vous vous connectez via Steam, nous recueillons votre SteamID64 (identifiant unique public), votre nom d'utilisateur Steam et votre avatar Steam. Nous ne collectons aucune autre information personnelle.</li>
                <li><b>Ce que nous ne collectons pas :</b> Nous ne collectons pas votre adresse e-mail, votre mot de passe Steam ou toute autre information personnelle sensible. Nous n'avons aucun accès à vos données de jeu ou à votre historique de jeu sur Steam.</li>
            </ul>
            <h3>3. Utilisation de vos données</h3>
            <p>Les données que nous collectons sont utilisées uniquement pour :</p>
            <ul>
                <li>Affichage de votre profil sur le site.</li>
                <li>Classement des joueurs sur le leaderboard (Hall of Fame).</li>
                <li>Statistiques de jeu liées à votre compte grâce aux données de l'API logs.tf.</li>
            </ul>
            <h3>4. Cookies (Google Analytics)</h3>
            <p>Nous utilisons Google Analytics pour collecter des données anonymes sur la manière dont les visiteurs utilisent notre site. Google Analytics utilise des cookies pour suivre les interactions des utilisateurs avec le site. Ces données nous aident à améliorer l'expérience utilisateur et à comprendre les tendances de trafic. Vous pouvez désactiver les cookies de Google Analytics en installant le module complémentaire de navigateur pour la désactivation de Google Analytics.</p>
            <h3>5. Stockage et Sécurité</h3>
            <p>Nous prenons la sécurité de vos données au sérieux. Les informations que nous collectons sont stockées sur des serveurs sécurisés et ne sont accessibles qu'à un nombre limité de personnes ayant des droits d'accès spéciaux à ces systèmes. Nous ne partageons pas vos données avec des tiers, sauf si cela est nécessaire pour se conformer à la loi ou pour protéger nos droits.</p>
            <h3>6. Vos Droits</h3>
            <p>Conformément au RGPD, vous avez le droit de demander l'accès à vos données personnelles, de les corriger, de les supprimer ou de limiter leur traitement. Si vous souhaitez exercer ces droits, veuillez nous contacter à l'adresse e-mail suivante : <a style="color: #007bff; text-decoration: underline;" href="mailto:contact@reconnexion.tf">contact@reconnexion.tf</a></p>
        </section>
    </div>

    <?php include("_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
    <script src="../_js/main.js"></script>
</body>
</html>