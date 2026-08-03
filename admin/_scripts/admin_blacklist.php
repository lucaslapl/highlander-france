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
    header("Location: ../manage_blacklist.php");
    exit;
}

$action = $_POST['action'] ?? '';
$log_id = $_POST['log_id'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (!ctype_digit($log_id)) {
    respond(false, "ID de log invalide.");
}

$log_id = (int)$log_id;
$steamid64 = $_SESSION['steamid'] ?? 'Inconnu';

try {
    if ($action === 'add') {
        $stmt = $db->prepare("INSERT OR IGNORE INTO log_blacklist (log_id, reason, added_by) VALUES (?, ?, ?)");
        $stmt->execute([$log_id, $reason !== '' ? $reason : null, $steamid64]);

        if ($stmt->rowCount() > 0) {
            respond(true, "Le log #$log_id a été blacklisté avec succès.");
        } else {
            respond(false, "Le log #$log_id est déjà blacklisté.");
        }
    } elseif ($action === 'remove') {
        $stmt = $db->prepare("DELETE FROM log_blacklist WHERE log_id = ?");
        $stmt->execute([$log_id]);

        if ($stmt->rowCount() > 0) {
            respond(true, "Le log #$log_id a été retiré de la blacklist.");
        } else {
            respond(false, "Le log #$log_id n'est pas dans la blacklist.");
        }
    } else {
        respond(false, "Action non reconnue.");
    }
} catch (PDOException $e) {
    respond(false, "Erreur BDD : " . $e->getMessage());
}