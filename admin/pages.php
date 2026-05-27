<?php
// ============================================================
// FreeHub.Live — Admin Pages Management (CMS)
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

// Process actions (Publish status toggle, duplication, and deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle') {
        $id = (int)($_POST['page_id'] ?? 0);
        $curr = db_fetch("SELECT status FROM pages WHERE id = ?", [$id]);
        if ($curr) {
            $new_status = ($curr['status'] === 'published') ? 'draft' : 'published';
            $is_pub = ($new_status === 'published') ? 1 : 0;
            db_query("UPDATE pages SET status = ?, is_published = ? WHERE id = ?", [$new_status, $is_pub, $id]);
            flash('success', 'Page visibility updated successfully.');
        }
        redirect(BASE_URL . '/admin/pages.php');
    }

    if ($action === 'duplicate') {
        $id = (int)($_POST['page_id'] ?? 0);
        $original = db_fetch("SELECT * FROM pages WHERE id = ?", [$id]);
        if ($original) {
            $new_title = $original['title'] . ' (Copy)';
            $base_slug = $original['slug'] . '-copy';
            
            // Ensure unique slug
            $slug = $base_slug;
            $counter = 1;
            while (true) {
                $check = db_fetch("SELECT id FROM pages WHERE slug = ?", [$slug]);
                if (!$check) {
                    break;
                }
                $slug = $base_slug . '-' . $counter;
                $counter++;
            }
            
            db_insert('pages', [
                'title' => $new_title,
                'slug' => $slug,
                'content' => $original['content'],
                'is_published' => 0, // Set duplicated page as draft
                'status' => 'draft',
            ]);
            
            flash('success', 'Page duplicated successfully as a draft.');
        } else {
            flash('error', 'Failed to find original page.');
        }
        redirect(BASE_URL . '/admin/pages.php');
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['page_id'] ?? 0);
        db_query("DELETE FROM pages WHERE id = ?", [$id]);
        flash('success', 'Page deleted successfully.');
        redirect(BASE_URL . '/admin/pages.php');
    }
}

// Advanced Search and Status Filters
$filter_status = $_GET['filter_status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = "1";
$params = [];

if ($filter_status !== 'all' && in_array($filter_status, ['published', 'draft', 'private', 'scheduled'], true)) {
    $where .= " AND status = ?";
    $params[] = $filter_status;
}

if ($search !== '') {
    $where .= " AND (title LIKE ? OR slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Fetch filtered pages
$pages = db_fetchAll("SELECT * FROM pages WHERE $where ORDER BY title ASC", $params);

// Define sticky header action button (moves Create button up to dashboard bar)
$header_actions = '
    <a href="' . BASE_URL . '/admin/page_edit.php" class="btn btn-primary btn-sm">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="margin-right: 4px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create New Page
    </a>
';

$meta_title = 'Page Management';
require_once __DIR__ . '/partials/admin_head.php';
?>
<style>
.btn-xs {
    padding: 4px 8px;
    font-size: 0.75rem;
    height: 28px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
}
</style>
<div class="admin-content">

    <!-- Advanced Filter Bar (styled like Videos page) -->
    <form method="GET" class="card" style="margin-bottom: 24px; padding: 18px; background: var(--bg2); border: 1px solid var(--border)">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: flex-end;">
            
            <!-- Search field -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Search Pages</label>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Title or slug..." class="form-input">
            </div>

            <!-- Status dropdown -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Visibility Status</label>
                <select class="form-input form-select" name="filter_status">
                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="published" <?= $filter_status === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $filter_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="private" <?= $filter_status === 'private' ? 'selected' : '' ?>>Private</option>
                    <option value="scheduled" <?= $filter_status === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                </select>
            </div>

            <!-- Action buttons -->
            <div class="flex gap-2" style="margin-bottom: 0;">
                <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; height: 42px;">Filter</button>
                <a href="?" class="btn btn-outline" style="flex: 1; justify-content: center; height: 42px; display: inline-flex; align-items: center; text-decoration: none;">Reset</a>
            </div>

        </div>
    </form>

    <!-- Alert Notifications -->
    <?php foreach (get_flash() as $f): ?>
        <div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Page Title</th>
                    <th>Slug / URL</th>
                    <th style="text-align: center;">Status</th>
                    <th>Last Updated</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="5" style="padding: 24px; text-align: center; color: var(--text2);">No pages found. Click "Create New Page" to add one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pages as $p): ?>
                        <tr>
                            <td style="font-weight: 600; font-size: 0.9rem;">
                                <?= e($p['title']) ?>
                            </td>
                            <td style="font-family: monospace; font-size: 0.82rem; color: var(--accent);">
                                <a href="<?= BASE_URL ?>/page.php?slug=<?= e($p['slug']) ?>" target="_blank" style="color: var(--accent); text-decoration: none;">
                                    /page.php?slug=<?= e($p['slug']) ?> ↗
                                </a>
                            </td>
                            <td style="text-align: center;">
                                <?php
                                $badge_class = match ($p['status'] ?? 'draft') {
                                    'published' => 'green',
                                    'private' => 'yellow',
                                    'scheduled' => 'blue',
                                    default => 'gray'
                                };
                                ?>
                                <span class="badge badge-<?= $badge_class ?>" style="font-size: 0.72rem; padding: 4px 8px; border-radius: 4px; text-transform: capitalize;">
                                    <?= e($p['status'] ?? 'draft') ?>
                                </span>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text2);">
                                <?= date('M d, Y H:i', strtotime($p['updated_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center;">
                                    <!-- Edit button -->
                                    <a href="<?= BASE_URL ?>/admin/page_edit.php?id=<?= $p['id'] ?>" class="btn btn-xs" style="background: var(--bg3); border: 1px solid var(--border); color: var(--text);">
                                        Edit
                                    </a>

                                    <!-- Duplicate button -->
                                    <form method="POST" action="" style="margin: 0; display: inline;">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="duplicate">
                                        <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-xs" style="background: transparent; border: 1px solid var(--accent); color: var(--accent);">
                                            Duplicate
                                        </button>
                                    </form>

                                    <!-- Toggle Status button -->
                                    <form method="POST" action="" style="margin: 0; display: inline;">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-xs" style="background: transparent; border: 1px solid <?= ($p['status'] === 'published') ? 'var(--yellow)' : 'var(--green)' ?>; color: <?= ($p['status'] === 'published') ? 'var(--yellow)' : 'var(--green)' ?>;">
                                            <?= ($p['status'] === 'published') ? 'Unpublish' : 'Publish' ?>
                                        </button>
                                    </form>

                                    <!-- Delete button (with confirmation) -->
                                    <form method="POST" action="" style="margin: 0; display: inline;" onsubmit="return confirm('Are you sure you want to delete this page? This action cannot be undone.');">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-xs" style="background: transparent; border: 1px solid rgba(239, 68, 68, 0.4); color: var(--red);">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
