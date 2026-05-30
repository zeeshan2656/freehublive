<?php
// ============================================================
// FreeHub.Live — Third-Party Embed Proxy (cURL based)
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$url = $_GET['url'] ?? '';
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die('Invalid URL.');
}

/** Check host resolution to prevent SSRF against loopback or private IP subnets */
function is_safe_proxy_url(string $url): bool {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;
    
    // Block localhost/local string hosts or IPv6 format
    if ($host === 'localhost' || str_contains($host, ':')) {
        return false;
    }
    
    // Resolve host IP address
    $ip = gethostbyname($host);
    if (!$ip || $ip === $host) {
        return false;
    }
    
    // Verify resolved IPv4 addresses are not in private/local address subnets
    if (
        str_starts_with($ip, '127.') ||
        str_starts_with($ip, '10.') ||
        str_starts_with($ip, '192.168.') ||
        str_starts_with($ip, '172.16.') ||
        str_starts_with($ip, '172.17.') ||
        str_starts_with($ip, '172.18.') ||
        str_starts_with($ip, '172.19.') ||
        str_starts_with($ip, '172.20.') ||
        str_starts_with($ip, '172.21.') ||
        str_starts_with($ip, '172.22.') ||
        str_starts_with($ip, '172.23.') ||
        str_starts_with($ip, '172.24.') ||
        str_starts_with($ip, '172.25.') ||
        str_starts_with($ip, '172.26.') ||
        str_starts_with($ip, '172.27.') ||
        str_starts_with($ip, '172.28.') ||
        str_starts_with($ip, '172.29.') ||
        str_starts_with($ip, '172.30.') ||
        str_starts_with($ip, '172.31.') ||
        str_starts_with($ip, '0.') ||
        $ip === '255.255.255.255'
    ) {
        return false;
    }
    
    return true;
}

if (!is_safe_proxy_url($url)) {
    http_response_code(403);
    die('Forbidden: Access to private networks is blocked.');
}

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

$response = curl_exec($ch);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $http_code >= 400) {
    header('Location: ' . $url);
    exit;
}

// Bypass browser frame blockers (like X-Frame-Options: SAMEORIGIN)
header('X-Frame-Options: ALLOWALL');
header('Content-Security-Policy: frame-ancestors *');
header_remove('X-Frame-Options');
header_remove('Content-Security-Policy');

if ($content_type) {
    header('Content-Type: ' . $content_type);
}

// For HTML pages, inject base href to resolve relative links (styles/scripts/images) to origin domain
if (stripos($content_type, 'text/html') !== false || $content_type === null) {
    $base_href = $effective_url ?: $url;
    $base_tag = '<base href="' . htmlspecialchars($base_href, ENT_QUOTES, 'UTF-8') . '">';
    
    if (preg_match('/<head[^>]*>/i', $response, $match, PREG_OFFSET_CAPTURE)) {
        $insert_pos = $match[0][1] + strlen($match[0][0]);
        $response = substr_replace($response, "\n" . $base_tag . "\n", $insert_pos, 0);
    } else {
        $response = $base_tag . $response;
    }
}

echo $response;
