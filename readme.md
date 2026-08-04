# Highlander France

Site web communautaire de la **communauté compétitive francophone de Team Fortress 2**, axée sur le format **9v9 (Highlander)**. Lancé en février 2026, il réunit joueurs et joueuses francophones autour du mode compétitif pour échanger, apprendre et progresser ensemble.

> Démonstration du projet — ce dépôt contient le code source du site. Aucune procédure d'installation n'est documentée ici.

## Aperçu des fonctionnalités

- **Accueil** (`index.php`) : présentation de la communauté, chiffres clés (matchs organisés, heures jouées) et agenda des prochains matchs d'équipes françaises.
- **Agenda ETF2L** (`_inc/upcoming_matches.php`) : liste des matchs à venir des équipes françaises, synchronisés depuis l'API ETF2L.
- **Logs des matchs** (`match-logs.php`) : navigateur des logs récupérés sur [logs.tf](https://logs.tf) avec recherche par date/carte, pagination et exclusion des logs blacklistés. Les administrateurs peuvent blacklister un log directement depuis la page.
- **Hall of Fame** (`hall-of-fame.php`) : classement des joueurs par nombre de matchs, séparé par mode de jeu (**9v9 Highlander** et **6v6 Sixes**) et recherche de joueurs intégrée.
- **Profils joueurs** (`profile/profil.php`) : page publique avec statistiques par mode, classe jouée, cartes et classes tuées (graphiques Chart.js), matchs récents avec résultat (Victoire/Défaite), badges de rôle et nationalité.
- **Espace personnel** (`profile/dashboard.php`) : profil du joueur connecté, choix unique et définitif du pseudo d'affichage et de la nationalité (verrouillage en base).
- **Équipe / Staff** (`staff.php`) : annuaire des fondateurs, modérateurs, mentors et lanceurs de mix.
- **Authentification Steam** (`login.php`, `callback.php`, `logout.php`) : connexion via OpenID Steam (`_libs/openid.php`) avec sessions sécurisées (cookie `HLFR_SESSION`, 30 jours, `HttpOnly` + `SameSite=Lax`).
- **Panel d'administration** (`admin/`) : tableau de bord avec graphiques de statistiques (Chart.js), statut des API en temps réel, gestion des joueurs et du staff, gestion des logs de matchs (durée, effectifs, correction du mode 6s/9v9, blacklist) et journal d'exécution des scripts.
- **Blacklist des logs** : exclusion des logs logs.tf des statistiques (Match Stats, accueil, profils), avec motif, traçabilité (auteur/date) et purge rétroactive en base.
- **Pages légales & erreurs** : politique de confidentialité (`confidentialite.php`), page de maintenance (`maintenance.html`) et pages d'erreur personnalisées (`errors/`, 400/403/404).

## Architecture technique

| Couche | Technologie |
| --- | --- |
| Backend | PHP (procédural, PDO) |
| Base de données | SQLite (`_scripts/stats.db`) |
| Frontend | HTML, SCSS → CSS (`_scss/` → `_css/`), JavaScript vanilla + jQuery 3.7.1 |
| Graphiques | [Chart.js 4](https://www.chartjs.org) (profils joueurs, dashboard admin) |
| Icônes | FontAwesome (Kit) |
| Authentification | OpenID Steam |
| APIs externes | [logs.tf API](https://logs.tf), [ETF2L API v2](https://api-v2.etf2l.org), Steam Web API |
| Analytics | Google Analytics (gtag.js) |

### Structure des dossiers

```
_inc/          Configuration, fonctions utilitaires, en-tête/pied de page, agenda, statut des API
_scripts/      Scripts de synchronisation & endpoints JSON (stats, logs, recherche)
_js/           Scripts front-end (leaderboard, recherche, profil, main)
_css/ _scss/   Feuilles de style compilées et sources
_libs/         Bibliothèque OpenID Steam
admin/         Panel d'administration (dashboard, gestion joueurs/staff/logs, blacklist)
profile/       Pages de profil joueur (public + espace personnel)
errors/        Pages d'erreur HTTP personnalisées
_img/ _fonts/  Ressources graphiques et polices
_sessions/     Stockage des sessions PHP (hors versionnement)
```

## Flux de données & synchronisation

Le site s'appuie sur des scripts PHP exécutables en ligne de commande (cron) ou manuellement depuis le panel admin :

- **`sync_etf2l.php`** : récupère les matchs planifiés de l'API ETF2L et filtre ceux concernant la France (pays + liste d'équipes autorisées) pour alimenter l'agenda.
- **`update_stats.php`** : interroge logs.tf, détecte les nouveaux logs (titres `Highlander France` / `highlanderfrance.tf`), en déduit le mode de jeu, et agrège les statistiques détaillées par joueur et par match. Il applique aussi :
  - une **blacklist automatique** des logs de moins de 5 minutes (`MIN_MATCH_LENGTH`, constante `_inc/config.php`),
  - une **purge rétroactive** des logs blacklistés et des logs à classe indéfinie (`undefined` / `unknown`) des statistiques.
- **`sync_steam.php` / `sync_steam_avatars.php`** : synchronisent pseudos et avatars depuis l'API Steam.
- **`generate_json.php` / `update_index_stats.php` / `get_index_stats.php`** : mettent en cache et exposent les statistiques globales de la page d'accueil.
- **`hlfr_logs.php` / `search_players.php`** : endpoints JSON consommés par le front-end (jQuery).
- **`backfill_log_dates.php` / `backfill_player_match_stats.php` / `migrate_player_match_stats.php`** : scripts ponctuels de migration/retour-arrière des données.

Chaque exécution de script est auditée via `logScriptExecution()` (fonction `_inc/functions.php`) dans un fichier de journal avec statut `STARTED` / `SUCCESS` / `FAILED`. Le panel admin peut aussi vérifier en temps réel l'état des API (ETF2L, logs.tf, Steam) via `_inc/api_status.php` (latence, code HTTP, dernière synchro).

### Base de données SQLite

Tables principales de `_scripts/stats.db` (créées/migrées automatiquement par `_inc/config.php`) :

- `players_info` : profils joueurs (nom, pseudo d'affichage, avatar, pays, rôles, dates d'inscription, verrouillages).
- `player_stats` : nombre de matchs par joueur et par mode (`9v9` / `6s`).
- `player_matches` : statistiques détaillées par joueur et par match (dégâts, kills, morts, assists, ubers, backstabs, headshots, airshots, classes tuées, victoire/défaite…).
- `etf2l_matches` : cache des prochains matchs ETF2L.
- `processed_logs` : logs logs.tf déjà traités.
- `log_blacklist` : logs exclus des statistiques (motif, auteur, date).
- `log_length_cache` / `log_dates` : caches des durées et dates des logs (page admin des matchs, graphiques).

## Authentification & rôles

- Connexion via **OpenID Steam** ; le SteamID64 est converti en SteamID3 pour l'indexation en base.
- Rôles gérés : `is_admin`, `is_founder`, `is_mentor`, `is_mixer`, `is_moderator`.
- Les pages sensibles (panel admin, scripts de sync) sont protégées par `checkAdminOrDie()` (vérification stricte de `$_SESSION['is_admin']`).

## Configuration

Le projet utilise un fichier `.env` (hors versionnement) pour les secrets, notamment la clé `STEAM_API_KEY` de l'API Steam — un dans `_inc/` et un dans `_scripts/` (les scripts CLI lisent celui de leur répertoire). Les sessions (durée 30 jours, cookie sécurisé `HLFR_SESSION`) et la base SQLite sont configurées dans `_inc/config.php`.

## Notes

- Langue de l'interface : **français**.
- Les fichiers de cache (`stats.db`, `*.json` de cache, `.htaccess`, `.env`, `_sessions/`) sont exclus du versionnement via `.gitignore`.
