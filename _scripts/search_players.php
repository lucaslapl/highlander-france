<?php
header('Content-Type: application/json');

// 1. Inclusion de la config pour avoir accès à la variable $db
require_once __DIR__ . '/../_inc/config.php';
require_once __DIR__ . '/../_inc/functions.php';

// 2. Récupération et nettoyage du paramètre de recherche 'q'
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Si la recherche est vide ou trop courte, on renvoie une liste vide pour économiser le serveur
if (strlen($searchQuery) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // 3. Préparation de la requête avec LIKE
    // On cherche dans 'name' (nom Steam) OU 'display_name' (nom personnalisé sur ton site)
    // Le symbole % signifie "n'importe quel caractère avant ou après"
    $stmt = $db->prepare("
        SELECT 
            steamid, 
            name, 
            display_name, 
            avatar 
        FROM players_info 
        WHERE name LIKE :query OR display_name LIKE :query
        ORDER BY display_name ASC, name ASC
        LIMIT 10
    ");

    $stmt->execute([
        ':query' => '%' . $searchQuery . '%'
    ]);

    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($players as $player) {
        // On détermine le nom à afficher (priorité au display_name s'il existe)
        $displayName = !empty($player['display_name']) ? $player['display_name'] : $player['name'];

        $results[] = [
            'steamid' => steamID3toSteamID64($player['steamid']),
            'name' => $displayName,
            'avatar' => $player['avatar']
        ];
    }

    // 4. Renvoi du résultat en JSON
    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la recherche en base de données.']);
}