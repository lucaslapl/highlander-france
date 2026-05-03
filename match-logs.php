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
            <h3>Stats des Matchs</h3>
            <p>Consultez les logs détaillés des matchs de Highlander France.</p>

            <div id="filters">
                <input type="text" id="filter-date" placeholder="Rechercher par date (ex: 27/04)">
                <input type="text" id="filter-map" placeholder="Rechercher une map…">
                <input type="text" id="filter-title" placeholder="Rechercher un titre…">
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

            <div class="leaderboard-container">
    <h2>TOP 18 Joueurs</h2>
    <table id="leaderboard-table">
        <thead>
            <tr>
                <th>Rang</th>
                <th>Joueur</th>
                <th>Matchs</th>
            </tr>
        </thead>
        <tbody id="leaderboard-body">
            </tbody>
    </table>
</div>

<style>
.leaderboard-container { max-width: 600px; margin: auto; font-family: sans-serif; }
.player-info { display: flex; align-items: center; gap: 10px; }
.player-avatar { width: 32px; height: 32px; border-radius: 50%; }
</style>

        </section>

    </main>

    <?php include("_inc/footer.php"); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
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

$.getJSON("./_scripts/hlfr_logs.php", function(logs) {

    // Supprimer les 4 plus anciennes logs
    logs = logs.slice(0, logs.length - 4);

    const logsPerPage = 10;
    let currentPage = 1;

    let filteredLogs = [...logs];

    function applyFilters() {
        const dateFilter = $("#filter-date").val().trim().toLowerCase();
        const mapFilter = $("#filter-map").val().trim().toLowerCase();
        const titleFilter = $("#filter-title").val().trim().toLowerCase();

        filteredLogs = logs.filter(log => {
            const dateStr = new Date(log.date * 1000).toLocaleString("fr-FR", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit"
            }).toLowerCase();

            const mapStr = log.map.toLowerCase();
            const titleStr = log.title.toLowerCase();

            if (dateFilter && !dateStr.includes(dateFilter)) return false;
            if (mapFilter && !mapStr.includes(mapFilter)) return false;
            if (titleFilter && !titleStr.includes(titleFilter)) return false;

            return true;
        });

        currentPage = 1;
        renderTable(currentPage);
        renderPagination();
    }

    function renderTable(page) {
        const start = (page - 1) * logsPerPage;
        const end = start + logsPerPage;
        const pageLogs = filteredLogs.slice(start, end);

        let rows = "";

        pageLogs.forEach((log, index) => {
            const date = new Date(log.date * 1000).toLocaleString("fr-FR", {
                year: "numeric",
                month: "2-digit",
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit"
            });

            rows += `
                <tr class="log-row" data-index="${index}">
                    <td>${date}</td>
                    <td>${log.map}</td>
                    <td>
                        <a class="log-link" href="https://logs.tf/${log.id}" target="_blank">
                            ${log.title}
                        </a>
                    </td>
                </tr>
            `;
        });

        $("#logsTable tbody").html(rows);

        $(".log-row").each(function(i) {
            setTimeout(() => $(this).addClass("visible"), i * 80);
        });
    }

    function renderPagination() {
        const totalPages = Math.ceil(filteredLogs.length / logsPerPage);
        let buttons = "";

        for (let i = 1; i <= totalPages; i++) {
            buttons += `
                <button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">
                    ${i}
                </button>
            `;
        }

        $("#pagination").html(buttons);

        $(".page-btn").on("click", function() {
            currentPage = parseInt($(this).data("page"));
            renderTable(currentPage);
            renderPagination();
        });
    }

    // Événements des filtres
    $("#filter-date").on("input", applyFilters);
    $("#filter-map").on("input", applyFilters);
    $("#filter-title").on("input", applyFilters);
    

    // Affichage initial
    applyFilters();
});

async function loadLeaderboard() {
    const tbody = document.getElementById('leaderboard-body');
    
    try {
        const response = await fetch('_scripts/leaderboard_cache.json?v=' + new Date().getTime()); // Le ?v=... évite le cache navigateur
        const players = await response.json();
        
        tbody.innerHTML = ''; // Nettoyer avant affichage
        
        players.forEach((player, index) => {
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>#${index + 1}</td>
                <td>
                    <div class="player-info">
                        <img src="${player.avatar}" class="player-avatar" alt="avatar">
                        <span>${escapeHtml(player.name)}</span>
                    </div>
                </td>
                <td>${player.count}</td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="3">Erreur lors du chargement...</td></tr>';
        console.error('Erreur:', error);
    }
}

// Sécurité basique pour éviter les caractères spéciaux dans les pseudos
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Lancement au chargement de la page
loadLeaderboard();
</script>
</body>
</html>