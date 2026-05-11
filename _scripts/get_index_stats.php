<?php
// get cache generated from CRON job
$json_content = file_get_contents(__DIR__ . '/cache_hlfr_stats.json');
$stats = json_decode($json_content, true);

$response = [
    'data' => $stats
];

header('Content-Type: application/json');
echo json_encode($response);
?>