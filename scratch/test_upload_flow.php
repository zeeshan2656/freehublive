<?php
// Test Orchestrator for Universal Upload System
require_once __DIR__ . '/../includes/db.php';

function run_mock_api($script, $method, $get = [], $post = [], $files = [], $server = []) {
    $payload = json_encode([
        'script' => $script,
        'get' => $get,
        'post' => $post,
        'files' => $files,
        'server' => array_merge(['REQUEST_METHOD' => $method], $server),
    ]);
    
    $escaped = escapeshellarg($payload);
    $cmd = "C:\\xampp\\php\\php.exe " . escapeshellarg(__DIR__ . '/mock_request.php') . " " . $escaped;
    $res = shell_exec($cmd);
    return json_decode($res, true) ?: $res;
}

function assert_test($name, $assertion) {
    if ($assertion) {
        echo "✅ SUCCESS: $name\n";
        return true;
    } else {
        echo "❌ FAILED: $name\n";
        return false;
    }
}

echo "=== STARTING UPLOAD PIPELINE INTEGRATION TESTS ===\n\n";

$allPassed = true;

// ============================================================
// PART 1: Standard Video Upload Flow
// ============================================================
echo "--- Testing Standard Video Upload Flow ---\n";

// 1. Initialize session
$initRes = run_mock_api('api/videos.php', 'POST', ['action' => 'init_upload'], [], [], [
    'CONTENT_TYPE' => 'application/json'
]);
// Since api/videos.php reads json body from php://input, let's mock it by passing body in post or raw
// Wait, mock_request.php doesn't mock php://input directly, but let's check how api/videos.php reads request body
// Ah! Let's check api/videos.php for body reading:
// It reads from php://input: $body = json_decode(file_get_contents('php://input'), true);
// Let's modify mock_request.php if needed or write a custom test in test_upload_flow.php that inserts directly, or we can just mock php://input in mock_request.php!
// Wait! Can mock_request.php mock php://input?
// In PHP, we cannot override php://input easily unless we use a custom stream wrapper or if we pass the raw body as POST data, or if we modify mock_request.php to support JSON input.
// Wait, let's check: does api/videos.php read from $_POST if php://input is empty?
// Let's search for body reading in api/videos.php:
// Line 205: $meta = $body['meta'] ?? [];
// And $body is defined at the top of the file! Let's find $body in api/videos.php.
?>
