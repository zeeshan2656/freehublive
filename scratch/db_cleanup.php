<?php
// DB Cleanup Script
require_once __DIR__ . '/../includes/db.php';

try {
    db_query("DROP TABLE IF EXISTS pages");
    echo "SUCCESS: dropped table 'pages'\n";
    
    db_query("DROP TABLE IF EXISTS footer_sections");
    echo "SUCCESS: dropped table 'footer_sections'\n";
    
    db_query("DROP TABLE IF EXISTS affiliate_clicks");
    echo "SUCCESS: dropped table 'affiliate_clicks'\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
