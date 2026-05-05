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
    <title>Highlander France</title>
    <link rel="stylesheet" href="_css/main.css">
</head>
<body>

    

    <?php include("_inc/header.php"); ?>

    <main id="main">
        <section id="content">

            <div class="leaderboard-container">
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