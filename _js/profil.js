async function switchProfileMode(button, mode, steamidFallback = null) {
    // 1. Changement visuel de l'onglet actif
    document.querySelectorAll('.profile-tab-btn').forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');

    // 2. Extraction du SteamID64 depuis l'URL de la page
    const urlParams = new URLSearchParams(window.location.search);
    const steamid = steamidFallback || urlParams.get('steamid');

    if (!steamid) {
        console.error("SteamID64 non trouvé dans l'URL.");
        return;
    }

    try {
        // Effet visuel de transition
        document.querySelector('.player-stats').style.opacity = '0.5';

        // Requête vers le fichier d'API de statistiques (à créer à l'étape suivante)
        const response = await fetch(`/profile/get_profile_stats.php?steamid=${steamid}&mode=${mode}`);
        if (!response.ok) throw new Error('Erreur réseau');

        const data = await response.json();

        // 3. Remplacement des textes basiques
        document.getElementById('stats-title').innerText = `Stats - ${mode === '6s' ? '6v6' : 'Highlander'}`;
        document.getElementById('recent-title').innerText = `Matchs Récents (${mode === '6s' ? '6v6' : '9v9'})`;
        document.getElementById('stat-total-matches').innerText = data.total_matches;
        document.getElementById('stat-total-damage').innerText = Number(data.total_damage || 0).toLocaleString('fr-FR');
        document.getElementById('stat-total-kills').innerText = data.total_kills || 0;
        document.getElementById('stat-total-deaths').innerText = data.total_deaths || 0;
        document.getElementById('stat-kd-ratio').innerText = data.kd_ratio || 0;

        // 4. Remplacement du tableau des Maps
        const mapsContainer = document.getElementById('maps-container');
        if (!data.top_maps || data.top_maps.length === 0) {
            mapsContainer.innerHTML = '<p class="no-data">Aucune donnée de map pour ce mode.</p>';
        } else {
            let html = '<ul class="stats-list">';
            data.top_maps.forEach(map => {
                html += `
                    <li class="flex space-between align-center">
                        <span class="stat-label">${escapeHtml(map.map_name)}</span>
                        <span class="stat-value">${map.total} match(s)</span>
                    </li>`;
            });
            html += '</ul>';
            mapsContainer.innerHTML = html;
        }

        // 5. Remplacement du tableau des Classes
        const classesContainer = document.getElementById('classes-container');
        if (!data.classes_played || data.classes_played.length === 0) {
            classesContainer.innerHTML = '<p class="no-data">Aucune donnée de classe pour ce mode.</p>';
        } else {
            let html = '<ul class="stats-list">';
            data.classes_played.forEach(cls => {
                const className = escapeHtml(cls.class_played);
                html += `
                    <li class="flex space-between align-center">
                        <div class="flex align-center gap-10">
                            <img src="/_img/classes/${className}.png" alt="${className}" class="class-icon" title="${className}">
                        </div>
                        <span class="stat-value">${cls.total}</span>
                    </li>`;
            });
            html += '</ul>';
            classesContainer.innerHTML = html;
        }

        // 5bis. Tableau des Classes tuées
        const ckContainer = document.getElementById('classes-killed-container');
        if (!data.classes_killed || Object.keys(data.classes_killed).length === 0) {
            ckContainer.innerHTML = '<p class="no-data">Aucune donnée de classe tuée pour ce mode.</p>';
        } else {
            let html = '<ul class="stats-list">';
            Object.entries(data.classes_killed).forEach(([cls, count]) => {
                const className = escapeHtml(cls);
                html += `
            <li class="flex space-between align-center">
                <div class="flex align-center gap-10">
                    <img src="/_img/classes/${className}.png" alt="${className}" class="class-icon" title="${className}">
                </div>
                <span class="stat-value">${count}</span>
            </li>`;
            });
            html += '</ul>';
            ckContainer.innerHTML = html;
        }

        // 6. Remplacement des Matchs Récents
        const recentContainer = document.getElementById('recent-container');
        if (!data.recent_matches || data.recent_matches.length === 0) {
            recentContainer.innerHTML = '<p class="no-data">Aucun match enregistré pour le moment dans ce mode.</p>';
        } else {
            let html = '<ul class="matches-list">';
            data.recent_matches.forEach(match => {
                const mId = escapeHtml(match.match_id);
                const cPlayed = escapeHtml(match.class_played);
                html += `
                    <li class="flex space-between align-center match-item">
                        <div class="flex align-center gap-15">
                            <img src="/_img/classes/${cPlayed}.png" alt="${cPlayed}" class="class-icon" title="Joué en ${cPlayed}">
                            <span class="match-map">${escapeHtml(match.map_name)}</span>
                            <span class="stat-value">${match.kills || 0}K / ${match.deaths || 0}D / ${Number(match.dmg || 0).toLocaleString('fr-FR')} dmg</span>
                        </div>
                        <a href="https://logs.tf/${mId}" target="_blank" class="btn-log">
                            <i class="fa-solid fa-file-lines"></i> Log #${mId}
                        </a>
                    </li>`;
            });
            html += '</ul>';
            recentContainer.innerHTML = html;
        }

    } catch (error) {
        console.error("Erreur lors de la mise à jour des statistiques:", error);
    } finally {
        document.querySelector('.player-stats').style.opacity = '1';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
}