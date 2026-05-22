async function loadLeaderboard(mode = '9v9') {
    const tbody = document.getElementById('leaderboard-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="3">Chargement...</td></tr>';
    
    try {
        const filename = `/../_scripts/leaderboard_cache_${mode}.json`;
        const response = await fetch(filename + '?v=' + new Date().getTime()); // Le ?v=... évite le cache navigateur
        if (!response.ok) {
            throw new Error('Fichier introuvable: ' + filename);
        }
        const players = await response.json();
        
        tbody.innerHTML = ''; // clean loading message
        
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

function switchLeaderboard(button, mode) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    button.classList.add('active');
    
    loadLeaderboard(mode);
}