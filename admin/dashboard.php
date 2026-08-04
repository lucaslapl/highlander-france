<?php
require_once __DIR__ . "/../_inc/config.php";
require_once __DIR__ . "/../_inc/functions.php";
require_once __DIR__ . "/../_inc/api_status.php";

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
    // ---- Données pour les graphiques du dashboard ----
    // Inscriptions : comptage par jour (12 derniers mois)
    $stmtReg = $db->query("SELECT date(created_at) AS d, COUNT(*) AS nb
        FROM players_info
        WHERE created_at IS NOT NULL AND created_at >= date('now', '-12 months')
        GROUP BY date(created_at)
        ORDER BY d ASC");
    $registrations = $stmtReg->fetchAll(PDO::FETCH_ASSOC);

    // Matchs joués : comptage par jour (les logs blacklistés sont déjà purgés de player_matches)
    $stmtMatches = $db->prepare("SELECT date(ld.date, 'unixepoch') AS d, COUNT(DISTINCT pm.match_id) AS nb
        FROM player_matches pm
        JOIN log_dates ld ON ld.log_id = pm.match_id
        WHERE ld.date IS NOT NULL AND ld.date >= ?
        GROUP BY date(ld.date, 'unixepoch')
        ORDER BY d ASC");
    $stmtMatches->execute([strtotime('-12 months')]);
    $matchesPerDay = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

    // Modes de jeu : nombre de matchs distincts par mode
    $modes = [];
    foreach ($db->query("SELECT game_mode, COUNT(DISTINCT match_id) AS nb FROM player_matches GROUP BY game_mode")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $modes[$row['game_mode']] = (int)$row['nb'];
    }
    $recentUsers = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
    $apiStatuses = getApiStatuses(isset($_GET['refresh_apis']));
} catch (PDOException $e) {
    $totalPlayers = 0;
    $totalStaff = 0;
    $registrations = [];
    $matchesPerDay = [];
    $modes = [];
    $recentUsers = [];
    $apiStatuses = [];
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

    <main id="main" class="admin-main">

        <div class="admin-header" style="--accent: #ff4444;">
            <h2><i class="fa-solid fa-screwdriver-wrench"></i> Panel d'Administration</h2>
            <p>Bienvenue dans l'espace de gestion de la communauté Highlander France.</p>
        </div>

        <div class="admin-stats-grid">
            <div class="admin-stat-card" style="--accent: #ff4444;">
                <span>Nombre de joueurs dans la base de données</span>
                <h3><?= $totalPlayers ?></h3>
            </div>
            <div class="admin-stat-card" style="--accent: #3498db;">
                <span>Joueurs enregistrés (web)</span>
                <h3><?= $totalRegistered ?></h3>
            </div>
            <div class="admin-stat-card" style="--accent: #00bc8c;">
                <span>Membres du staff</span>
                <h3><?= $totalStaff ?></h3>
            </div>
        </div>

        <div class="dashboard-charts">
            <h3 class="admin-section-title">
                <i class="fa-solid fa-chart-line"></i> Statistiques
            </h3>

            <div class="charts-grid">

                <div class="chart-card">
                    <div class="chart-card__header">
                        <h4 class="chart-card__title">
                            <i class="fa-solid fa-user-plus"></i> Inscriptions
                        </h4>
                        <div class="chart-toggles" data-target="registrations">
                            <button type="button" class="chart-toggle" data-period="week">Semaine</button>
                            <button type="button" class="chart-toggle active" data-period="month">Mois</button>
                        </div>
                    </div>
                    <div class="chart-card__body">
                        <canvas id="chart-registrations"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-card__header">
                        <h4 class="chart-card__title">
                            <i class="fa-solid fa-clock-rotate-left"></i> Matchs joués
                        </h4>
                        <div class="chart-toggles" data-target="matches">
                            <button type="button" class="chart-toggle" data-period="day">Jour</button>
                            <button type="button" class="chart-toggle active" data-period="week">Semaine</button>
                            <button type="button" class="chart-toggle" data-period="month">Mois</button>
                        </div>
                    </div>
                    <div class="chart-card__body">
                        <canvas id="chart-matches"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-card__header">
                        <h4 class="chart-card__title">
                            <i class="fa-solid fa-scale-balanced"></i> Répartition 6s / 9v9
                        </h4>
                    </div>
                    <div class="chart-card__body chart-card__body--tall">
                        <canvas id="chart-modes"></canvas>
                    </div>
                </div>

            </div>
        </div>

        <div class="admin-content-layout">

            <section class="admin-manipulations">
                <h3 class="admin-section-title">Actions disponibles</h3>

                <div class="admin-cards-grid">

                    <div class="admin-action-card" style="--accent: #ff4444;">
                        <div>
                            <h4 class="admin-action-card__title"><i class="fa-solid fa-users-gear"></i> Modération des joueurs</h4>
                            <p class="admin-action-card__desc">Rechercher un profil, attribuer/retirer des rôles, changer le pseudo ou nationalité d'un joueur (ou réinitialiser la restriction associée).</p>
                        </div>
                        <div class="search-container">
                            <input type="text" id="player-search-input" placeholder="Rechercher un joueur..." autocomplete="off">
                            <div id="search-results-dropdown" class="search-dropdown" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="admin-action-card" style="--accent: #00bc8c;">
                        <h4 class="admin-action-card__title"><i class="fa-solid fa-user-shield"></i> L'équipe complète</h4>
                        <p class="admin-action-card__desc">Liste complète des utilisateurs possédant un rôle staff pour vérifier les permissions globales.</p>
                        <a href="list_staff.php" class="admin-link-btn">Voir l'équipe</a>
                    </div>

                    <div class="admin-action-card" style="--accent: #f39c12;">
                        <h4 class="admin-action-card__title"><i class="fa-solid fa-rotate"></i> Tâches CRON</h4>
                        <p class="admin-action-card__desc">NE PAS UTILISER SAUF URGENCE OU SANS Y AVOIR ÉTÉ INVITÉ</p>
                        <a href="run_cron_manual.php" class="admin-link-btn">Panel CRON</a>
                    </div>

                    <div class="admin-action-card" style="--accent: #3498db;">
                        <h4 class="admin-action-card__title"><i class="fa-solid fa-database"></i> Logs du site</h4>
                        <p class="admin-action-card__desc">(Indisponible pour le moment)</p>
                        <a href="view_logs.php" class="admin-link-btn">Ouvrir l'inspecteur log</a>
                    </div>

                    <div class="admin-action-card" style="--accent: #f39c12;">
                        <h4 class="admin-action-card__title"><i class="fa-solid fa-clock-rotate-left"></i> Logs des matchs joués</h4>
                        <p class="admin-action-card__desc">Liste des matchs joués avec nombre de joueurs et durée, avec alertes orange (match court, effectif incomplet).</p>
                        <a href="match_logs.php" class="admin-link-btn">Voir les logs</a>
                    </div>

                    <div class="admin-action-card" style="--accent: #f35f5f;">
                        <h4 class="admin-action-card__title"><i class="fa-solid fa-ban"></i> Logs blacklistés</h4>
                        <p class="admin-action-card__desc">Exclure des logs logs.tf des stats et de la page Match Stats, avec motif et traçabilité.</p>
                        <a href="manage_blacklist.php" class="admin-link-btn">Gérer la blacklist</a>
                    </div>
                </div>



                <div class="admin-api-status">
                    <div class="api-status-header">
                        <h3>
                            <i class="fa-solid fa-tower-broadcast"></i> Statut des API
                        </h3>
                        <a href="dashboard?refresh_apis=1" class="admin-btn">
                            <i class="fa-solid fa-rotate"></i> Vérifier maintenant
                        </a>
                    </div>

                    <?php if (empty($apiStatuses)): ?>
                        <p style="color: #666; font-size: 14px; margin: 0;">Impossible de récupérer le statut des API.</p>
                    <?php else: ?>
                        <?php
                        $statusColors = ['ok' => '#00bc8c', 'slow' => '#f39c12', 'down' => '#ff4444', 'error' => '#ff4444'];
                        $statusLabels = ['ok' => 'Opérationnel', 'slow' => 'Lent', 'down' => 'Indisponible', 'error' => 'Erreur'];
                        ?>
                        <div class="api-status-grid">
                            <?php foreach ($apiStatuses as $api): ?>
                                <?php
                                $color = $statusColors[$api['status']] ?? '#ff4444';
                                $label = $statusLabels[$api['status']] ?? 'Inconnu';
                                ?>
                                <div class="api-status-card" style="--accent: <?= $color ?>;">
                                    <div class="api-status-card__header">
                                        <strong class="api-status-card__name">
                                            <i class="<?= htmlspecialchars($api['icon']) ?>"></i>
                                            <?= htmlspecialchars($api['api']) ?>
                                        </strong>
                                        <span class="status-pill"><?= $label ?></span>
                                    </div>

                                    <div class="api-status-card__meta">
                                        <span><i class="fa-solid fa-gauge-high" style="width: 18px;"></i> Latence : <strong><?= $api['latency_ms'] !== null ? $api['latency_ms'] . ' ms' : '—' ?></strong></span>
                                        <span><i class="fa-solid fa-code" style="width: 18px;"></i> HTTP : <strong><?= $api['http_code'] ?: '—' ?></strong></span>
                                        <span style="color: <?= $api['status'] === 'ok' ? '#aaa' : $color ?>;"><?= htmlspecialchars($api['message']) ?></span>

                                        <?php if (!empty($api['last_sync'])): ?>
                                            <?php
                                            $ls  = $api['last_sync'];
                                            $ago = '';
                                            $ts  = (int)($ls['ts'] ?? 0);
                                            if ($ts > 0) {
                                                $diff = time() - $ts;
                                                if ($diff < 60) {
                                                    $ago = 'à l\'instant';
                                                } elseif ($diff < 3600) {
                                                    $ago = 'il y a ' . floor($diff / 60) . ' min';
                                                } elseif ($diff < 86400) {
                                                    $ago = 'il y a ' . floor($diff / 3600) . ' h';
                                                } else {
                                                    $ago = 'il y a ' . floor($diff / 86400) . ' j';
                                                }
                                            }
                                            ?>
                                            <span class="api-divider" style="color: <?= $ls['status'] === 'success' ? '#00bc8c' : '#ff4444' ?>;">
                                                <i class="fa-solid <?= $ls['status'] === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                                Dernière synchro : <?= htmlspecialchars($ls['message']) ?><?= $ago !== '' ? ' · ' . $ago : '' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="api-divider" style="color: #666;">
                                                <i class="fa-solid fa-circle-question"></i> Aucune exécution de script enregistrée
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="admin-sidebar">
                <h3 class="admin-sidebar__title">Dernières inscriptions</h3>

                <?php if (empty($recentUsers)): ?>
                    <p class="admin-sidebar__empty">Aucun utilisateur trouvé.</p>
                <?php else: ?>
                    <ul class="admin-sidebar__list">
                        <?php foreach ($recentUsers as $user): ?>
                            <?php
                            $name = !empty($user['display_name']) ? $user['display_name'] : $user['name'];
                            $steam64 = steamID3ToSteamID64($user['steamid']);
                            $date = date("d/m à H:i", strtotime($user['created_at']));
                            ?>
                            <li class="admin-sidebar__item">
                                <div style="display: flex; flex-direction: column;">
                                    <a href="/profile/profil?steamid=<?= $steam64 ?>" target="_blank" class="admin-sidebar__item-link">
                                        <?= htmlspecialchars($name) ?>
                                    </a>
                                    <span class="admin-sidebar__item-meta"><?= $date ?></span>
                                </div>
                                <a href="manage_player.php?steamid=<?= $steam64 ?>" class="admin-sidebar__manage" title="Gérer cet utilisateur">
                                    <i class="fa-solid fa-user-pen"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="admin-card admin-card--alt">
                    <h3 class="admin-sidebar__title" style="--accent: #e74c3c;">
                        <i class="fa-solid fa-code font-awesome-icon"></i> Équipe Technique
                        <span class="status-pill">
                            <?= count($tech_team ?? []) ?>
                        </span>
                    </h3>

                    <?php if (empty($tech_team)): ?>
                        <p class="admin-sidebar__hint" style="color: #aaa; font-style: italic;">Aucun administrateur configuré (Bizarre !).</p>
                    <?php else: ?>
                        <div class="admin-sidebar__stack">
                            <p class="admin-sidebar__hint">Utilisateurs ayant accès à ce panel.</p>
                            <?php foreach ($tech_team as $admin): ?>
                                <?php
                                // Conversion inverse si nécessaire pour le lien de gestion (on repasse souvent en SteamID64)
                                // Si tes fonctions attendent déjà le SteamID3, tu passeras juste $admin['steamid']
                                $steamid64_link = steamID3ToSteamID64($admin['steamid']);
                                ?>
                                <div class="tech-member">

                                    <div class="tech-member__identity">
                                        <?php if (!empty($admin['country'])): ?>
                                            <img src="/_img/flags/<?= htmlspecialchars($admin['country']) ?>.gif"
                                                alt="<?= strtoupper($admin['country']) ?>"
                                                class="tech-member__flag">
                                        <?php else: ?>
                                            <img src="/_img/flags/unknown.gif" class="tech-member__flag">
                                        <?php endif; ?>

                                        <strong class="tech-member__name">
                                            <?= htmlspecialchars($admin['display_name']) ?>
                                        </strong>
                                    </div>

                                    <a href="manage_player.php?steamid=<?= urlencode($steamid64_link) ?>" class="admin-btn">
                                        <i class="fa-solid fa-user-gear"></i> Gérer
                                    </a>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>

        </div>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script>
        window.__dashboardData = {
            registrations: <?= json_encode($registrations) ?>,
            matchesPerDay: <?= json_encode($matchesPerDay) ?>,
            modes: <?= json_encode($modes) ?>
        };
    </script>
    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="../_js/main.js"></script>
    <script src="_scripts/admin_player_search.js"></script>
    <script src="_scripts/admin_charts.js"></script>
</body>

</html>