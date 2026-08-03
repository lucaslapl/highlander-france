<?php
require_once __DIR__ . "/../_inc/config.php";
require_once __DIR__ . "/../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : accès admin strict
checkAdminOrDie();

/**
 * Récupère du JSON via cURL
 */
function getJson($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return ($response === false) ? false : json_decode($response, true);
}

/**
 * Récupère la durée (en secondes) des logs manquants en parallèle (cURL multi), par lots de 10.
 */
function fetchLogLengths(array $logIds, int $batchSize = 10, int $sleepMicros = 300000)
{
    $lengths = [];
    foreach (array_chunk(array_values($logIds), $batchSize) as $batch) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($batch as $id) {
            $ch = curl_init("https://logs.tf/api/v1/log/$id");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$id] = $ch;
        }
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh);
            }
        } while ($running > 0);

        foreach ($handles as $id => $ch) {
            $data = json_decode(curl_multi_getcontent($ch), true);
            if (isset($data['length'])) {
                $lengths[$id] = (int)$data['length'];
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        if (count($batch) === $batchSize) {
            usleep($sleepMicros);
        }
    }
    return $lengths;
}

function formatDuration($seconds)
{
    if ($seconds === null) {
        return '—';
    }
    $min = intdiv($seconds, 60);
    $sec = $seconds % 60;
    return $min > 0 ? $min . ' min ' . $sec . ' s' : $sec . ' s';
}

// 1. Récupération de l'index logs.tf (les deux titres utilisés par la communauté)
$data_old = getJson("https://logs.tf/api/v1/log?title=Highlander%20France&limit=200");
$data_new = getJson("https://logs.tf/api/v1/log?title=highlanderfrance.tf&limit=200");

$logs_old = $data_old["logs"] ?? [];
$logs_new = $data_new["logs"] ?? [];

// 2. Fusion, dédup par id et exclusion des logs blacklistés
$blacklist = getBlacklistedLogIds($db);
$merged = [];
foreach (array_merge($logs_old, $logs_new) as $log) {
    $log_id = $log["id"] ?? null;
    if (!$log_id || in_array($log_id, $blacklist)) {
        continue;
    }
    $merged[$log_id] = $log;
}
usort($merged, function ($a, $b) {
    return $b['id'] <=> $a['id'];
});

// 3. Durées : lecture du cache puis fetch des logs manquants
$cache = [];
$cacheStmt = $db->query("SELECT log_id, length FROM log_length_cache");
foreach ($cacheStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $cache[(int)$row['log_id']] = (int)$row['length'];
}

$missingIds = [];
foreach ($merged as $log) {
    $logId = (int)$log['id'];
    if (!isset($cache[$logId])) {
        $missingIds[] = $logId;
    }
}

if (!empty($missingIds)) {
    $newLengths = fetchLogLengths($missingIds);
    $insert = $db->prepare("INSERT OR IGNORE INTO log_length_cache (log_id, length) VALUES (?, ?)");
    foreach ($newLengths as $id => $length) {
        $insert->execute([$id, $length]);
        $cache[$id] = $length;
    }
}

// 3bis. Modes stockés en base de données (un log = un mode)
$dbModes = [];
$modeStmt = $db->query("SELECT match_id, game_mode FROM player_matches GROUP BY match_id");
foreach ($modeStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $dbModes[(int)$row['match_id']] = $row['game_mode'];
}

