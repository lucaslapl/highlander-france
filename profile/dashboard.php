<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 1. Protection : Si pas de session, on renvoie à la connexion
if (!isset($_SESSION['steamid'])) {
    header('Location: ../login.php');
    exit;
}

// 2. Connexion à la base
$db = new PDO('sqlite:' . __DIR__ . '/../_scripts/stats.db');

// 3. Récupération des infos du joueur connecté
$stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
$stmt->execute([$_SESSION['steamid']]);
$user = $stmt->fetch();

// 4. Affichage
?>
<h1>Bienvenue, <?php echo htmlspecialchars($user['display_name'] ?? 'Joueur'); ?> !</h1>
<p>Votre SteamID : <?php echo $_SESSION['steamid']; ?></p>

<br>

<?php if (isset($_GET['success'])): ?>
    <div style="color: green; margin-bottom: 10px;">
        Votre pseudo a bien été mis à jour !
    </div>
<?php endif; ?>

<form action="update_profile.php" method="POST">
    <label>Mon nom d'affichage :</label>
    <?php
    // On sécurise : si $user n'est pas un tableau (donc false), on met une chaîne vide
    $valeur_nom = ($user !== false && isset($user['display_name'])) ? $user['display_name'] : '';
    ?>

    <input type="text" name="display_name" value="<?php echo htmlspecialchars($valeur_nom); ?>">
    <button type="submit">Enregistrer</button>
</form>

<a href="../logout.php">Déconnexion</a>