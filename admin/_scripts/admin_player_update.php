<?php
require_once __DIR__ . "/../../_inc/config.php";
require_once __DIR__ . "/../../_inc/functions.php";

// 🔥 SÉCURITÉ CRITIQUE : Si le visiteur n'est pas admin, le script meurt ici immédiatement.
checkAdminOrDie();

// On accepte uniquement les requêtes en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Méthode non autorisée.");
}

// 1. Récupération et sécurisation du SteamID cible
$target_steamid = $_POST['target_steamid'] ?? null;

if (!$target_steamid || !preg_match('/^\d{17}$/', $target_steamid)) {
    $_SESSION['error'] = "Erreur : SteamID64 invalide.";
    header("Location: ../index.php");
    exit();
}

// Conversion en SteamID3 pour correspondre à ta structure SQLite
$target_steamid3 = steamID64ToSteamID3($target_steamid);

// 2. Récupération et nettoyage des champs texte
$display_name = trim($_POST['display_name'] ?? '');
$country      = strtolower(trim($_POST['country'] ?? 'unknown'));

// Validation rapide du pseudo d'affichage
if (empty($display_name)) {
    $_SESSION['error'] = "Le pseudo d'affichage ne peut pas être vide.";
    header("Location: ../manage_player.php?steamid=" . urlencode($target_steamid));
    exit();
}

// 3. Récupération des cases à cocher (les rôles Staff)
// Si la case est cochée, la valeur vaut 1, sinon elle vaut 0
$is_founder   = isset($_POST['is_founder']) ? 1 : 0;
$is_moderator = isset($_POST['is_moderator']) ? 1 : 0;
$is_mentor    = isset($_POST['is_mentor']) ? 1 : 0;
$is_mixer     = isset($_POST['is_mixer']) ? 1 : 0;

// Vérification de la case de réinitialisation du pseudo et pays
$reset_name_change = isset($_POST['reset_name_change']) ? 1 : 0;
$reset_country_change = isset($_POST['reset_country_change']) ? 1 : 0;

try {
    // 4. Préparation de la requête SQL de mise à jour globale
    $sql = "UPDATE players_info 
            SET display_name = ?, 
                country = ?, 
                is_founder = ?, 
                is_moderator = ?, 
                is_mentor = ?, 
                is_mixer = ?";
    
    $params = [$display_name, $country, $is_founder, $is_moderator, $is_mentor, $is_mixer];

    // Si l'admin a coché la réinitialisation forcée, on repasse 'name_changed' à 0
    if ($reset_name_change === 1) {
        $sql .= ", name_changed = 0";
    }

    // Si l'admin a coché la réinitialisation forcée du pays, on repasse 'country_locked' à 0
    if ($reset_country_change === 1) {
        $sql .= ", country_locked = 0";
    }

    // Condition finale pour cibler le bon joueur
    $sql .= " WHERE steamid = ?";
    $params[] = $target_steamid3;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // On vérifie si la ligne a bien été modifiée (ou si le joueur existe)
    if ($stmt->rowCount() >= 0) {
        $_SESSION['success'] = "Le profil de " . htmlspecialchars($display_name) . " a été mis à jour avec succès !";
    } else {
        $_SESSION['error'] = "Le joueur est introuvable ou aucune modification n'a été détectée.";
    }

} catch (PDOException $e) {
    // En cas d'erreur de base de données (ex: table verrouillée ou colonne manquante)
    $_SESSION['error'] = "Erreur BDD lors de l'enregistrement : " . $e->getMessage();
}

// 5. Redirection vers la page d'édition avec le feedback visuel
header("Location: ../manage_player.php?steamid=" . urlencode($target_steamid));
exit();