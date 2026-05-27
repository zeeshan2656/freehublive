<?php
// ============================================================
// FreeHub.Live — Dynamic Page Router (CMS)
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

// Query the page by slug
$page = db_fetch("SELECT * FROM pages WHERE slug = ?", [$slug]);

$is_admin = is_admin();
$error_type = null;

if (!$page) {
    $error_type = '404';
} else {
    // Advanced visibility status gates
    if ($page['status'] === 'draft' && !$is_admin) {
        $error_type = '404';
    } elseif ($page['status'] === 'private' && !is_logged_in()) {
        $error_type = 'private';
    } elseif ($page['status'] === 'scheduled' && !$is_admin) {
        // If scheduled and time has not arrived yet, restrict access
        if (empty($page['publish_at']) || strtotime($page['publish_at']) > time()) {
            $error_type = 'scheduled';
        }
    }
}

// Render access restriction screen if gate is triggered
if ($error_type) {
    http_response_code($error_type === 'private' ? 403 : 404);
    $meta_title = ($error_type === 'private') ? 'Access Restricted' : 'Page Not Found';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container" style="max-width: 800px; padding: 80px 20px; text-align: center;">
        <?php if ($error_type === 'private'): ?>
            <h1 style="font-size: 3rem; font-weight: 800; color: var(--text); margin-bottom: 16px;">🔒 Private Page</h1>
            <p style="font-size: 1.15rem; color: var(--text2); margin-bottom: 32px;">This page content is private. Please log in to your account to view this page.</p>
            <a href="<?= BASE_URL ?>/auth/login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary" style="height: 38px; border-radius: 19px; display: inline-flex; align-items: center; padding: 0 24px; font-weight: 600; text-decoration: none;">
                Log In
            </a>
        <?php elseif ($error_type === 'scheduled'): ?>
            <h1 style="font-size: 3rem; font-weight: 800; color: var(--text); margin-bottom: 16px;">⏳ Scheduled Page</h1>
            <p style="font-size: 1.15rem; color: var(--text2); margin-bottom: 32px;">This page has been scheduled to be published soon. Please check back later.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-primary" style="height: 38px; border-radius: 19px; display: inline-flex; align-items: center; padding: 0 24px; font-weight: 600; text-decoration: none;">
                Return Home
            </a>
        <?php else: ?>
            <h1 style="font-size: 4rem; font-weight: 800; color: var(--text); margin-bottom: 16px; line-height: 1;">404</h1>
            <p style="font-size: 1.15rem; color: var(--text2); margin-bottom: 32px;">The page you are looking for does not exist or has been moved.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-primary" style="height: 38px; border-radius: 19px; display: inline-flex; align-items: center; padding: 0 24px; font-weight: 600; text-decoration: none;">
                Return Home
            </a>
        <?php endif; ?>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Set up dynamic SEO headers
$meta_title = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'];

if (!empty($page['meta_desc'])) {
    $meta_desc = $page['meta_desc'];
} else {
    $plain_text = strip_tags($page['content']);
    $meta_desc = mb_substr($plain_text, 0, 155);
    if (mb_strlen($plain_text) > 155) {
        $meta_desc .= '...';
    }
}

if (!empty($page['meta_keywords'])) {
    $meta_keywords = $page['meta_keywords'];
}

require_once __DIR__ . '/includes/header.php';
?>
<style>
/* Responsive Article Content Styles matching Global Inter Font */
.wysiwyg-content {
    font-size: 0.96rem;
    line-height: 1.8;
    color: var(--text);
}
.wysiwyg-content p {
    margin-bottom: 20px;
}
.wysiwyg-content h1, 
.wysiwyg-content h2, 
.wysiwyg-content h3, 
.wysiwyg-content h4 {
    color: var(--text);
    font-weight: 700;
    margin-top: 32px;
    margin-bottom: 16px;
    line-height: 1.4;
}
.wysiwyg-content h1 { font-size: 1.8rem; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
.wysiwyg-content h2 { font-size: 1.45rem; }
.wysiwyg-content h3 { font-size: 1.25rem; }

.wysiwyg-content ul, 
.wysiwyg-content ol {
    margin-top: 8px;
    margin-bottom: 20px;
    padding-left: 24px;
}
.wysiwyg-content li {
    margin-bottom: 8px;
}
.wysiwyg-content ul {
    list-style-type: disc;
}
.wysiwyg-content ol {
    list-style-type: decimal;
}

.wysiwyg-content table {
    width: 100% !important;
    border-collapse: collapse;
    margin: 24px 0;
    font-size: 0.88rem;
    background: var(--bg3);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border);
}
.wysiwyg-content th, 
.wysiwyg-content td {
    padding: 12px 16px;
    border: 1px solid var(--border);
    text-align: left;
}
.wysiwyg-content th {
    background: rgba(255, 255, 255, 0.03);
    font-weight: 700;
    color: var(--text);
}
.wysiwyg-content tr:last-child td {
    border-bottom: 0;
}

.wysiwyg-content img {
    max-width: 100%;
    height: auto !important;
    border-radius: 8px;
    margin: 16px 0;
    display: block;
}
.wysiwyg-content iframe, 
.wysiwyg-content video {
    max-width: 100%;
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 8px;
    border: 0;
    margin: 20px 0;
}
.wysiwyg-content a {
    color: var(--accent);
    text-decoration: underline;
    transition: color 0.15s;
}
.wysiwyg-content a:hover {
    color: var(--text);
}
.wysiwyg-content blockquote {
    border-left: 4px solid var(--accent);
    background: var(--bg3);
    padding: 16px 20px;
    margin: 20px 0;
    border-radius: 0 8px 8px 0;
    font-style: italic;
    color: var(--text2);
}
</style>

<div class="container" style="max-width: 860px; padding: 40px 16px; margin: 0 auto;">
    <article class="card" style="padding: 40px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
        
        <!-- Administrator CMS Info notice -->
        <?php if ($page['status'] !== 'published'): ?>
            <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid var(--yellow); color: var(--yellow); padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 24px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <span>⚠️</span>
                <span>
                    <strong><?= ucfirst($page['status']) ?> Mode:</strong> 
                    This page is visibility-restricted (Status: <?= $page['status'] ?>). 
                    <?php if ($page['status'] === 'scheduled'): ?>
                        Scheduled to go live on <?= date('M d, Y H:i', strtotime($page['publish_at'])) ?>.
                    <?php endif; ?>
                    Only administrators can view it in this state.
                </span>
            </div>
        <?php endif; ?>

        <!-- Article Header -->
        <header style="margin-bottom: 36px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
            <h1 style="font-size: 2.2rem; font-weight: 800; line-height: 1.25; margin-bottom: 8px; color: var(--text);"><?= e($page['title']) ?></h1>
            <div style="font-size: 0.8rem; color: var(--text2); display: flex; align-items: center; gap: 6px;">
                <span>Published by FreeHub</span>
                <span>•</span>
                <span>Updated <?= date('F d, Y', strtotime($page['updated_at'])) ?></span>
            </div>
        </header>

        <!-- Article Content -->
        <div class="wysiwyg-content">
            <?= $page['content'] ?>
        </div>
    </article>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
