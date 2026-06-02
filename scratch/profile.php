<?php
$start = microtime(true);

require_once __DIR__ . '/../includes/db.php';
$after_db = microtime(true);

require_once __DIR__ . '/../includes/auth.php';
$after_auth = microtime(true);

require_once __DIR__ . '/../includes/functions.php';
$after_functions = microtime(true);

echo "Load db.php: " . number_format(($after_db - $start) * 1000, 2) . " ms\n";
echo "Load auth.php: " . number_format(($after_auth - $after_db) * 1000, 2) . " ms\n";
echo "Load functions.php: " . number_format(($after_functions - $after_auth) * 1000, 2) . " ms\n";

$q_start = microtime(true);
$res = db_fetch("SELECT 1");
$q_end = microtime(true);
echo "Simple query: " . number_format(($q_end - $q_start) * 1000, 2) . " ms\n";

$q2_start = microtime(true);
$latest = db_fetchAll(
    "SELECT v.*,u.username,u.channel_name,u.avatar
     FROM videos v
     JOIN users u ON u.id=v.user_id
     WHERE v.status='published' AND v.visibility='public' AND v.is_reel=0
     ORDER BY v.published_at DESC LIMIT 51"
);
$q2_end = microtime(true);
echo "Latest query (51 rows): " . number_format(($q2_end - $q2_start) * 1000, 2) . " ms\n";

$total = microtime(true) - $start;
echo "Total load time: " . number_format($total * 1000, 2) . " ms\n";
