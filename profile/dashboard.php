<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// On charge la configuration et les fonctions
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// 1. Protection : Si pas de session, on renvoie à la connexion
if (!isset($_SESSION['steamid'])) {
    header('Location: ../login.php');
    exit;
}

$env = parse_ini_file(__DIR__ . '/../_inc/.env');
$STEAM_API_KEY = $env['STEAM_API_KEY'];

$steamid64 = $_SESSION['steamid']; // ou $_GET['steamid']

// 2. Conversion vers le format stocké en base de données
$steamid3 = steamID64ToSteamID3($steamid64);

// 3. Récupération des infos du joueur connecté
$stmt = $db->prepare("SELECT * FROM players_info WHERE steamid = ?");
$stmt->execute([$steamid3]);
$user = $stmt->fetch();
//S'il n'existe pas, on l'ajoute
if ($user === false) {
    try {
        $insert = $db->prepare("INSERT INTO players_info (steamid, display_name) VALUES (?, ?)");
        $insert->execute([$steamid3, 'Nouveau Joueur']);
        
        // On recharge les données
        $stmt->execute([$steamid3]);
        $user = $stmt->fetch();
        echo "";
    } catch (PDOException $e) {
        die("Erreur lors de l'insertion : " . $e->getMessage());
    }
}

// 4. Maintenant que $user est défini, on vérifie si on doit synchroniser
// Note : on convertit last_updated en entier pour être sûr
$last_update = (int)($user['last_updated'] ?? 0);

if (empty($user['name']) || ($last_update < time() - 86400)) {
    // Appel de la fonction de synchro
    syncSteamProfile($steamid3, $db, $STEAM_API_KEY);
    
    // On recharge les données car elles ont changé en base
    $stmt->execute([$steamid3]);
    $user = $stmt->fetch();
}

// 5. Affichage
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