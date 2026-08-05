<?php
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

$isAdmin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);

// 1. On récupère l'ID du log passé dans l'URL
$logId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($logId <= 0) {
    header("Location: /errors/400.php");
    exit();
}

// 2. Le log est-il blacklisté ? (les stats ont été purgées de la BDD)
if (in_array($logId, getBlacklistedLogIds($db))) {
    header("Location: /errors/404.php");
    exit();
}

// 3. Récupération des joueurs du match (jointure avec players_info pour pseudo/avatar)
$stmt = $db->prepare("
    SELECT pm.steamid, pm.map_name, pm.game_mode, pm.class_played, pm.team,
           pm.dmg, pm.kills, pm.deaths, pm.assists,
           pm.suicides, pm.heal, pm.medkits, pm.ubers, pm.drops, pm.backstabs,
           pm.headshots, pm.longest_killstreak,
           pi.name, pi.display_name, pi.avatar
    FROM player_matches pm
    LEFT JOIN players_info pi ON pi.steamid = pm.steamid
    WHERE pm.match_id = ?
    ORDER BY pm.dmg DESC
");
$stmt->execute([$logId]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($players)) {
    header("Location: /errors/404.php");
    exit();
}

// 4. Métadonnées du match
$first   = $players[0];
$mapName = $first['map_name'] ?? '';
$gameMode = strtoupper($first['game_mode'] ?? '9v9');
$gameModeLabel = ($gameMode === '6S') ? 'Sixes (6v6)' : 'Highlander (9v9)';

$stmtDate = $db->prepare("SELECT date FROM log_dates WHERE log_id = ?");
$stmtDate->execute([$logId]);
$matchDate = $stmtDate->fetchColumn();
$matchDate = $matchDate ? date('d/m/Y à H:i', (int)$matchDate) : null;

$length = 0;
$stmtLen = $db->prepare("SELECT length FROM matches_cache WHERE match_id = ?");
$stmtLen->execute([$logId]);
$length = (int)$stmtLen->fetchColumn();
if ($length <= 0) {
    $stmtLen2 = $db->prepare("SELECT length FROM log_length_cache WHERE log_id = ?");
    $stmtLen2->execute([$logId]);
    $length = (int)$stmtLen2->fetchColumn();
}

// 5. Équipes (RED / BLU) et scores
$redPlayers = [];
$bluePlayers = [];
$otherPlayers = [];
$hasTeamData = false;

foreach ($players as $p) {
    $team = $p['team'] ?? null;
    if ($team === 'red') {
        $redPlayers[] = $p;
        $hasTeamData = true;
    } elseif ($team === 'blue') {
        $bluePlayers[] = $p;
        $hasTeamData = true;
    } else {
        $otherPlayers[] = $p;
    }
}

$redScore = null;
$blueScore = null;
$stmtScore = $db->prepare("SELECT red_score, blue_score FROM match_scores WHERE match_id = ?");
$stmtScore->execute([$logId]);
$scoreRow = $stmtScore->fetch(PDO::FETCH_ASSOC);
if ($scoreRow) {
    $redScore  = (int)$scoreRow['red_score'];
    $blueScore = (int)$scoreRow['blue_score'];
    $hasTeamData = true;
}

// 5b. Ordre d'affichage des équipes : vainqueur en premier, égalité => BLU en premier
function teamResult($score, $otherScore)
{
    if ($score === null || $otherScore === null) return null;
    if ($score > $otherScore) return 'win';
    if ($score < $otherScore) return 'loss';
    return 'draw';
}

$teamPanels = [
    [
        'key'        => 'blue',
        'name'       => 'BLU',
        'players'    => $bluePlayers,
        'score'      => $blueScore,
        'otherScore' => $redScore,
    ],
    [
        'key'        => 'red',
        'name'       => 'RED',
        'players'    => $redPlayers,
        'score'      => $redScore,
        'otherScore' => $blueScore,
    ],
];

if ($redScore !== null && $blueScore !== null && $redScore > $blueScore) {
    $teamPanels = array_reverse($teamPanels);
}

foreach ($teamPanels as &$panel) {
    $panel['result'] = teamResult($panel['score'], $panel['otherScore']);
}
unset($panel);

// 6. Helpers d'affichage
function matchMapDisplay($raw)
{
    $raw = trim($raw);
    if ($raw === '') return '—';
    $parts = preg_split('/\s*\+\s*/', $raw);
    $names = [];
    foreach ($parts as $p) {
        $p = preg_replace('/_(final|rc|v|b|f)\d*$/i', '', $p);
        $p = preg_replace('/^(koth|cp|pl|plr|ctf|td|dom|tc|arena|mvm|sd|pass|rd|pd|vsh|ph|zr|dr|slay)_/i', '', $p);
        $p = ucwords(preg_replace('/_/', ' ', trim($p)));
        if ($p !== '') $names[] = $p;
    }
    return implode(' + ', $names);
}

function matchDuration($sec)
{
    $sec = (int)$sec;
    if ($sec <= 0) return null;
    $m = floor($sec / 60);
    $s = $sec % 60;
    return sprintf('%d:%02d', $m, $s);
}

function matchRowHtml($p, $rank)
{
    $classPlayed = htmlspecialchars($p['class_played']);
    $iconPath = "/_img/classes/" . $classPlayed . ".png";
    $iconExists = file_exists(__DIR__ . '/../_img/classes/' . $classPlayed . '.png');
    $pseudo = !empty($p['display_name']) ? $p['display_name'] : ($p['name'] ?? '');
    $pseudo = !empty($pseudo) ? $pseudo : 'Joueur Steam';
    $pseudoDisplay = htmlspecialchars($pseudo);
    $steamid64 = steamID3ToSteamID64($p['steamid']);
    $kills = (int)$p['kills'];
    $deaths = (int)$p['deaths'];
    $kd = $deaths > 0 ? round($kills / $deaths, 2) : ($kills > 0 ? (float)$kills : 0);

    $iconHtml = $iconExists
        ? '<img src="' . $iconPath . '" alt="' . ucfirst($classPlayed) . '" class="class-icon" title="' . ucfirst($classPlayed) . '">'
        : '<span class="class-unknown" title="' . ucfirst($classPlayed) . '">?</span>';

    $avatarHtml = !empty($p['avatar'])
        ? '<img src="' . htmlspecialchars($p['avatar']) . '" alt="Avatar de ' . $pseudoDisplay . '" class="player-avatar">'
        : '';

    $linkHtml = $steamid64
        ? '<a href="/profile/profil?steamid=' . $steamid64 . '" class="player-link">' . $pseudoDisplay . '</a>'
        : '<span class="player-link">' . $pseudoDisplay . '</span>';

    return '<tr>'
        . '<td>' . $rank . '</td>'
        . '<td>' . $iconHtml . '</td>'
        . '<td><div class="player-cell flex align-center gap-10">' . $avatarHtml . $linkHtml . '</div></td>'
        . '<td data-sort-val="' . $kills . '">' . $kills . '</td>'
        . '<td data-sort-val="' . $deaths . '">' . $deaths . '</td>'
        . '<td data-sort-val="' . (int)$p['assists'] . '">' . (int)$p['assists'] . '</td>'
        . '<td data-sort-val="' . (int)$p['dmg'] . '" class="col-dmg">' . number_format((int)$p['dmg'], 0, ',', ' ') . '</td>'
        . '<td data-sort-val="' . (int)$p['heal'] . '">' . number_format((int)$p['heal'], 0, ',', ' ') . '</td>'
        . '<td data-sort-val="' . (int)$p['headshots'] . '">' . (int)$p['headshots'] . '</td>'
        . '<td data-sort-val="' . (int)$p['longest_killstreak'] . '">' . (int)$p['longest_killstreak'] . '</td>'
        . '<td data-sort-val="' . $kd . '">' . $kd . '</td>'
        . '</tr>';
}

function matchTableHeadHtml()
{
    return '<thead><tr>'
        . '<th>#</th>'
        . '<th data-sort="text">Classe</th>'
        . '<th data-sort="text">Joueur</th>'
        . '<th data-sort="num">Kills</th>'
        . '<th data-sort="num">Morts</th>'
        . '<th data-sort="num">Assists</th>'
        . '<th data-sort="num">Dégâts</th>'
        . '<th data-sort="num">Soins</th>'
        . '<th data-sort="num">Headshots</th>'
        . '<th data-sort="num">Killstreak</th>'
        . '<th data-sort="num">K/D</th>'
        . '</tr></thead>';
}

function matchRowsHtml($players)
{
    if (empty($players)) {
        return '<tbody><tr><td colspan="11" class="no-data">Aucun joueur dans cette équipe.</td></tr></tbody>';
    }
    $html = '<tbody>';
    foreach ($players as $i => $p) {
        $html .= matchRowHtml($p, $i + 1);
    }
    return $html . '</tbody>';
}

$mapDisplay = matchMapDisplay($mapName);
$durationDisplay = matchDuration($length);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <!-- HTML Meta Tags -->
    <title>Highlander France - <?= htmlspecialchars($mapDisplay) ?> | <?= $gameModeLabel ?></title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - <?= htmlspecialchars($mapDisplay) ?> | <?= $gameModeLabel ?>">
    <meta property="og:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta property="og:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="highlanderfrance.tf">
    <meta property="twitter:url" content="https://highlanderfrance.tf/">
    <meta name="twitter:title" content="Highlander France - <?= htmlspecialchars($mapDisplay) ?> | <?= $gameModeLabel ?>">
    <meta name="twitter:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta name="twitter:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <!-- Favicon standard -->
    <link rel="shortcut icon" href="https://highlanderfrance.tf/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="https://highlanderfrance.tf/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://highlanderfrance.tf/favicon-16x16.png">
    <link rel="icon" type="image/x-icon" href="https://highlanderfrance.tf/favicon.ico">

    <link rel="apple-touch-icon" href="https://highlanderfrance.tf/apple-touch-icon.png">

    <link rel="icon" type="image/png" sizes="192x192" href="https://highlanderfrance.tf/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="https://highlanderfrance.tf/android-chrome-512x512.png">

    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="../_css/main.css">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-30553SX3GJ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-30553SX3GJ');
    </script>
</head>

<body>

    <?php include(__DIR__ . '/../_inc/header.php'); ?>

    <main id="main">
        <section id="content">

            <div class="matchlog-header">

                <a href="../match-logs.php" class="matchlog-back">
                    <i class="fa-solid fa-arrow-left"></i> Retour aux matchs
                </a>

                <div class="matchlog-title flex align-center gap-10">
                    <h2><?= htmlspecialchars($mapDisplay) ?></h2>
                    <span class="matchlog-mode <?= $gameMode === '6S' ? 'mode-6s' : 'mode-9v9' ?>"><?= $gameMode === '6S' ? '6v6' : '9v9' ?></span>
                </div>

                <div class="matchlog-meta flex align-center wrap">
                    <?php if ($matchDate): ?>
                        <span class="matchlog-meta-item">
                            <i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($matchDate) ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($durationDisplay): ?>
                        <span class="matchlog-meta-item">
                            <i class="fa-regular fa-clock"></i> <?= $durationDisplay ?>
                        </span>
                    <?php endif; ?>

                    <span class="matchlog-meta-item">
                        <i class="fa-solid fa-users"></i> <?= count($players) ?> joueurs
                    </span>

                    <a href="https://logs.tf/<?= $logId ?>" target="_blank" class="matchlog-logs-tf" rel="noopener">
                        <img src="../_img/logo-logstf.png" alt="Voir sur logs.tf" class="logs-tf-logo">
                        Voir sur logs.tf
                    </a>
                </div>

                <?php if ($isAdmin): ?>
                    <div class="matchlog-admin" style="margin-top: 12px;">
                        <button type="button" class="btn-blacklist" data-log-id="<?= $logId ?>" data-log-title="Log #<?= $logId ?> (<?= htmlspecialchars($mapDisplay) ?>)">
                            <i class="fa-solid fa-ban"></i> Blacklister ce log
                        </button>
                    </div>
                <?php endif; ?>

            </div>

                <?php if ($hasTeamData): ?>

                    <?php if ($redScore !== null && $blueScore !== null): ?>
                        <div class="matchlog-scorebar flex align-center justify-center">
                            <span class="score-team score-<?= $teamPanels[0]['key'] ?>"><?= $teamPanels[0]['name'] ?></span>
                            <span class="score-value score-<?= $teamPanels[0]['key'] ?>"><?= $teamPanels[0]['score'] ?></span>
                            <span class="score-sep">-</span>
                            <span class="score-value score-<?= $teamPanels[1]['key'] ?>"><?= $teamPanels[1]['score'] ?></span>
                            <span class="score-team score-<?= $teamPanels[1]['key'] ?>"><?= $teamPanels[1]['name'] ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="matchlog-teams">
                        <?php foreach ($teamPanels as $panel): ?>
                            <div class="matchlog-team team-<?= $panel['key'] ?>">
                                <div class="matchlog-team-head">
                                    <div class="team-head-left flex align-center gap-10">
                                        <span class="team-name"><?= $panel['name'] ?></span>
                                        <?php if ($panel['result']): ?>
                                            <span class="team-result result-<?= $panel['result'] ?>">
                                                <?= $panel['result'] === 'win' ? 'Vainqueur' : ($panel['result'] === 'loss' ? 'Perdant' : 'Égalité') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($panel['score'] !== null): ?>
                                        <span class="team-score"><?= $panel['score'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="matchlog-table-wrapper">
                                    <table class="matchlog-table">
                                        <?= matchTableHeadHtml() ?>
                                        <?= matchRowsHtml($panel['players']) ?>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($otherPlayers)): ?>
                        <div class="matchlog-team-unassigned">
                            <h3>Sans équipe</h3>
                            <div class="matchlog-table-wrapper">
                                <table class="matchlog-table">
                                    <?= matchTableHeadHtml() ?>
                                    <?= matchRowsHtml($otherPlayers) ?>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>

                    <div class="matchlog-table-wrapper">
                        <table id="matchlogTable" class="matchlog-table">
                            <?= matchTableHeadHtml() ?>
                            <?= matchRowsHtml($players) ?>
                        </table>
                    </div>

                <?php endif; ?>

        </section>
    </main>

    <?php include(__DIR__ . '/../_inc/footer.php'); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
    <script src="../_js/main.js"></script>
    <script>
        window.addEventListener("load", function() {

            const content = document.querySelector("#content");
            const offset = -115;

            if (!content) return;

            setTimeout(() => {

                const target = content.getBoundingClientRect().top + window.scrollY + offset;
                const duration = 1000;
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

        // Tri du tableau des joueurs (clic sur les en-têtes)
        (function() {
            const getIndex = cell => Array.prototype.indexOf.call(cell.parentElement.children, cell);

            document.querySelectorAll(".matchlog-table").forEach(function(table) {
                const tbody = table.querySelector("tbody");
                if (!tbody) return;

                table.querySelectorAll("thead th").forEach(function(th) {
                    const type = th.dataset.sort;
                    if (!type) return;

                    th.addEventListener("click", function() {
                        const idx = getIndex(th);
                        const rows = Array.prototype.slice.call(tbody.querySelectorAll("tr"));

                        rows.sort(function(a, b) {
                            const ca = a.cells[idx];
                            const cb = b.cells[idx];
                            if (type === "num") {
                                const va = parseFloat(ca.dataset.sortVal || "0");
                                const vb = parseFloat(cb.dataset.sortVal || "0");
                                return vb - va;
                            }
                            return ca.textContent.trim().localeCompare(cb.textContent.trim(), "fr");
                        });

                        rows.forEach(function(row, pos) {
                            row.cells[0].textContent = pos + 1;
                        });

                        rows.forEach(function(row) {
                            tbody.appendChild(row);
                        });

                        table.querySelectorAll("thead th").forEach(function(h) {
                            h.classList.remove("sorted-asc", "sorted-desc");
                        });
                        th.classList.add("sorted-desc");
                    });
                });
            });
        })();

        // Blacklist d'un log (admin)
        $(document).on("click", ".matchlog-admin .btn-blacklist", function() {
            const logId = $(this).data("log-id");
            const logTitle = $(this).data("log-title");

            if (!confirm(`Blacklister le log #${logId} (« ${logTitle} ») ?\nIl sera exclu des Match Stats et des statistiques.`)) {
                return;
            }

            $.ajax({
                type: "POST",
                url: "/admin/_scripts/admin_blacklist.php",
                data: {
                    action: "add",
                    log_id: logId
                },
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                dataType: "json"
            }).done(function(res) {
                if (res.success) {
                    alert("Log blacklisté. Il a été retiré des statistiques.");
                    window.location.href = "match-logs.php";
                } else {
                    alert(res.message);
                }
            }).fail(function() {
                alert("Erreur lors du blacklisting du log.");
            });
        });
    </script>
</body>

</html>