// 4. Construction des lignes
$rows = '';
foreach ($merged as $log) {
    $logId   = (int)$log['id'];
    $title   = $log['title'] ?? '';
    $map     = $log['map'] ?? '';
    $date    = $log['date'] ?? null;
    $players = (int)($log['players'] ?? 0);
    $length  = isset($cache[$logId]) ? $cache[$logId] : null;

    // Durée < 10 min (600 s) => orange
    $durationStr = formatDuration($length);
    $durationCls = ($length !== null && $length < 600) ? ' cell-warning' : '';

    // Mode retenu pour la règle orange : BDD d'abord, sinon marqueur du titre
    $modeForCheck = $dbModes[$logId] ?? null;
    if (!$modeForCheck) {
        if (stripos($title, '[6s]') !== false) {
            $modeForCheck = '6s';
        } elseif (stripos($title, '[9s]') !== false) {
            $modeForCheck = '9v9';
        }
    }

    $playersCls = '';
    if ($modeForCheck === '6s' && $players < 12) {
        $playersCls = ' cell-warning';
    } elseif ($modeForCheck === '9v9' && $players < 18) {
        $playersCls = ' cell-warning';
    }

    // Mode BDD + boutons d'action
    $dbMode = $dbModes[$logId] ?? null;
    $modeBadge = $dbMode
        ? '<span class="badge mode-badge">' . htmlspecialchars($dbMode) . '</span>'
        : '<span style="color:#555;">—</span>';

    $blacklistBtn = '<button type="button" class="btn-icon btn-blacklist" data-log-id="' . $logId . '" data-log-title="' . htmlspecialchars($title, ENT_QUOTES) . '" title="Exclure ce log des statistiques"><i class="fa-solid fa-ban"></i></button>';

    if ($dbMode) {
        $targetMode = ($dbMode === '6s') ? '9v9' : '6s';
        $modeBtn = '<button type="button" class="btn-icon btn-mode" data-log-id="' . $logId . '" data-mode="' . $targetMode . '" title="Passer ce log en mode ' . $targetMode . '"><i class="fa-solid fa-arrows-rotate"></i></button>';
    } else {
        $modeBtn = '<button type="button" class="btn-icon btn-mode" disabled title="Log non traité en base"><i class="fa-solid fa-arrows-rotate"></i></button>';
    }

    $dateStr = $date ? date('d/m/Y H:i', $date) : '—';

    $rows .= "<tr>
        <td>{$dateStr}</td>
        <td>" . htmlspecialchars($map) . "</td>
        <td><a href=\"https://logs.tf/{$logId}\" target=\"_blank\">" . htmlspecialchars($title) . "</a></td>
        <td class=\"{$playersCls}\" style=\"text-align:center;\">{$players}</td>
        <td class=\"{$durationCls}\" style=\"text-align:center;\">{$durationStr}</td>
        <td style=\"text-align:center;\">{$modeBadge}</td>
        <td style=\"text-align:center; white-space:nowrap;\">{$blacklistBtn}{$modeBtn}</td>
    </tr>";
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Logs des matchs joués</title>
    <link rel="stylesheet" href="../_css/main.css">
    <link rel="stylesheet" href="_css/admin.css">
</head>

<body>

    <?php include("../_inc/header.php"); ?>

    <main id="main" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">

        <div style="margin-bottom: 20px;">
            <a href="dashboard" style="color: #aaa; text-decoration: none; font-size: 14px;">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
        </div>

        <div class="admin-header" style="border-bottom: 2px solid #f39c12; padding-bottom: 15px; margin-bottom: 30px;">
            <h2 style="color: #f39c12; margin: 0;"><i class="fa-solid fa-clock-rotate-left"></i> Logs des matchs joués</h2>
            <p style="margin: 5px 0 0 0; color: #aaa;">
                Liste des matchs avec nombre de joueurs et durée.
                <span class="cell-warning" style="padding: 2px 6px; border-radius: 3px;">Orange</span> = match de moins de 10 min, ou effectif incomplet ([6s] &lt; 12 joueurs, [9s] &lt; 18 joueurs).
            </p>
        </div>

        <div style="margin-bottom: 15px;">
            <input type="text" id="log-search" placeholder="Rechercher un titre ou une carte…"
                style="width: 100%; max-width: 400px; background: #1e1e1e; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 10px 15px; border-radius: 6px; font-size: 0.95em; box-sizing: border-box;">
        </div>

        <div style="max-height: 600px; overflow-y: auto;">
            <table class="admin-table" id="logsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Carte</th>
                        <th>Titre</th>
                        <th style="text-align:center;">Joueurs</th>
                        <th style="text-align:center;">Durée</th>
                        <th style="text-align:center;">Mode (BDD)</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?= $rows ?: '<tr><td colspan="7" style="color:#aaa;font-style:italic;">Aucun log à afficher.</td></tr>' ?>
                </tbody>
            </table>
        </div>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        $("#log-search").on("input", function() {
            const q = this.value.toLowerCase();
            $("#logsTable tbody tr").each(function() {
                $(this).toggle($(this).text().toLowerCase().includes(q));
            });
        });

        // Blacklister un log
        $(document).on("click", ".btn-blacklist", function() {
            const btn = $(this);
            const logId = btn.data("log-id");
            const logTitle = btn.data("log-title");

            if (!confirm(`Blacklister le log #${logId} (« ${logTitle} ») ?\nIl sera exclu des Match Stats et des statistiques.`)) {
                return;
            }

            $.ajax({
                type: "POST",
                url: "_scripts/admin_blacklist.php",
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
                    btn.closest("tr").remove();
                    if ($("#logsTable tbody tr").length === 0) {
                        $("#logsTable tbody").html('<tr><td colspan="7">Aucun log à afficher.</td></tr>');
                    }
                } else {
                    alert(res.message);
                }
            }).fail(function() {
                alert("Erreur lors du blacklisting du log.");
            });
        });

        // Changer le mode de jeu (6s / 9v9)
        $(document).on("click", ".btn-mode", function() {
            const btn = $(this);
            const logId = btn.data("log-id");
            const targetMode = btn.data("mode");

            if (!confirm(`Passer le log #${logId} en mode ${targetMode.toUpperCase()} dans la base de données ?`)) {
                return;
            }

            $.ajax({
                type: "POST",
                url: "_scripts/admin_match_mode.php",
                data: {
                    action: "switch_mode",
                    log_id: logId,
                    mode: targetMode
                },
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                dataType: "json"
            }).done(function(res) {
                alert(res.message);
                if (res.success) {
                    location.reload();
                }
            }).fail(function() {
                alert("Erreur lors du changement de mode.");
            });
        });
    </script>
</body>

</html>