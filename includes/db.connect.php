<?php
/**
 * PDO connection helper — tries Hostinger-friendly host variants.
 *
 * @param array{host:string,user:string,pass:string,name:string,charset?:string,port?:int} $cfg
 * @return array{pdo:PDO,host_used:string}|array{error:string,code:int}
 */
function fh_pdo_connect(array $cfg): array {
    $charset = $cfg['charset'] ?? 'utf8mb4';
    $port    = isset($cfg['port']) ? (int)$cfg['port'] : 3306;
    $user    = $cfg['user'] ?? '';
    $pass    = $cfg['pass'] ?? '';
    $name    = $cfg['name'] ?? '';
    $hostIn  = trim($cfg['host'] ?? 'localhost');

    if ($user === '' || $name === '') {
        return ['error' => 'Database user and database name are required in includes/db.config.php', 'code' => 0];
    }

    $hosts = array_values(array_unique(array_filter([
        $hostIn,
        $hostIn === 'localhost' ? '127.0.0.1' : null,
        $hostIn === '127.0.0.1' ? 'localhost' : null,
    ])));

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset . ' COLLATE utf8mb4_unicode_ci',
    ];

    $last = null;
    foreach ($hosts as $host) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            return ['pdo' => $pdo, 'host_used' => $host];
        } catch (PDOException $e) {
            $last = $e;
            // "Unknown database" — credentials may be OK; tables not imported
            if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                return [
                    'error' => 'Database "' . $name . '" does not exist. Create it in Hostinger hPanel, then import install/schema.sql via phpMyAdmin.',
                    'code'  => (int)$e->getCode(),
                ];
            }
            // Access denied — wrong user/pass or user not linked to DB
            if ($e->getCode() == 1045 || str_contains($e->getMessage(), 'Access denied')) {
                return [
                    'error' => 'Access denied for user "' . $user . '". Check password in db.config.php and that the user is assigned to database "' . $name . '" in hPanel.',
                    'code'  => (int)$e->getCode(),
                ];
            }
        }
    }

    $msg = $last ? $last->getMessage() : 'Could not connect to MySQL';
    return ['error' => $msg, 'code' => $last ? (int)$last->getCode() : 0];
}

/**
 * Load DB config array from includes/db.config.php (or legacy defines).
 */
function fh_load_db_config(): array {
    $defaults = [
        'host'    => 'localhost',
        'user'    => 'root',
        'pass'    => '',
        'name'    => 'freehub',
        'charset' => 'utf8mb4',
        'port'    => 3306,
    ];

    $file = __DIR__ . '/db.config.php';
    if (is_file($file)) {
        $loaded = require $file;
        if (is_array($loaded)) {
            return array_merge($defaults, $loaded);
        }
    }

    return $defaults;
}
