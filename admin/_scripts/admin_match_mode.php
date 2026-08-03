<?php
require_once __DIR__ . "/../../_inc/config.php";
require_once __DIR__ . "/../../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : accès admin strict
checkAdminOrDie();

// On accepte uniquement les requêtes en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Méthode non autorisée.");
}

$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

function respond($success, $message) {
    global $isAjax;
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    if ($success) {
        $_SESSION['success'] = $message;
    } else {
        $_SESSION['error'] = $message;
    }
    header("Location: ../match_logs.php");
    exit;
}

$action = $_POST['action'] ?? '';
$log_id = $_POST['log_id'] ?? '';
$mode   = strtolower(trim($_POST['mode'] ?? ''));

if (!ctype_digit($log_id)) {
    respond(false, "ID de log invalide.");
}
if (!in_array($mode, ['6s', '9v9'], true)) {
    respond(false, "Mode de jeu invalide (6s ou 9v9 attendu).");
}
$log_id = (int)$log_id;

try {
    if ($action !== 'switch_mode') {
        respond(false, "Action non reconnue.");
    }

    // Mode actuellement stocké en base pour ce log
    $stmt = $db->prepare("SELECT game_mode FROM player_matches WHERE match_id = ? LIMIT 1");
    $stmt->execute([$log_id]);
    $current = $stmt->fetchColumn();

    if ($current === false) {
        respond(false, "Ce log n'est pas encore traité en base de données (aucun joueur associé).");
    }
    if ($current === $mode) {
        respond(false, "Le log #$log_id est déjà en mode $mode.");
    }

    // Joueurs présents sur ce log
    $stmtPlayers = $db->prepare("SELECT steamid FROM player_matches WHERE match_id = ?");
    $stmtPlayers->execute([$log_id]);
    $steamids = $stmtPlayers->fetchAll(PDO::FETCH_COLUMN);

    $db->beginTransaction();

    // 1. Bascule du mode sur toutes les lignes du log
    $db->prepare("UPDATE player_matches SET game_mode = ? WHERE match_id = ?")
       ->execute([$mode, $log_id]);

    // 2. Ajustement des compteurs joueurs (même logique que le purge de update_stats.php)
    $dec = $db->prepare("UPDATE player_stats SET count = count - 1 WHERE steamid = ? AND game_mode = ?");
    $inc = $db->prepare("INSERT INTO player_stats (steamid, count, game_mode) VALUES (?, 1, ?)
                         ON CONFLICT(steamid, game_mode) DO UPDATE SET count = count + 1");
    foreach ($steamids as $steamid) {
        $dec->execute([$steamid, $current]);
        $inc->execute([$steamid, $mode]);
    }
    $db->exec("DELETE FROM player_stats WHERE count <= 0");

    $db->commit();

    respond(true, "Le log #$log_id est passé du mode $current au mode $mode.");
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    respond(false, "Erreur BDD : " . $e->getMessage());
}