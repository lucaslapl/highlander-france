<?php
// On lit le fichier cache généré par le CRON
$json_content = file_get_contents(__DIR__ . '/cache_hlfr_stats.json');
$stats = json_decode($json_content, true);

// On crée une réponse structurée avec la clé "data" attendue par votre JS
$response = [
    'data' => $stats // Ici on enveloppe les données existantes
];

// On envoie le JSON final
header('Content-Type: application/json');
echo json_encode($response);
?>