<?php
/**
 * dashboard-proxy.php
 * Server-side proxy for the NXNE 2026 DASHBOARD_CACHE gviz URL.
 * Bypasses Chrome/Google Workspace auth-blocking of direct browser requests
 * to docs.google.com. Returns the raw gviz response as-is (the dashboard JS
 * already knows how to parse the /*O_o* / wrapper format).
 *
 * Cache: 30 seconds (matches dashboard poll interval).
 * Falls back to stale cache if Google is unreachable.
 */

$GVIZ_URL = 'https://docs.google.com/spreadsheets/d/1dPw4Okk7JwfMP4KlEqYQeJFN_3e5wF0xJG_RzdFlO0s/gviz/tq?tqx=out:json&sheet=DASHBOARD_CACHE&gid=653524335';
$CACHE_FILE = sys_get_temp_dir() . '/nxne_dashboard_cache.txt';
$CACHE_TTL  = 30; // seconds

$allowed_origins = [
    'https://velluma.co',
    'https://www.velluma.co',
    'https://vellumagit.github.io',
    'http://localhost',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://vellumagit.github.io');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/javascript; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Return cached response if still fresh
if (file_exists($CACHE_FILE) && (time() - filemtime($CACHE_FILE)) < $CACHE_TTL) {
    header('X-Cache: HIT');
    echo file_get_contents($CACHE_FILE);
    exit;
}

// Fetch from Google
$context = stream_context_create([
    'http' => [
        'method'          => 'GET',
        'timeout'         => 10,
        'follow_location' => 1,
        'max_redirects'   => 5,
        'header'          => "User-Agent: NXNE-Dashboard-Proxy/1.0\r\n",
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$response = @file_get_contents($GVIZ_URL, false, $context);

if ($response !== false && strlen($response) > 50) {
    // Fresh response — write to cache
    file_put_contents($CACHE_FILE, $response);
    header('X-Cache: MISS');
    echo $response;
} elseif (file_exists($CACHE_FILE)) {
    // Google unreachable — serve stale cache
    header('X-Cache: STALE');
    echo file_get_contents($CACHE_FILE);
} else {
    http_response_code(502);
    echo '/*O_o*/\ngoogle.visualization.Query.setResponse({"status":"error","errors":[{"reason":"proxy_error","message":"Dashboard proxy could not reach Google Sheets"}]});';
}
