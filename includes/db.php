<?php
// Enable GZIP compression at PHP level for maximum performance on Hostinger
if (!headers_sent() && !ini_get('zlib.output_compression') && ob_get_level() == 0) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', '6');
}

// ============================================================
// FreeHub.Live — Database Connection
// ============================================================
// Credentials: includes/db.config.php (recommended)
// Do NOT replace this whole file with only define() lines.
// ============================================================

$dbDefaults = [
    'host'    => 'localhost',
    'port'    => 3306,
    'user'    => 'root',
    'pass'    => '',
    'name'    => 'freehub',
    'charset' => 'utf8mb4',
];

foreach (['db.config.php', 'db.config.local.php'] as $cfgName) {
    $cfgPath = __DIR__ . '/' . $cfgName;
    if (is_file($cfgPath)) {
        $loaded = require $cfgPath;
        if (is_array($loaded)) {
            $dbDefaults = array_merge($dbDefaults, $loaded);
        }
    }
}

define('DB_HOST', (string)$dbDefaults['host']);
define('DB_PORT', (int)($dbDefaults['port'] ?? 3306));
define('DB_USER', (string)$dbDefaults['user']);
define('DB_PASS', (string)$dbDefaults['pass']);
define('DB_NAME', (string)$dbDefaults['name']);
define('DB_CHARSET', (string)($dbDefaults['charset'] ?? 'utf8mb4'));

function fh_connect_pdo(): PDO {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . DB_CHARSET . ' COLLATE utf8mb4_unicode_ci',
    ];

    $hosts = array_unique([DB_HOST, DB_HOST === 'localhost' ? '127.0.0.1' : DB_HOST]);
    $last  = null;

    foreach ($hosts as $host) {
        $dsn = 'mysql:host=' . $host . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $last = $e;
        }
    }

    throw $last ?? new PDOException('Database connection failed');
}

try {
    $pdo = fh_connect_pdo();
} catch (PDOException $e) {
    http_response_code(503);
    $hasConfig = is_file(__DIR__ . '/db.config.php');
    $hint = $hasConfig
        ? 'Open /install/check-db.php for details. Verify db.config.php matches hPanel → MySQL Databases exactly.'
        : 'Create includes/db.config.php (see db.config.example.php) or run /install/';
    $detail = $e->getMessage();
    $isApi  = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
           || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($isApi) {
        die(json_encode(['error' => 'Database connection failed', 'hint' => $hint, 'detail' => $detail]));
    }
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Database Error</title></head>'
        . '<body style="font-family:system-ui,sans-serif;padding:40px;background:#0f0f0f;color:#eee">'
        . '<h1>Database connection failed</h1><p>' . htmlspecialchars($hint) . '</p>'
        . '<p style="color:#f88;font-size:.85rem;font-family:monospace">' . htmlspecialchars($detail) . '</p>'
        . '<ul style="color:#aaa;font-size:.9rem;line-height:1.7">'
        . '<li>Upload the <strong>full</strong> <code>includes/db.php</code> from the project (not only define lines).</li>'
        . '<li>Put credentials in <code>includes/db.config.php</code></li>'
        . '<li>Import <code>install/schema.sql</code> in phpMyAdmin</li>'
        . '<li>hPanel: user must be <strong>assigned</strong> to the database</li>'
        . '</ul><p><a href="/install/check-db.php" style="color:#8af">Test connection</a></p></body></html>');
}

require_once __DIR__ . '/migrate.php';
fh_run_migrations();

function db_query(string $sql, array $params = []): PDOStatement {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch(string $sql, array $params = []): ?array {
    return db_query($sql, $params)->fetch() ?: null;
}

function db_fetchAll(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

function db_insert(string $table, array $data): int {
    global $pdo;
    $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
    $vals = implode(',', array_fill(0, count($data), '?'));
    $stmt = $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($vals)");
    $stmt->execute(array_values($data));
    return (int)$pdo->lastInsertId();
}

function db_update(string $table, array $data, string $where, array $whereParams = []): int {
    $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($data)));
    $stmt = db_query("UPDATE `$table` SET $set WHERE $where", [...array_values($data), ...$whereParams]);
    return $stmt->rowCount();
}

function db_count(string $table, string $where = '1', array $params = []): int {
    $parts = explode(' ', trim($table));
    $tbl = '`' . $parts[0] . '`';
    if (isset($parts[1])) {
        $tbl .= ' ' . $parts[1];
    }
    return (int)(db_fetch("SELECT COUNT(*) as c FROM $tbl WHERE $where", $params)['c'] ?? 0);
}

function setting(string $key, mixed $default = ''): mixed {
    static $cache = null;
    if ($cache === null) {
        if (function_exists('fh_cache_get')) {
            $cache = fh_cache_get('fh_settings_cache');
        }
        if ($cache === null) {
            if (!fh_table_exists('settings')) {
                return $default;
            }
            $rows = db_fetchAll("SELECT `key`,`value` FROM settings");
            $cache = array_column($rows, 'value', 'key');
            if (function_exists('fh_cache_set')) {
                fh_cache_set('fh_settings_cache', $cache, 120);
            }
        }
    }
    return $cache[$key] ?? $default;
}

/**
 * Fetch all rows from a query, utilizing filesystem cache if valid.
 */
function db_fetchAll_cached(string $sql, array $params = [], int $ttl = 60): array {
    if (!function_exists('fh_cache_get')) {
        return db_fetchAll($sql, $params);
    }
    $cache_key = 'db_query_all_' . md5($sql . serialize($params));
    $cached = fh_cache_get($cache_key);
    if ($cached !== null) {
        return $cached;
    }
    $res = db_fetchAll($sql, $params);
    fh_cache_set($cache_key, $res, $ttl);
    return $res;
}

/**
 * Fetch a single row from a query, utilizing filesystem cache if valid.
 */
function db_fetch_cached(string $sql, array $params = [], int $ttl = 60): ?array {
    if (!function_exists('fh_cache_get')) {
        return db_fetch($sql, $params);
    }
    $cache_key = 'db_query_row_' . md5($sql . serialize($params));
    $cached = fh_cache_get($cache_key);
    if ($cached !== null) {
        return $cached;
    }
    $res = db_fetch($sql, $params);
    fh_cache_set($cache_key, $res, $ttl);
    return $res;
}

