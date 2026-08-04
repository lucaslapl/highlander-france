<?php
/**
 * Statut des API externes (ETF2L, logs.tf, Steam) pour le dashboard admin.
 * Vérifications en direct via cURL, mises en cache quelques secondes.
 */

if (!defined('API_STATUS_CACHE_FILE')) {
    define('API_STATUS_CACHE_FILE', __DIR__ . '/../_scripts/api_status_cache.json');
}
if (!defined('API_STATUS_CACHE_TTL')) {
    define('API_STATUS_CACHE_TTL', 60); // secondes avant une nouvelle vérification
}
if (!defined('API_STATUS_SLOW_MS')) {
    define('API_STATUS_SLOW_MS', 2000); // latence au-delà de laquelle l'API est jugée "lente"
}

/**
 * Requête cURL courte et mesurée.
 */
function apiStatusCurl($url, $timeout = 5) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false, // environnement WAMP ; passer à true en production
        CURLOPT_USERAGENT      => 'Highlander France Bot/1.0',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body  = curl_exec($ch);
    $info  = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'body'       => $body,
        'http_code'  => (int)($info['http_code'] ?? 0),
        'latency_ms' => (int)round(($info['total_time'] ?? 0) * 1000),
        'error'      => $error,
    ];
}

/**
 * Clé Steam, lue depuis _scripts/.env (fallback _inc/.env). Jamais affichée.
 */
function apiGetSteamKey() {
    foreach ([__DIR__ . '/../_scripts/.env', __DIR__ . '/.env'] as $envFile) {
        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);
            if (!empty($env['STEAM_API_KEY'])) {
                return trim($env['STEAM_API_KEY']);
            }
        }
    }
    return '';
}

function apiEvalStatus($httpCode, $valid, $latencyMs) {
    if (!$valid || $httpCode <= 0) {
        return 'down';
    }
    if ($latencyMs > API_STATUS_SLOW_MS) {
        return 'slow';
    }
    return 'ok';
}

function apiMessage($valid, $r) {
    if ($valid) {
        return 'API opérationnelle';
    }
    if (!empty($r['error'])) {
        return 'Erreur cURL : ' . $r['error'];
    }
    if ($r['http_code'] > 0) {
        return 'Réponse invalide (HTTP ' . $r['http_code'] . ')';
    }
    return 'Connexion impossible';
}

/**
 * Vérification en direct des 3 API (mêmes endpoints que les scripts de synchro).
 */
function getApiChecks() {
    $checks = [];

    // --- ETF2L ---
    $r = apiStatusCurl('https://api-v2.etf2l.org/matches?scheduled=1');
    $data  = json_decode($r['body'], true);
    $valid = $r['http_code'] === 200 && is_array($data['results']['data'] ?? null);
    $checks['etf2l'] = [
        'api'        => 'ETF2L',
        'icon'       => 'fa-solid fa-flag-checkered',
        'status'     => apiEvalStatus($r['http_code'], $valid, $r['latency_ms']),
        'http_code'  => $r['http_code'],
        'latency_ms' => $r['latency_ms'],
        'message'    => apiMessage($valid, $r),
        'script'     => 'sync_etf2l.php',
    ];

    // --- logs.tf ---
    $r = apiStatusCurl('https://logs.tf/api/v1/log?title=Highlander%20France&limit=1');
    $data  = json_decode($r['body'], true);
    $valid = $r['http_code'] === 200 && is_array($data['logs'] ?? null);
    $checks['logstf'] = [
        'api'        => 'LOGS.TF',
        'icon'       => 'fa-solid fa-fire',
        'status'     => apiEvalStatus($r['http_code'], $valid, $r['latency_ms']),
        'http_code'  => $r['http_code'],
        'latency_ms' => $r['latency_ms'],
        'message'    => apiMessage($valid, $r),
        'script'     => 'update_stats.php',
    ];

    // --- Steam ---
    $key = apiGetSteamKey();
    if ($key === '') {
        $checks['steam'] = [
            'api'        => 'Steam',
            'icon'       => 'fa-brands fa-steam',
            'status'     => 'error',
            'http_code'  => null,
            'latency_ms' => null,
            'message'    => 'Clé API manquante dans le fichier .env',
            'script'     => 'sync_steam.php',
        ];
    } else {
        $url  = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $key . '&steamids=76561197960265729';
        $r    = apiStatusCurl($url);
        $data = json_decode($r['body'], true);
        $resp = $data['response'] ?? null;
        $valid = $r['http_code'] === 200 && is_array($resp) && empty($resp['error'] ?? null);
        $checks['steam'] = [
            'api'        => 'Steam',
            'icon'       => 'fa-brands fa-steam',
            'status'     => apiEvalStatus($r['http_code'], $valid, $r['latency_ms']),
            'http_code'  => $r['http_code'],
            'latency_ms' => $r['latency_ms'],
            'message'    => $valid
                ? 'API opérationnelle'
                : (is_array($resp) && !empty($resp['error']) ? 'Réponse Steam invalide (clé ?)' : apiMessage($valid, $r)),
            'script'     => 'sync_steam.php',
        ];
    }

    return $checks;
}

/**
 * Dernière exécution (SUCCESS/FAILED) de chaque script dans cron_debug.log.
 */
function apiLastCronRuns() {
    $logFile = __DIR__ . '/../_scripts/cron_debug.log';
    if (!file_exists($logFile)) {
        return [];
    }

    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }
    $lines = array_slice($lines, -2000); // performance sur les gros journaux

    $last = [];
    foreach ($lines as $line) {
        if (!preg_match('/\[SCRIPT:\s*([a-z0-9_\.]+)\]/i', $line, $m)) continue;
        $script = $m[1];

        if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*\[STATUS:(.*?)\]\s*\[SCRIPT:/i', $line, $m2)) continue;
        $status = $m2[2];
        if (strpos($status, 'STARTED') !== false) continue; // exécutions terminées uniquement

        // Les dates du cron_debug.log sont écrites en Europe/Paris (logScriptExecution),
        // même si le PHP web est réglé sur UTC : on force le bon fuseau pour strtotime.
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $m2[1], new DateTimeZone('Europe/Paris'));

        $last[$script] = [
            'status'  => strpos($status, 'FAILED') === 0 ? 'failed' : 'success',
            'message' => $status,
            'date'    => $m2[1],
            'ts'      => $dt ? $dt->getTimestamp() : 0,
        ];
    }
    return $last;
}

/**
 * Point d'entrée : retourne le tableau des statuts (avec cache court).
 */
function getApiStatuses($forceRefresh = false) {
    if (!$forceRefresh && file_exists(API_STATUS_CACHE_FILE)) {
        $cached = json_decode(@file_get_contents(API_STATUS_CACHE_FILE), true);
        if (is_array($cached) && isset($cached['checked_at'], $cached['checks'])
            && (time() - (int)$cached['checked_at']) < API_STATUS_CACHE_TTL) {
            return $cached['checks'];
        }
    }

    $checks   = getApiChecks();
    $lastRuns = apiLastCronRuns();

    foreach ($checks as $key => $check) {
        $checks[$key]['last_sync'] = $lastRuns[$check['script']] ?? null;
    }

    @file_put_contents(API_STATUS_CACHE_FILE, json_encode(['checked_at' => time(), 'checks' => $checks]), LOCK_EX);

    return $checks;
}