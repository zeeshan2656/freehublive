<?php
// ============================================================
// FreeHub.Live — Admin Rich Text Editor Upload Handler
// ============================================================
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Admin role validation
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// CSRF Token validation for secure upload requests
if (!verify_csrf($_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF verification failed.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed.']);
    exit;
}

if (empty($_FILES['file']['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['file'];

// Check upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload error code: ' . $file['error']]);
    exit;
}

// MIME Type validation
$mime = mime_content_type($file['tmp_name']);
if (!allowed_image($mime)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WEBP images are allowed.']);
    exit;
}

// Ensure the upload directory exists
$upload_dir = __DIR__ . '/../uploads/pages/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename and move file
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
    $ext = 'jpg';
}

$image_fn = unique_filename($ext);
$destination = $upload_dir . $image_fn;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    // Return absolute URL location for TinyMCE editor
    echo json_encode([
        'location' => BASE_URL . '/uploads/pages/' . $image_fn
    ]);
    exit;
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save the uploaded image.']);
    exit;
}
