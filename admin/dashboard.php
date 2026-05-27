<?php
require_once __DIR__ . "/../_inc/config.php";
require_once __DIR__ . "/../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : Si le visiteur n'est pas admin, le script meurt ici immédiatement.
checkAdminOrDie();

try {
    // Quelques statistiques rapides pour donner de la vie au dashboard admin
    $tech_team = getTechnicalTeam($db);
    $totalPlayers = $db->query("SELECT COUNT(*) FROM players_info")->fetchColumn();
    $totalStaff = $db->query("SELECT COUNT(*) FROM players_info WHERE is_admin = 1 OR is_founder = 1 OR is_moderator = 1 OR is_mentor = 1 OR is_mixer = 1")->fetchColumn();
    $totalRegistered = $db->query("SELECT COUNT(*) FROM players_info WHERE created_at IS NOT NULL")->fetchColumn();

    // Récupération des 5 derniers inscrits sur le site
    $stmtRecent = $db->query("SELECT steamid, name, display_name, created_at FROM players_info ORDER BY created_at DESC LIMIT 5");
    $recentUsers = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $totalPlayers = 0;
    $totalStaff = 0;
    $recentUsers = [];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Highlander France - Panel Admin</title>
    <link rel="stylesheet" href="../_css/main.css">
    <link rel="stylesheet" href="_css/admin.css">
</head>

<body>

    <?php include("../_inc/header.php"); ?>

    <main id="main" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">

        <div class="admin-header" style="border-bottom: 2px solid #ff4444; padding-bottom: 15px; margin-bottom: 30px;">
            <h2 style="color: #ff4444; margin: 0;"><i class="fa-solid fa-screwdriver-wrench"></i> Panel d'Administration</h2>
            <p style="margin: 5px 0 0 0; color: #aaa;">Bienvenue dans l'espace de gestion de la communauté Highlander France.</p>
        </div>

        <div class="admin-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div style="background: #1e1e24; border-left: 4px solid #ff4444; padding: 20px; border-radius: 4px;">
                <span style="color: #aaa; font-size: 14px; text-transform: uppercase;">Nombre de joueurs dans la base de données</span>
                <h3 style="margin: 10px 0 0 0; font-size: 28px;"><?= $totalPlayers ?></h3>
            </div>
            <div style="background: #1e1e24; border-left: 4px solid #3498db; padding: 20px; border-radius: 4px;">
                <span style="color: #aaa; font-size: 14px; text-transform: uppercase;">Joueurs enregistrés (web)</span>
                <h3 style="margin: 10px 0 0 0; font-size: 28px;"><?= $totalRegistered ?></h3>
            </div>
            <div style="background: #1e1e24; border-left: 4px solid #00bc8c; padding: 20px; border-radius: 4px;">
                <span style="color: #aaa; font-size: 14px; text-transform: uppercase;">Membres du staff</span>
                <h3 style="margin: 10px 0 0 0; font-size: 28px;"><?= $totalStaff ?></h3>
            </div>
        </div>

        <div class="admin-content-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start;">

            <section class="admin-manipulations">
                <h3 style="margin-top: 0; color: #fff; border-bottom: 1px solid #333; padding-bottom: 10px;">Actions disponibles</h3>

                <div class="admin-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">

                    <div style="background: #1a1a1a; border: 1px solid #333; padding: 20px; border-radius: 6px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 style="margin: 0 0 10px 0; color: #ff4444;"><i class="fa-solid fa-users-gear"></i> Modération des joueurs</h4>
                            <p style="font-size: 14px; color: #ccc; margin: 0 0 15px 0;">Rechercher un profil, attribuer/retirer des rôles, changer le pseudo ou nationalité d'un joueur (ou réinitialiser la restriction associée).</p>
                        </div>
                        <div class="search-container">
                            <input type="text" id="player-search-input" placeholder="Rechercher un joueur..." autocomplete="off">
                            <div id="search-results-dropdown" class="search-dropdown" style="display: none;"></div>
                        </div>
                    </div>

                    <div style="background: #1a1a1a; border: 1px solid #333; padding: 20px; border-radius: 6px;">
                        <h4 style="margin: 0 0 10px 0; color: #00bc8c;"><i class="fa-solid fa-user-shield"></i> L'équipe complète</h4>
                        <p style="font-size: 14px; color: #ccc; margin: 0 0 20px 0;">Liste complète des utilisateurs possédant un rôle staff pour vérifier les permissions globales.</p>
                        <a href="list_staff.php" style="background: #00bc8c; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; display: inline-block; font-size: 14px;">Voir l'équipe</a>
                    </div>

                    <div style="background: #1a1a1a; border: 1px solid #333; padding: 20px; border-radius: 6px;">
                        <h4 style="margin: 0 0 10px 0; color: #f39c12;"><i class="fa-solid fa-rotate"></i> Tâches CRON</h4>
                        <p style="font-size: 14px; color: #ccc; margin: 0 0 20px 0;">NE PAS UTILISER SAUF URGENCE OU SANS Y AVOIR ÉTÉ INVITÉ</p>
                        <a href="run_cron_manual.php" style="background: #f39c12; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; display: inline-block; font-size: 14px;">Panel CRON</a>
                    </div>

                    <div style="background: #1a1a1a; border: 1px solid #333; padding: 20px; border-radius: 6px;">
                        <h4 style="margin: 0 0 10px 0; color: #3498db;"><i class="fa-solid fa-database"></i> Logs du site</h4>
                        <p style="font-size: 14px; color: #ccc; margin: 0 0 20px 0;">(Indisponible pour le moment)</p>
                        <a href="view_logs.php" style="background: #3498db; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; display: inline-block; font-size: 14px;">Ouvrir l'inspecteur log</a>
                    </div>

                </div>
                <div class="admin-card" style="background: #1e1e24; border: 1px solid #2d2d35; border-radius: 6px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0; color: #e74c3c; display: flex; align-items: center; gap: 10px; font-size: 18px; border-bottom: 1px solid #2d2d35; padding-bottom: 10px;">
                        <i class="fa-solid fa-code font-awesome-icon"></i> Équipe Technique
                        <span style="background: #e74c3c; color: #fff; font-size: 12px; padding: 2px 8px; border-radius: 10px; margin-left: auto;">
                            <?= count($tech_team ?? []) ?>
                        </span>
                    </h3>

                    <?php if (empty($tech_team)): ?>
                        <p style="color: #aaa; font-style: italic; font-size: 14px; margin: 0;">Aucun administrateur configuré (Bizarre !).</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 10px; max-height: 300px; overflow-y: auto; padding-right: 5px;">
                            <p style="font-size: 14px; color: #ccc; margin: 0;">Utilisateurs ayant accès à ce panel.</p>
                            <?php foreach ($tech_team as $admin): ?>
                                <?php
                                // Conversion inverse si nécessaire pour le lien de gestion (on repasse souvent en SteamID64)
                                // Si tes fonctions attendent déjà le SteamID3, tu passeras juste $admin['steamid']
                                $steamid64_link = steamID3ToSteamID64($admin['steamid']);
                                ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #16161a; padding: 10px 12px; border-radius: 4px; border-left: 3px solid #e74c3c;">

                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if (!empty($admin['country'])): ?>
                                            <img src="/_img/flags/<?= htmlspecialchars($admin['country']) ?>.gif"
                                                alt="<?= strtoupper($admin['country']) ?>"
                                                style="width: 16px; height: 11px; border-radius: 2px;">
                                        <?php else: ?>
                                            <img src="/_img/flags/unknown.gif" style="width: 16px; height: 11px;">
                                        <?php endif; ?>

                                        <strong style="color: #fff; font-size: 14px;">
                                            <?= htmlspecialchars($admin['display_name']) ?>
                                        </strong>
                                    </div>

                                    <a href="manage_player.php?steamid=<?= urlencode($steamid64_link) ?>"
                                        style="background: #2d2d35; color: #ccc; text-decoration: none; font-size: 12px; padding: 5px 10px; border-radius: 4px; transition: background 0.2s;"
                                        onmouseover="this.style.background='#3e3e48'; this.style.color='#fff';"
                                        onmouseout="this.style.background='#2d2d35'; this.style.color='#ccc';">
                                        <i class="fa-solid fa-user-gear"></i> Gérer
                                    </a>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="admin-sidebar" style="background: #141419; border: 1px solid #222; padding: 20px; border-radius: 6px;">
                <h3 style="margin-top: 0; font-size: 16px; color: #aaa; border-bottom: 1px solid #222; padding-bottom: 10px;">Dernières inscriptions</h3>

                <?php if (empty($recentUsers)): ?>
                    <p style="color: #666; font-size: 14px; margin: 10px 0 0 0;">Aucun utilisateur trouvé.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0; margin: 10px 0 0 0; display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($recentUsers as $user): ?>
                            <?php
                            $name = !empty($user['display_name']) ? $user['display_name'] : $user['name'];
                            $steam64 = steamID3ToSteamID64($user['steamid']);
                            $date = date("d/m à H:i", strtotime($user['created_at']));
                            ?>
                            <li style="font-size: 14px; display: flex; justify-content: space-between; align-items: center; background: #1e1e24; padding: 8px 12px; border-radius: 4px;">
                                <div style="display: flex; flex-direction: column;">
                                    <a href="/profile/profil?steamid=<?= $steam64 ?>" target="_blank" style="color: #fff; text-decoration: none; font-weight: bold; font-size: 13px;">
                                        <?= htmlspecialchars($name) ?>
                                    </a>
                                    <span style="color: #666; font-size: 11px;"><?= $date ?></span>
                                </div>
                                <a href="manage_player.php?steamid=<?= $steam64 ?>" style="color: #ff4444; font-size: 12px; text-decoration: none;" title="Gérer cet utilisateur">
                                    <i class="fa-solid fa-user-pen"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </aside>

        </div>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
    <script src="../_js/main.js"></script>
    <script src="_scripts/admin_player_search.js"></script>
</body>

</html>