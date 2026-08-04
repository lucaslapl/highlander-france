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

// Nom final pour l'affichage de l'en-tête
$final_name = !empty($target_player['display_name']) ? $target_player['display_name'] : $target_player['name'];

// Liste des pays pour le menu déroulant (Value ISO minuscule => Nom affiché)
$countries_list = [
    'fr' => 'France',
    'be' => 'Belgique',
    'sw' => 'Suisse',
    'lu' => 'Luxembourg',
    'uk' => 'Royaume-Uni',
    'eu' => 'Europe',
    'al' => 'Algérie',
    'mo' => 'Maroc',
    'tu' => 'Tunisie',
    'ca' => 'Canada',
    'breizh' => 'Bretagne',
];

// On récupère la nationalité actuelle du joueur (mise en minuscule pour correspondre au tableau)
$current_country = !empty($target_player['country']) ? strtolower($target_player['country']) : 'unknown';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gérer <?= htmlspecialchars($final_name) ?></title>
    <link rel="stylesheet" href="../_css/main.css">
    <link rel="stylesheet" href="_css/admin.css">
</head>

<body>

    <?php include("../_inc/header.php"); ?>

    <main id="main" class="admin-main">

        <div class="admin-back admin-back--split">
            <a href="dashboard">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
            <a href="/profile/profil?steamid=<?= htmlspecialchars($target_steamid) ?>">
                Voir le profil public <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </a>
        </div>

        <div class="admin-header" style="--accent: #ff4444;">
            <h2><i class="fa-solid fa-user-gear"></i> Panel d'édition de compte utilisateur</h2>
        </div>

        <div class="player-profile-header">
            <img src="<?= htmlspecialchars($target_player['avatar'] ?? '../_img/default_avatar.jpg') ?>" alt="Avatar" class="player-avatar">
            <div>
                <h3 style="margin: 0 0 5px 0; color: #fff; font-size: 20px;"><?= htmlspecialchars($final_name) ?></h3>
                <p style="font-family: monospace; color: #888; font-size: 13px;">SteamID64 : <?= htmlspecialchars($target_steamid) ?></p>
                <p style="font-family: monospace; color: #888; font-size: 13px;">SteamID3 : <?= htmlspecialchars($target_player['steamid']) ?></p>
                <span style="display: block; margin-top: 5px; font-size: 12px; color: #aaa;">
                    <?= (int)$target_player['is_admin'] === 1 ? '<span class="badge badge-admin">Admin</span>' : '' ?>
                    <?= (int)$target_player['is_founder'] === 1 ? '<span class="badge badge-founder">Fondateur</span>' : '' ?>
                    <?= (int)$target_player['is_moderator'] === 1 ? '<span class="badge badge-moderator">Modérateur</span>' : '' ?>
                    <?= (int)$target_player['is_mentor'] === 1 ? '<span class="badge badge-mentor">Mentor</span>' : '' ?>
                    <?= (int)$target_player['is_mixer'] === 1 ? '<span class="badge badge-mixer">Lanceur de Mix</span>' : '' ?>
                </span>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="admin-alert admin-alert--success">
                <i class="fa-solid fa-circle-check"></i> <?= $_SESSION['success'];
                                                            unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="admin-alert admin-alert--error">
                <i class="fa-solid fa-circle-xmark"></i> <?= $_SESSION['error'];
                                                            unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <form action="_scripts/admin_player_update.php" method="POST">
                <input type="hidden" name="target_steamid" value="<?= htmlspecialchars($target_steamid) ?>">

                <div class="form-section">
                    <h3><i class="fa-solid fa-id-card"></i> Informations du Profil</h3>
                    <div class="form-grid-2">

                        <div class="form-group">
                            <label for="display_name">Pseudo enregistré sur le site :</label>
                            <input type="text" name="display_name" id="display_name" class="form-control"
                                value="<?= htmlspecialchars($target_player['display_name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="country">Nationalité :</label>
                            <select name="country" id="country" class="form-control">
                                <?php foreach ($countries_list as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $current_country === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php array_key_exists($current_country, $countries_list) ? '' : '';
                                endforeach; ?>

                                <?php if (!array_key_exists($current_country, $countries_list) && !empty($target_player['country'])): ?>
                                    <option value="<?= htmlspecialchars($current_country) ?>" selected>
                                        <?= htmlspecialchars(ucfirst($target_player['country'])) ?> (Actuel)
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>

                    </div>
                </div>

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
                    <div style="display: flex; flex-direction: column; gap: 12px;">

                        <label class="admin-label" style="justify-content: space-between; width: 100%; box-sizing: border-box;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="reset_name_change" value="1">
                                <div>
                                    <strong>Forcer la réinitialisation du changement de pseudo</strong><br>
                                    <span style="font-size: 12px; color: #aaa;">Cocher la case pour permettre au joueur de modifier de lui-même à nouveau son pseudo depuis son profil.</span>
                                </div>
                            </div>
                            <div>
                                <?php if ((int)$target_player['name_changed'] === 1): ?>
                                    <span class="badge-status" style="background: #d9534f; color: #fff;">Déjà utilisé</span>
                                <?php else: ?>
                                    <span class="badge-status" style="background: #5cb85c; color: #fff;">Libre</span>
                                <?php endif; ?>
                            </div>
                        </label>

                        <label class="admin-label" style="justify-content: space-between; width: 100%; box-sizing: border-box;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="reset_country_change" value="1">
                                <div>
                                    <strong>Forcer la réinitialisation du changement de nationalité</strong><br>
                                    <span style="font-size: 12px; color: #aaa;">Cocher la case pour permettre au joueur de modifier de lui-même à nouveau son drapeau/pays depuis son profil.</span>
                                </div>
                            </div>
                            <div>
                                <?php if (isset($target_player['country_locked']) && (int)$target_player['country_locked'] === 1): ?>
                                    <span class="badge-status" style="background: #d9534f; color: #fff;">Déjà utilisé</span>
                                <?php else: ?>
                                    <span class="badge-status" style="background: #5cb85c; color: #fff;">Libre</span>
                                <?php endif; ?>
                            </div>
                        </label>

                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <button type="submit" class="admin-btn admin-btn--primary">
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