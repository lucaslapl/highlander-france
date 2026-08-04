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

    <main id="main" class="admin-main">

        <div class="admin-back">
            <a href="dashboard">
                <i class="fa-solid fa-arrow-left"></i> Retour au Panel Admin
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="admin-alert admin-alert--success">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['success']);
                                                            unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="admin-alert admin-alert--error">
                <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($_SESSION['error']);
                                                            unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="admin-header" style="--accent: #f35f5f;">
            <h2><i class="fa-solid fa-ban"></i> Gestion des logs blacklistés</h2>
            <p>
                Les logs présents ici sont exclus des Match Stats, des stats de l'accueil et des statistiques joueurs.
            </p>
        </div>

        <div class="admin-card">
            <h3 class="admin-card__title">
                <i class="fa-solid fa-plus"></i> Ajouter un log à la blacklist
            </h3>
            <form action="_scripts/admin_blacklist.php" method="POST" class="admin-form-stack">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="log_id">ID du log (logs.tf) :</label>
                    <input type="text" name="log_id" id="log_id" class="form-control" placeholder="Ex : 4062936" pattern="[0-9]+" required>
                </div>
                <div class="form-group">
                    <label for="reason">Raison (facultatif) :</label>
                    <input type="text" name="reason" id="reason" class="form-control" placeholder="Ex : Log de test, stats fausses...">
                </div>
                <div>
                    <button type="submit" class="admin-btn admin-btn--primary" style="--accent: #f35f5f;">
                        <i class="fa-solid fa-ban"></i> Blacklister ce log
                    </button>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <h3 class="admin-card__title">
                <i class="fa-solid fa-list"></i> Logs blacklistés
                <span class="status-pill" style="--accent: #f35f5f;">
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
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blacklist as $entry): $adminName = getAdminDisplayName($db, $entry['added_by'] ?? ''); ?>
                            <tr>
                                <td>
                                    <a href="https://logs.tf/<?= (int)$entry['log_id'] ?>" target="_blank" class="admin-mono">
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
                                <td class="text-center">
                                    <form action="_scripts/admin_blacklist.php" method="POST" style="display: inline;"
                                        onsubmit="return confirm('Retirer le log #<?= (int)$entry['log_id'] ?> de la blacklist ?');">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="log_id" value="<?= (int)$entry['log_id'] ?>">
                                        <button type="submit" class="admin-btn admin-btn--success">
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