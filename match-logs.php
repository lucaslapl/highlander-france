<?php
require_once "_inc/config.php";
require_once "_inc/functions.php";
$isAdmin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <!-- HTML Meta Tags -->
    <title>Highlander France - Logs des Matchs</title>
    <meta name="description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://highlanderfrance.tf/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Highlander France - Logs des Matchs">
    <meta property="og:description" content="Highlander France est une communauté compétitive francophone de Team Fortress 2, offrant un espace pour les joueurs de tous niveaux pour apprendre, jouer et progresser ensemble.">
    <meta property="og:image" content="https://highlanderfrance.tf/_img/meta-bg-hlfr.jpg">

    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="highlanderfrance.tf">
    <meta property="twitter:url" content="https://highlanderfrance.tf/">
    <meta name="twitter:title" content="Highlander France - Logs des Matchs">
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

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-30553SX3GJ');
    </script>
</head>

<body>



    <?php include("_inc/header.php"); ?>

    <main id="main">
        <section id="content">
            <h2>Stats des Matchs</h2>
            <p>Consultez les logs détaillés des matchs de Highlander France.</p>

            <div id="filters">
                <input type="text" id="filter-date" placeholder="Rechercher par date (ex: 27/04)">
                <input type="text" id="filter-map" placeholder="Rechercher une map…">
            </div>

            <table id="logsTable" border="0" cellspacing="20">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Carte</th>
                        <th>Titre</th>
                    </tr>
                </thead>
                <tbody id="logs">

                </tbody>
            </table>

            <div id="pagination" class="pagination"></div>

        </section>

    </main>

    <?php include("_inc/footer.php"); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
    <script src="../_js/main.js"></script>
    <script>
        window.addEventListener("load", function() {

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

        const HLFR_IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;

        $.getJSON("./_scripts/hlfr_logs.php", function(logs) {

            // Supprimer les 4 plus anciennes logs
            logs = logs.slice(0, logs.length - 4);

            if (HLFR_IS_ADMIN) {
                $("#logsTable thead tr").append('<th style="text-align:center;">Action</th>');
            }

            // Précalcul des chaînes une seule fois (évite de re-formater les dates à chaque rendu/filtre)
            logs = logs.map(log => {
                const d = new Date(log.date * 1000);
                const opts = {
                    year: "numeric",
                    month: "2-digit",
                    day: "2-digit",
                    hour: "2-digit",
                    minute: "2-digit"
                };
                return {
                    id: log.id,
                    map: log.map,
                    title: log.title,
                    _display: d.toLocaleString("fr-FR", opts),
                    _filter: d.toLocaleString("fr-FR", opts).toLowerCase(),
                    _map: String(log.map).toLowerCase(),
                    _title: String(log.title).toLowerCase()
                };
            });

            const logsPerPage = 10;
            let currentPage = 1;

            let filteredLogs = [...logs];

            function applyFilters() {
                const dateFilter = $("#filter-date").val().trim().toLowerCase();
                const mapFilter = $("#filter-map").val().trim().toLowerCase();

                if (!dateFilter && !mapFilter) {
                    filteredLogs = logs;
                } else {
                    filteredLogs = logs.filter(log => {
                        if (dateFilter && !log._filter.includes(dateFilter)) return false;
                        if (mapFilter && !log._map.includes(mapFilter)) return false;
                        return true;
                    });
                }

                currentPage = 1;
                renderTable(currentPage);
                renderPagination();
            }

            function escapeAttr(text) {
                return (text || '').toString().replace(/"/g, '&quot;');
            }

            function renderTable(page) {
                const start = (page - 1) * logsPerPage;
                const end = start + logsPerPage;
                const pageLogs = filteredLogs.slice(start, end);

                let rows = "";

                pageLogs.forEach((log, index) => {
                    let actionsCell = "";
                    if (HLFR_IS_ADMIN) {
                        actionsCell = `
                    <td style="text-align:center;">
                        <button type="button" class="btn-blacklist" data-log-id="${log.id}" data-log-title="${escapeAttr(log.title)}" title="Exclure ce log des statistiques">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                    </td>`;
                    }

                    rows += `
                <tr class="log-row" data-index="${index}">
                    <td>${log._display}</td>
                    <td>${log.map}</td>
                    <td>
                        <div class="log-title-cell flex align-center gap-10">
                            <a class="log-link" href="log/match-log.php?id=${log.id}">
                                ${log.title}
                            </a>
                            <a class="log-external" href="https://logs.tf/${log.id}" target="_blank" rel="noopener" title="Voir sur logs.tf">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </td>
                    ${actionsCell}
                </tr>
            `;
                });

                if (!pageLogs.length) {
                    rows = '<tr><td colspan="' + (HLFR_IS_ADMIN ? 4 : 3) + '">Aucun log à afficher.</td></tr>';
                }

                $("#logsTable tbody").html(rows);

                $(".log-row").each(function(i) {
                    setTimeout(() => $(this).addClass("visible"), i * 80);
                });
            }

            function renderPagination() {
                const totalPages = Math.max(1, Math.ceil(filteredLogs.length / logsPerPage));

                if (totalPages <= 1) {
                    $("#pagination").html("");
                    return;
                }

                const pageBtn = i => `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
                const prev = `<button class="page-btn nav" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>&laquo;</button>`;
                const next = `<button class="page-btn nav" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>&raquo;</button>`;

                // Fenêtre glissante autour de la page courante (jamais plus de 7 numéros)
                const maxVisible = 7;
                let start = Math.max(1, currentPage - 3);
                let end = Math.min(totalPages, start + maxVisible - 1);
                if (currentPage > totalPages - 4) {
                    start = Math.max(1, totalPages - maxVisible + 1);
                    end = totalPages;
                }

                let buttons = prev;

                if (start > 1) {
                    buttons += pageBtn(1);
                    if (start > 2) buttons += '<span class="page-ellipsis">…</span>';
                }

                for (let i = start; i <= end; i++) {
                    buttons += pageBtn(i);
                }

                if (end < totalPages) {
                    if (end < totalPages - 1) buttons += '<span class="page-ellipsis">…</span>';
                    buttons += pageBtn(totalPages);
                }

                buttons += next;

                $("#pagination").html(buttons);
            }

            // Délégation : les handlers sont bindés une seule fois, quel que soit le nombre de pages
            $("#pagination").on("click", ".page-btn", function() {
                if ($(this).is("[disabled]")) return;
                const page = parseInt($(this).data("page"), 10);
                if (isNaN(page) || page === currentPage) return;
                currentPage = page;
                renderTable(currentPage);
                renderPagination();
            });

            // Délégation pour le blacklist (admin uniquement)
            $("#logsTable tbody").on("click", ".btn-blacklist", function() {
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
                        $(`.btn-blacklist[data-log-id="${logId}"]`).closest("tr").remove();
                        if ($("#logsTable tbody tr").length === 0) {
                            $("#logsTable tbody").html('<tr><td colspan="4">Aucun log à afficher.</td></tr>');
                        }
                    } else {
                        alert(res.message);
                    }
                }).fail(function() {
                    alert("Erreur lors du blacklisting du log.");
                });
            });

            // Événements des filtres (debounce 200ms pour éviter de recalculer à chaque frappe)
            let filterTimer;
            $("#filter-date").on("input", function() {
                clearTimeout(filterTimer);
                filterTimer = setTimeout(applyFilters, 200);
            });
            $("#filter-map").on("input", function() {
                clearTimeout(filterTimer);
                filterTimer = setTimeout(applyFilters, 200);
            });

            // Affichage initial
            applyFilters();
        });
    </script>
</body>

</html>