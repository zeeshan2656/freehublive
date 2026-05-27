<?php
// ============================================================
// FreeHub.Live — Currency preference API
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$code = strtoupper(trim((string)($body['currency'] ?? '')));

if ($code === '' || !fh_set_user_currency($code)) {
    json_error('Invalid currency', 400);
}

$currencies = fh_currencies();
json_response([
    'success'  => true,
    'currency' => $code,
    'symbol'   => $currencies[$code]['symbol'] ?? $code,
    'label'    => $currencies[$code]['label'] ?? $code,
]);
