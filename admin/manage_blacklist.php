<?php
require_once __DIR__ . "/../_inc/config.php";
require_once __DIR__ . "/../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : accès admin strict
checkAdminOrDie();

$blacklist = getBlacklist($db);
$totalBlacklisted = count($blacklist);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion des logs blacklistés</title>
    <link rel="stylesheet" href="../_css/main.css">
    <link rel="stylesheet" href="_css/admin.css">
</head>

<body>

    <?php include("../_inc/header.php"); ?>

    <main id="main" style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">

        <div style="margin-bottom: 20px;">
            <a href="dashboard" style="color: #aaa; text-decoration: none; font-size: 14px;">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: #1c3d27; color: #2ecc71; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['success']);
                                                            unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: #3d1c1c; color: #e74c3c; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']);
                                                            unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="admin-header" style="border-bottom: 2px solid #f35f5f; padding-bottom: 15px; margin-bottom: 30px;">
            <h2 style="color: #f35f5f; margin: 0;"><i class="fa-solid fa-ban"></i> Gestion des logs blacklistés</h2>
            <p style="margin: 5px 0 0 0; color: #aaa;">
                Les logs présents ici sont exclus des Match Stats, des stats de l'accueil et des statistiques joueurs.
            </p>
        </div>

        <div class="admin-card" style="margin-top: 0;">
            <h3 style="color: #fff; border-bottom: 1px solid #2b2b2b; padding-bottom: 8px; margin-bottom: 15px; font-size: 16px; text-transform: uppercase;">
                <i class="fa-solid fa-plus"></i> Ajouter un log à la blacklist
            </h3>
            <form action="_scripts/admin_blacklist.php" method="POST" style="display: flex; flex-direction: column; gap: 12px; max-width: 480px;">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="log_id" style="font-size: 14px; font-weight: bold; color: #ccc;">ID du log (logs.tf) :</label>
                    <input type="text" name="log_id" id="log_id" class="form-control" placeholder="Ex : 4062936" pattern="[0-9]+" required>
                </div>
                <div class="form-group">
                    <label for="reason" style="font-size: 14px; font-weight: bold; color: #ccc;">Raison (facultatif) :</label>
                    <input type="text" name="reason" id="reason" class="form-control" placeholder="Ex : Log de test, stats fausses...">
                </div>
                <div>
                    <button type="submit" style="background: #f35f5f; color: #fff; padding: 10px 16px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                        <i class="fa-solid fa-ban"></i> Blacklister ce log
                    </button>
                </div>
            </form>
        </div>

        <div class="admin-card" style="margin-top: 20px;">
            <h3 style="color: #fff; border-bottom: 1px solid #2b2b2b; padding-bottom: 8px; margin-bottom: 15px; font-size: 16px; text-transform: uppercase; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-list"></i> Logs blacklistés
                <span style="background: #f35f5f; color: #fff; font-size: 12px; padding: 2px 8px; border-radius: 10px; margin-left: auto;">
                    <?= $totalBlacklisted ?>
                </span>
            </h3>

            <?php if (empty($blacklist)): ?>
                <p style="color: #aaa; font-style: italic; font-size: 14px;">Aucun log blacklisté pour le moment.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Raison</th>
                            <th>Ajouté par</th>
                            <th>Date</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blacklist as $entry): $adminName = getAdminDisplayName($db, $entry['added_by'] ?? ''); ?>
                            <tr>
                                <td>
                                    <a href="https://logs.tf/<?= (int)$entry['log_id'] ?>" target="_blank" style="color: #3498db; font-family: monospace;">
                                        <?= (int)$entry['log_id'] ?>
                                    </a>
                                </td>
                                <td style="color: #ccc;"><?= htmlspecialchars($entry['reason'] ?: '—') ?></td>
                                <td style="color: #aaa; font-size: 13px;">
                                    <?= htmlspecialchars($adminName ?: '—') ?>
                                    <?php if (!empty($entry['added_by']) && preg_match('/^\d{17}$/', $entry['added_by'])): ?>
                                        <i class="fa-solid fa-circle-info" style="color: #555; cursor: help; margin-left: 4px;"
                                            title="SteamID64 : <?= htmlspecialchars($entry['added_by']) ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #aaa; font-size: 13px;"><?= $entry['created_at'] ? date('d/m/Y H:i', strtotime($entry['created_at'])) : '—' ?></td>
                                <td style="text-align: center;">
                                    <form action="_scripts/admin_blacklist.php" method="POST" style="display: inline;"
                                        onsubmit="return confirm('Retirer le log #<?= (int)$entry['log_id'] ?> de la blacklist ?');">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="log_id" value="<?= (int)$entry['log_id'] ?>">
                                        <button type="submit" style="background: #2b2b2b; border: 1px solid #444; color: #5cb85c; padding: 5px 10px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                            <i class="fa-solid fa-rotate-left"></i> Restaurer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </main>

    <?php include("../_inc/footer.php"); ?>

    <script src="https://kit.fontawesome.com/2f306d349c.js" crossorigin="anonymous"></script>
</body>

</html>