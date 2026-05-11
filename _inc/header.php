        <header id="header" fetchpriority="high">
            <div class="head-content flex space-between align-center">
                <div class="flex justify-center align-center">
                    <a href="https://highlanderfrance.tf">
                        <img class="header-logo" src="/_img/hf.webp" alt="Logo Highlander France" aria-label="Redirection vers la page d'accueil">
                    </a>
                    <h1>
                        Highlander France
                    </h1>
                </div>
            </div>
            <!-- Twitch Embed -->
            <!--<div class="embed">
                <div id="twitch-embed"></div>
            </div>-->

            <nav id="nav">
                <div class="nav-content flex space-between align-center">
                    <?php 
                    $page_actuelle = basename($_SERVER['PHP_SELF']); 
                    ?>

                    <ul class="flex justify-center align-center">
                        <li><a href="/index" class="<?= ($page_actuelle == 'index.php') ? 'active' : '' ?>">Accueil</a></li>
                        <li><a href="/staff" class="<?= ($page_actuelle == 'staff.php') ? 'active' : '' ?>">L'équipe</a></li>
                        <li><a href="/hall-of-fame" class="<?= ($page_actuelle == 'hall-of-fame.php') ? 'active' : '' ?>">Hall of Fame</a></li>
                        <li><a href="/match-logs" class="<?= ($page_actuelle == 'match-logs.php') ? 'active' : '' ?>">Match Stats</a></li>
                    </ul>
                    <div class="nav-right flex justify-center align-center">
                        <div id="session-profile" class="flex justify-center align-center">
                            <?php if (isset($_SESSION['steamid'])): ?>
                                <a href="/profile/dashboard">Mon Profil</a>
                                <a href="/logout">Déconnexion</a>
                            <?php else: ?>
                                <a href="/login">
                                    <img class="steamlogin" src="/_img/sits_01.png" alt="Connexion via Steam" aria-label="Se connecter via Steam">
                                </a>
                            <?php endif; ?>
                        </div>
                        <a class="nav-discord discord-link" href="https://discord.gg/BMuj3cqUFt">
                            <i class="fa-brands fa-discord"></i> Discord
                        </a>
                    </div>
                </div>
            </nav>
        </header>