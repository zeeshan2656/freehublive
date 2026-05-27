<?php
// ============================================================
// FreeHub.Live — One-click Installer
// ============================================================
if (file_exists(__DIR__ . '/../includes/db.php')) {
    // Try connecting to check if already installed
    try {
        require_once __DIR__ . '/../includes/db.php';
        $check = db_fetch("SELECT id FROM users WHERE role='admin' LIMIT 1");
        if ($check) {
            die('<div style="font-family:sans-serif;padding:40px;background:#0f0f13;color:#fff;min-height:100vh">
                <h2 style="color:#6366f1">&#10003; FreeHub.Live is already installed!</h2>
                <p style="color:#aaa;margin-top:8px">Admin user exists. <a href="/FreeHub.Live/" style="color:#6366f1">Go to Homepage</a> or <a href="/FreeHub.Live/admin/" style="color:#6366f1">Admin Panel</a></p>
                </div>');
        }
    } catch (Exception $e) {}
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = trim($_POST['db_name'] ?? 'freehub');
    $admin_user  = trim($_POST['admin_user'] ?? 'admin');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_pass  = $_POST['admin_pass'] ?? '';
    $site_name   = trim($_POST['site_name'] ?? 'FreeHub');

    try {
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        // Run schema
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        // Remove USE statement since we already selected db
        $sql = preg_replace('/USE\s+\w+;/', '', $sql);
        // Remove CREATE DATABASE
        $sql = preg_replace('/CREATE DATABASE.+?;/s', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt) $pdo->exec($stmt);
        }

        // Create admin
        $hash = password_hash($admin_pass ?: 'admin123', PASSWORD_BCRYPT, ['cost'=>12]);
        $ref  = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
        $pdo->prepare("INSERT INTO users (username,email,password,role,status,email_verified,ref_code,channel_name,preferred_currency)
                        VALUES (?,?,?,'admin','active',1,?,?,'USD') ON DUPLICATE KEY UPDATE password=VALUES(password), channel_name=VALUES(channel_name)")
            ->execute([$admin_user, $admin_email ?: "$admin_user@freehub.live", $hash, $ref, $admin_user . ' Channel']);

        // Update site name
        $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES ('site_name',?) ON DUPLICATE KEY UPDATE `value`=?")
            ->execute([$site_name, $site_name]);

        // Save database credentials (keeps includes/db.php intact)
        $configPhp = "<?php\nreturn " . var_export([
            'host'    => $db_host,
            'user'    => $db_user,
            'pass'    => $db_pass,
            'name'    => $db_name,
            'charset' => 'utf8mb4',
        ], true) . ";\n";
        file_put_contents(__DIR__ . '/../includes/db.config.php', $configPhp);

        $success = true;
        $message = "Installation complete! Admin: <b>$admin_user</b> / Password: <b>" . ($admin_pass ?: 'admin123') . "</b>";
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install FreeHub.Live</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0f0f13;color:#e8e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{width:100%;max-width:520px;background:#18181f;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:36px}
h1{font-size:1.6rem;font-weight:800;background:linear-gradient(135deg,#6366f1,#818cf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:4px}
.sub{color:#9999b3;font-size:.875rem;margin-bottom:28px}
label{display:block;font-size:.82rem;font-weight:600;color:#9999b3;margin-bottom:5px}
input{width:100%;padding:10px 14px;background:#0f0f13;border:1px solid rgba(255,255,255,.1);border-radius:8px;color:#e8e8f0;font-size:.9rem;margin-bottom:14px;outline:none}
input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.section-title{font-size:.75rem;font-weight:700;color:#5555aa;text-transform:uppercase;letter-spacing:.08em;margin:16px 0 10px;border-top:1px solid rgba(255,255,255,.07);padding-top:16px}
button{width:100%;padding:12px;background:#6366f1;color:#fff;border:none;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;margin-top:8px;transition:filter .15s}
button:hover{filter:brightness(1.1)}
.msg{padding:14px;border-radius:8px;margin-bottom:16px;font-size:.88rem}
.msg.ok{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#22c55e}
.msg.err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#ef4444}
.links{margin-top:20px;text-align:center;font-size:.85rem;color:#9999b3}
.links a{color:#6366f1}
</style>
</head>
<body>
<div class="box">
  <h1>FreeHub.Live</h1>
  <p class="sub">One-click installer — takes about 10 seconds</p>

  <?php if ($message): ?>
  <div class="msg <?= $success?'ok':'err' ?>"><?= $message ?></div>
  <?php if ($success): ?>
  <div class="links"><a href="/FreeHub.Live/">&#127968; Go to Homepage</a> &nbsp;|&nbsp; <a href="/FreeHub.Live/admin/">&#9881; Admin Panel</a></div>
  <?php endif; ?>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST">
    <div class="section-title">Database</div>
    <label>Host</label><input name="db_host" value="localhost" required placeholder="localhost (Hostinger default)">
    <label>Username</label><input name="db_user" value="root" required placeholder="e.g. u123456789_freehub">
    <label>Password</label><input name="db_pass" type="password" placeholder="From Hostinger hPanel → MySQL">
    <label>Database Name</label><input name="db_name" value="freehub" required placeholder="e.g. u123456789_freehub">

    <div class="section-title">Admin Account</div>
    <label>Admin Username</label><input name="admin_user" value="admin" required>
    <label>Admin Email</label><input name="admin_email" type="email" placeholder="admin@yourdomain.com">
    <label>Admin Password</label><input name="admin_pass" type="password" placeholder="Leave blank for: admin123">

    <div class="section-title">Site</div>
    <label>Site Name</label><input name="site_name" value="FreeHub" required>

    <button type="submit">&#9889; Install Now</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
