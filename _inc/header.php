        <header id="header">
            <div class="head-content flex space-between align-center">
                <div class="flex justify-center align-center">
                    <a href="https://highlanderfrance.tf">
                        <img class="header-logo" src="_img/hf.webp" alt="Highlander France">
                    </a>
                    <h1>
                        Highlander France
                    </h1>
                </div>
                <div id="session-profile" class="flex justify-center align-center">
                    <?php if (isset($_SESSION['steamid'])): ?>
                        <a href="profile/dashboard.php">Mon Profil</a>
                        <a href="logout.php">Déconnexion</a>
                    <?php else: ?>
                        <a href="login.php">
                            <img class="steamlogin" src="_img/sits_01.png" alt="Connexion via Steam">
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Twitch Embed -->
            <!--<div class="embed">
                <div id="twitch-embed"></div>
            </div>-->

            <nav id="nav">
                <div class="nav-content flex space-between align-center">
                    <?php 
                    // On récupère le nom du fichier actuel
                    $page_actuelle = basename($_SERVER['PHP_SELF']); 
                    ?>

                    <ul class="flex justify-center align-center">
                        <li><a href="index.php" class="<?= ($page_actuelle == 'index.php') ? 'active' : '' ?>">Accueil</a></li>
                        <li><a href="staff.php" class="<?= ($page_actuelle == 'staff.php') ? 'active' : '' ?>">L'équipe</a></li>
                        <li><a href="hall-of-fame.php" class="<?= ($page_actuelle == 'hall-of-fame.php') ? 'active' : '' ?>">Hall of Fame</a></li>
                        <li><a href="match-logs.php" class="<?= ($page_actuelle == 'match-logs.php') ? 'active' : '' ?>">Match Stats</a></li>
                    </ul>
                    <a class="nav-discord discord-link" href="https://discord.gg/highlanderfrance">
                        <i class="fa-brands fa-discord"></i> Discord
                    </a>
                </div>
            </nav>
        </header>