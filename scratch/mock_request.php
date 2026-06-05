<?php
// Mock Request Runner for Testing
if (php_sapi_name() !== 'cli') {
    die("CLI only");
}

$input = json_decode($argv[1] ?? '{}', true);

// Setup globals
$_GET = $input['get'] ?? [];
$_POST = $input['post'] ?? [];
$_SERVER = array_merge([
    'REQUEST_METHOD' => 'GET',
    'HTTP_CONTENT_RANGE' => '',
    'PHP_SELF' => '/api/upload.php'
], $input['server'] ?? []);

if (isset($input['files'])) {
    $_FILES = [];
    foreach ($input['files'] as $key => $file) {
        // Create a temporary file with the given content
        $tmpName = tempnam(sys_get_temp_dir(), 'mock_upload_');
        file_put_contents($tmpName, base64_decode($file['base64_content']));
        $_FILES[$key] = [
            'name' => $file['name'],
            'type' => $file['type'] ?? 'video/mp4',
            'tmp_name' => $tmpName,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpName),
        ];
    }
}

// Start session and mock user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user'] = $input['session_user'] ?? [
    'id' => 1,
    'role' => 'admin',
    'username' => 'admin',
];

// Include the script
$script = $input['script'];

// Capture output
ob_start();
try {
    include __DIR__ . '/../' . $script;
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$output = ob_get_clean();

// Cleanup temp files
if (isset($_FILES)) {
    foreach ($_FILES as $file) {
        if (is_file($file['tmp_name'])) {
            @unlink($file['tmp_name']);
        }
    }
}

echo $output;
