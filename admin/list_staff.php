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
    <style>
        /* Quelques styles rapides dédiés au tableau d'administration */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #1a1a1a;
            border-radius: 6px;
            overflow: hidden;
        }
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #2b2b2b;
        }
        .admin-table th {
            background-color: #222;
            color: #ff4444;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
        }
        .admin-table tr:hover {
            background-color: #222;
        }
        .staff-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            vertical-align: middle;
            margin-right: 10px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 3px;
            margin-right: 4px;
            color: #fff;
            background: #444;
        }
        .badge-admin     { background-color: #d9534f; } /* Rouge */
        .badge-founder   { background-color: #f0ad4e; } /* Orange */
        .badge-moderator { background-color: #5bc0de; } /* Bleu ciel */
        .badge-mentor    { background-color: #5cb85c; } /* Vert */
        .badge-mixer     { background-color: #9b59b6; } /* Violet */
        
        .badge-disabled {
            background-color: #2b2b2b;
            color: #555;
            text-decoration: line-through;
            font-weight: normal;
        }
    </style>
</head>
<body>

    <?php include("../_inc/header.php"); ?>

    <main id="main" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        
        <div style="margin-bottom: 20px;">
            <a href="dashboard" style="color: #aaa; text-decoration: none; font-size: 14px;">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
        </div>

        <div class="admin-header" style="border-bottom: 2px solid #00bc8c; padding-bottom: 15px; margin-bottom: 30px;">
            <h2 style="color: #00bc8c; margin: 0;"><i class="fa-solid fa-user-shield"></i> Gestion de l'équipe complète</h2>
            <p style="margin: 5px 0 0 0; color: #aaa;">Vue d'ensemble de tous les comptes possédant des privilèges sur Highlander France.</p>
        </div>

        <?php if (empty($staff_members)): ?>
            <div style="background: #1a1a1a; padding: 20px; border-radius: 4px; text-align: center; color: #aaa;">
                Aucun membre du staff trouvé dans la base de données.
            </div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Membre</th>
                        <th>SteamID64</th>
                        <th>Rôles actifs</th>
                        <th style="text-align: center;">Actions</th>
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
                            
                            <td style="text-align: center;">
                                <a href="manage_player.php?steamid=<?= $steamid64 ?>" style="background: #222; border: 1px solid #444; color: #fff; text-decoration: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; display: inline-block;">
                                    <i class="fa-solid fa-user-gear" style="color: #ff4444;"></i> Modifier les droits
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script></body>
</html>