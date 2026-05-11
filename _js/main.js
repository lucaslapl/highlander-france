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
                        <a href="/profile/profil?steamid=${player.steamid}" class="player-link">
                            <img src="${player.avatar}" class="player-avatar" alt="Avatar de ${escapeHtml(player.name)}">
                            <span>${escapeHtml(player.name)}</span>
                        </a>
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