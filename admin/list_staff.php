<?php
require_once __DIR__ . "/../_inc/config.php";
require_once __DIR__ . "/../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : Si le visiteur n'est pas admin, le script meurt ici immédiatement.
checkAdminOrDie();

try {
    // Récupération de tous les membres de l'équipe (ceux qui ont au moins un rôle à 1)
    $stmt = $db->query("
        SELECT steamid, name, display_name, avatar, is_founder, is_moderator, is_mentor, is_mixer, is_admin 
        FROM players_info 
        WHERE is_founder = 1 OR is_moderator = 1 OR is_mentor = 1 OR is_mixer = 1 OR is_admin = 1
        ORDER BY is_admin DESC, display_name ASC, name ASC
    ");
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $staff_members = [];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Liste de l'équipe</title>
    <link rel="stylesheet" href="../_css/main.css">
    <link rel="stylesheet" href="_css/admin.css">
</head>

<body>

    <?php include("../_inc/header.php"); ?>

    <main id="main" class="admin-main">

        <div class="admin-back">
            <a href="dashboard">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
        </div>

        <div class="admin-header" style="--accent: #00bc8c;">
            <h2><i class="fa-solid fa-user-shield"></i> Gestion de l'équipe staff</h2>
            <p>Vue d'ensemble de tous les comptes possédant un rang particulier sur Highlander France.</p>
        </div>

        <?php if (empty($staff_members)): ?>
            <div class="admin-empty">
                Aucun membre du staff trouvé dans la base de données.
            </div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Membre</th>
                        <th>SteamID64</th>
                        <th>Rôles actifs</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_members as $member): ?>
                        <?php
                        $steamid64 = steamID3ToSteamID64($member['steamid']);
                        $final_name = !empty($member['display_name']) ? $member['display_name'] : $member['name'];
                        ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($member['avatar']) ?>" alt="Avatar" class="staff-avatar">
                                <strong style="color: #fff;"><?= htmlspecialchars($final_name) ?></strong>
                            </td>

                            <td style="font-family: monospace; color: #aaa; font-size: 13px;">
                                <?= htmlspecialchars($steamid64) ?>
                            </td>

                            <td>
                                <?= (int)$member['is_admin'] === 1 ? '<span class="badge badge-admin">ADMIN</span>' : '<span class="badge badge-disabled">ADMIN</span>' ?>
                                <?= (int)$member['is_founder'] === 1 ? '<span class="badge badge-founder">FONDATEUR</span>' : '<span class="badge badge-disabled">FONDATEUR</span>' ?>
                                <?= (int)$member['is_moderator'] === 1 ? '<span class="badge badge-moderator">MODO</span>' : '<span class="badge badge-disabled">MODO</span>' ?>
                                <?= (int)$member['is_mentor'] === 1 ? '<span class="badge badge-mentor">MENTOR</span>' : '<span class="badge badge-disabled">MENTOR</span>' ?>
                                <?= (int)$member['is_mixer'] === 1 ? '<span class="badge badge-mixer">MIXER</span>' : '<span class="badge badge-disabled">MIXER</span>' ?>
                            </td>

                            <td class="text-center">
                                <a href="manage_player.php?steamid=<?= $steamid64 ?>" class="admin-btn">
                                    <i class="fa-solid fa-user-gear" style="color: #ff4444;"></i> Modifier les rôles
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
</body>

</html>