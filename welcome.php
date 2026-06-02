<?php
// ============================================================
// FreeHub.Live — Welcome Page Redirect to Homepage
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect everyone to the main homepage
header('Location: ' . BASE_URL . '/');
exit;
