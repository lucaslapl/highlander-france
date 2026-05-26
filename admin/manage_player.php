<?php
require_once __DIR__ . "/../_inc/config.php";
require_once __DIR__ . "/../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : Si le visiteur n'est pas admin, le script meurt ici immédiatement.
checkAdminOrDie();

// Récupération du joueur à modifier
$target_steamid = $_GET['steamid'] ?? null;

if (!$target_steamid || !preg_match('/^\d{17}$/', $target_steamid)) {
    http_response_code(400);
    die("SteamID cible invalide.");
}

$target_steamid3 = steamID64ToSteamID3($target_steamid);

// On récupère les infos du joueur cible
$stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
$stmt->execute([$target_steamid3]);
$target_player = $stmt->fetch();

if (!$target_player) {
    die("Joueur introuvable en base de données.");
}

// Nom final pour l'affichage
$final_name = !empty($target_player['display_name']) ? $target_player['display_name'] : $target_player['name'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gérer <?= htmlspecialchars($final_name) ?></title>
    <link rel="stylesheet" href="../_css/main.css">
    <style>
        /* Styles dédiés au formulaire d'administration */
        .admin-card {
            background: #1a1a1a;
            border: 1px solid #2b2b2b;
            border-radius: 6px;
            padding: 25px;
            margin-top: 20px;
        }
        .player-profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #141419;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #ff4444;
            margin-bottom: 25px;
        }
        .player-avatar {
            width: 64px;
            height: 64px;
            border-radius: 6px;
            border: 2px solid #333;
        }
        .form-section {
            margin-bottom: 25px;
        }
        .form-section h3 {
            color: #fff;
            border-bottom: 1px solid #2b2b2b;
            padding-bottom: 8px;
            margin-bottom: 15px;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .admin-label {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #222;
            padding: 12px 15px;
            border-radius: 4px;
            cursor: pointer;
            user-select: none;
            transition: background 0.2s, border-color 0.2s;
            border: 1px solid transparent;
            font-size: 14px;
        }
        .admin-label:hover {
            background: #2b2b2b;
            border-color: #444;
        }
        .admin-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #ff4444;
            cursor: pointer;
        }
        .btn-admin-submit {
            background: #ff4444;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .btn-admin-submit:hover {
            background: #cc2424;
        }
        .badge-status {
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-locked { background: #d9534f; color: #fff; }
        .status-free { background: #5cb85c; color: #fff; }
    </style>
</head>
<body>
    
    <?php include("../_inc/header.php"); ?>

    <main id="main" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <a href="dashboard" style="color: #aaa; text-decoration: none; font-size: 14px;">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
            <a href="/profile/profil?steamid=<?= htmlspecialchars($target_steamid) ?>" style="color: #3498db; text-decoration: none; font-size: 14px;">
                Voir le profil public <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </a>
        </div>

        <div class="admin-header" style="border-bottom: 2px solid #ff4444; padding-bottom: 15px; margin-bottom: 30px;">
            <h2 style="color: #ff4444; margin: 0;"><i class="fa-solid fa-user-gear"></i> Édition administrative du compte</h2>
        </div>

        <div class="player-profile-header">
            <img src="<?= htmlspecialchars($target_player['avatar'] ?? '../_img/default_avatar.jpg') ?>" alt="Avatar" class="player-avatar">
            <div>
                <h3 style="margin: 0 0 5px 0; color: #fff; font-size: 20px;"><?= htmlspecialchars($final_name) ?></h3>
                <span style="font-family: monospace; color: #888; font-size: 13px;">SteamID64 : <?= htmlspecialchars($target_steamid) ?></span>
            </div>
        </div>

        <div class="admin-card">
            <form action="_scripts/admin_process.php" method="POST" class="flex flex-column gap-10">
                <input type="hidden" name="target_steamid" value="<?= htmlspecialchars($target_steamid) ?>">
                
                <div class="form-section">
                    <h3><i class="fa-solid fa-users-viewfinder"></i> Gestion des rôles Staff</h3>
                    <div class="checkbox-group">
                        <label class="admin-label">
                            <input type="checkbox" name="is_founder" value="1" <?= (int)$target_player['is_founder'] === 1 ? 'checked' : '' ?>>
                            <span>Fondateur</span>
                        </label>
                        
                        <label class="admin-label">
                            <input type="checkbox" name="is_moderator" value="1" <?= (int)$target_player['is_moderator'] === 1 ? 'checked' : '' ?>>
                            <span>Modérateur</span>
                        </label>
                        
                        <label class="admin-label">
                            <input type="checkbox" name="is_mentor" value="1" <?= (int)$target_player['is_mentor'] === 1 ? 'checked' : '' ?>>
                            <span>Mentor</span>
                        </label>
                        
                        <label class="admin-label">
                            <input type="checkbox" name="is_mixer" value="1" <?= (int)$target_player['is_mixer'] === 1 ? 'checked' : '' ?>>
                            <span>Lanceur de Mix</span>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fa-solid fa-shield-halved"></i> Modération avancée</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <label class="admin-label" style="justify-content: space-between; width: 100%; box-sizing: border-box;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="reset_name_change" value="1">
                                <div>
                                    <strong>Débloquer le nom d'affichage</strong><br>
                                    <span style="font-size: 12px; color: #aaa;">Permet au joueur de modifier à nouveau son pseudo unique depuis son dashboard.</span>
                                </div>
                            </div>
                            <div>
                                <?php if ((int)$target_player['name_changed'] === 1): ?>
                                    <span class="badge-status status-locked">Utilisé / Bloqué</span>
                                <?php else: ?>
                                    <span class="badge-status status-free">Libre</span>
                                <?php endif; ?>
                            </div>
                        </label>
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <button type="submit" class="btn-admin-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
</body>
</html>