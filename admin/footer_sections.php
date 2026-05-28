<?php
// ============================================================
// FreeHub.Live — Admin Footer Sections Management
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$error = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if ($name === '') {
            flash('error', 'Section name is required.');
        } else {
            db_insert('footer_sections', [
                'name'       => $name,
                'sort_order' => $sort_order
            ]);
            flash('success', 'Footer section created successfully.');
        }
        redirect(BASE_URL . '/admin/footer_sections.php');
    }

    if ($action === 'update') {
        $id = (int)($_POST['section_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);

        if ($id && $name !== '') {
            db_update('footer_sections', [
                'name'       => $name,
                'sort_order' => $sort_order
            ], 'id=?', [$id]);
            flash('success', 'Footer section updated successfully.');
        } else {
            flash('error', 'Section name is required.');
        }
        redirect(BASE_URL . '/admin/footer_sections.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['section_id'] ?? 0);
        
        // Remove association on pages
        db_query("UPDATE pages SET footer_section_id = NULL WHERE footer_section_id = ?", [$id]);
        
        // Delete the section
        db_query("DELETE FROM footer_sections WHERE id = ?", [$id]);
        
        flash('success', 'Footer section deleted.');
        redirect(BASE_URL . '/admin/footer_sections.php');
    }
}

// Fetch all sections
$sections = db_fetchAll("SELECT * FROM footer_sections ORDER BY sort_order ASC, id ASC");

// Fetch pages with their sections for listing
$unassigned_pages = db_fetchAll("SELECT id, title FROM pages WHERE footer_section_id IS NULL AND is_published = 1 ORDER BY title ASC");

$header_actions = '
    <a href="' . BASE_URL . '/admin/pages.php" class="btn btn-outline btn-sm" style="margin-right: 8px;">
        ← Back to Pages
    </a>
    <button type="button" class="btn btn-primary btn-sm" id="btn-add-section">
        + Create Footer Section
    </button>
';

$meta_title = 'Footer Sections';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">

    <!-- Flash notifications -->
    <?php foreach (get_flash() as $f): ?>
        <div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>

    <!-- Sections List Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Section Name</th>
                    <th style="text-align: center; width: 120px;">Sort Order</th>
                    <th>Assigned Pages</th>
                    <th style="text-align: right; width: 220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sections)): ?>
                    <tr>
                        <td colspan="4" style="padding: 24px; text-align: center; color: var(--text2);">No footer sections defined yet. Click "Create Footer Section" to add one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sections as $sec): ?>
                        <tr>
                            <td style="font-weight: 700; font-size: 0.95rem; color: var(--text);">
                                <?= e($sec['name']) ?>
                            </td>
                            <td style="text-align: center; font-weight: 600;">
                                <?= (int)$sec['sort_order'] ?>
                            </td>
                            <td>
                                <?php
                                $assigned = db_fetchAll("SELECT title, slug FROM pages WHERE footer_section_id = ? ORDER BY title ASC", [$sec['id']]);
                                if ($assigned) {
                                    $links = [];
                                    foreach ($assigned as $p) {
                                        $links[] = '<a href="' . BASE_URL . '/page.php?slug=' . e($p['slug']) . '" target="_blank" style="color: var(--accent); text-decoration: none;">' . e($p['title']) . '</a>';
                                    }
                                    echo implode(' · ', $links);
                                } else {
                                    echo '<span class="text-muted text-xs">No pages assigned</span>';
                                }
                                ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    <button type="button" class="btn btn-outline btn-xs btn-edit-sec" 
                                            data-id="<?= $sec['id'] ?>" 
                                            data-name="<?= e($sec['name']) ?>" 
                                            data-sort="<?= (int)$sec['sort_order'] ?>">
                                        Edit
                                    </button>
                                    
                                    <form method="POST" action="" style="margin: 0; display: inline;" onsubmit="return confirm('Are you sure you want to delete this section? All assigned pages will remain, but will no longer be grouped under this section.');">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="section_id" value="<?= $sec['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-xs" style="border-color: rgba(239, 68, 68, 0.4); color: var(--red);">
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

<!-- Modal: Create / Edit Section -->
<div id="section-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; justify-content: center; align-items: center; padding: 20px;">
    <div class="card" style="background: var(--bg2); width: 100%; max-width: 440px; border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modal-title" style="font-weight: 800; font-size: 1.05rem;">Create Footer Section</h3>
            <button type="button" id="close-modal" style="font-size: 1.4rem; color: var(--text2); cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 20px;">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" id="modal-action" value="create">
            <input type="hidden" name="section_id" id="modal-section-id" value="">
            
            <div class="form-group">
                <label class="form-label" for="sec-name">Section Name</label>
                <input type="text" id="sec-name" name="name" class="form-input" required placeholder="e.g. Platform, Useful Links">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="sec-sort">Sort Order</label>
                <input type="number" id="sec-sort" name="sort_order" class="form-input" value="0" required>
            </div>
            
            <div class="flex" style="justify-content: flex-end; gap: 10px; margin-top: 24px;">
                <button type="button" id="btn-cancel-modal" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Section</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('section-modal');
    const closeBtn = document.getElementById('close-modal');
    const cancelBtn = document.getElementById('btn-cancel-modal');
    const addBtn = document.getElementById('btn-add-section');
    
    const mTitle = document.getElementById('modal-title');
    const mAction = document.getElementById('modal-action');
    const mId = document.getElementById('modal-section-id');
    const mName = document.getElementById('sec-name');
    const mSort = document.getElementById('sec-sort');

    function openModal(title, action, id = '', name = '', sort = '0') {
        mTitle.textContent = title;
        mAction.value = action;
        mId.value = id;
        mName.value = name;
        mSort.value = sort;
        modal.style.display = 'flex';
        mName.focus();
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    if (addBtn) {
        addBtn.addEventListener('click', () => openModal('Create Footer Section', 'create'));
    }
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    document.querySelectorAll('.btn-edit-sec').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const sort = this.getAttribute('data-sort');
            openModal('Edit Footer Section', 'update', id, name, sort);
        });
    });

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
});
</script>
<?php require_once __DIR__ . '/partials/admin_foot.php'; ?>
