

<?php
/**
 * Copy this file to db.config.php and fill in your Hostinger MySQL details.
 *
 * Hostinger hPanel → Websites → Manage → Databases → MySQL Databases
 *   - Database name: e.g. u123456789_freehub
 *   - Username:      e.g. u123456789_freehub
 *   - Password:      (the one you set in hPanel)
 *   - Host:          usually "localhost" on Hostinger (use the host shown in hPanel if different)
 */
return [
    'host'    => 'localhost',
    'port'    => 3306,
    'user'    => 'u123456789_freehub',
    'pass'    => 'YOUR_DATABASE_PASSWORD',
    'name'    => 'u123456789_freehub',
    'charset' => 'utf8mb4',
];
