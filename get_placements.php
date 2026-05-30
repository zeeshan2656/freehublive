<?php
$config = require __DIR__ . '/includes/db.config.php';
$dsn = "mysql:host=" . $config['host'] . ";port=" . $config['port'] . ";dbname=" . $config['name'] . ";charset=utf8mb4";
$pdo = new PDO($dsn, $config['user'], $config['pass']);

$stmt = $pdo->query("SELECT id, key_name, name, device_target, ad_width, ad_height FROM ad_placements");
$placements = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($placements as $p) {
    echo "ID: {$p['id']} | Key: {$p['key_name']} | Name: {$p['name']} | Device: {$p['device_target']} | Width: " . var_export($p['ad_width'], true) . " | Height: " . var_export($p['ad_height'], true) . "\n";
}
