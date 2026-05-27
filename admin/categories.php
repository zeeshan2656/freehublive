<?php
// Admin — Categories Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??'')) {
    $action = $_POST['action']??'';
    if ($action==='add') {
        $name = trim($_POST['name']??'');
        if ($name) {
            $image_fn = null;
            if (!empty($_FILES['image']['name'])) {
                $file = $_FILES['image'];
                $mime = mime_content_type($file['tmp_name']);
                if (allowed_image($mime)) {
                    if (!is_dir(CAT_PATH)) mkdir(CAT_PATH, 0755, true);
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $image_fn = unique_filename($ext ?: 'jpg');
                    move_uploaded_file($file['tmp_name'], CAT_PATH . $image_fn);
                }
            }
            db_insert('categories',[
                'name'       => $name,
                'slug'       => slugify($name),
                'icon'       => trim($_POST['icon']??'play'),
                'color'      => trim($_POST['color']??'#6366f1'),
                'description'=> trim($_POST['description']??''),
                'image'      => $image_fn,
                'sort_order' => (int)($_POST['sort_order']??0),
                'is_active'  => 1,
            ]);
            flash('success','Category added.');
        }
    }
    if ($action==='edit') {
        $id = (int)($_POST['cat_id']??0);
        $name = trim($_POST['name']??'');
        if ($name && $id) {
            $cat = db_fetch("SELECT image FROM categories WHERE id=?", [$id]);
            $image_fn = $cat['image'] ?? null;
            if (!empty($_FILES['image']['name'])) {
                $file = $_FILES['image'];
                $mime = mime_content_type($file['tmp_name']);
                if (allowed_image($mime)) {
                    if (!is_dir(CAT_PATH)) mkdir(CAT_PATH, 0755, true);
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $image_fn = unique_filename($ext ?: 'jpg');
                    move_uploaded_file($file['tmp_name'], CAT_PATH . $image_fn);
                    if ($cat && $cat['image'] && file_exists(CAT_PATH . $cat['image'])) {
                        @unlink(CAT_PATH . $cat['image']);
                    }
                }
            }
            db_update('categories', [
                'name'       => $name,
                'slug'       => slugify($name),
                'icon'       => trim($_POST['icon']??'play'),
                'color'      => trim($_POST['color']??'#6366f1'),
                'description'=> trim($_POST['description']??''),
                'image'      => $image_fn,
                'sort_order' => (int)($_POST['sort_order']??0),
                'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            ], 'id=?', [$id]);
            flash('success','Category updated.');
            redirect(BASE_URL.'/admin/categories.php');
        }
    }
    if ($action==='delete') {
        $id=(int)($_POST['cat_id']??0);
        $old = db_fetch("SELECT image FROM categories WHERE id=?", [$id]);
        if ($old && $old['image'] && file_exists(CAT_PATH . $old['image'])) {
            @unlink(CAT_PATH . $old['image']);
        }
        db_query("DELETE FROM categories WHERE id=?",[$id]);
        flash('success','Category deleted.');
    }
    if ($action==='toggle') {
        $id=(int)($_POST['cat_id']??0);
        db_query("UPDATE categories SET is_active=1-is_active WHERE id=?"  ,[$id]);
    }
    if ($action==='update_limit') {
        $limit = max(1, (int)($_POST['dropdown_cat_limit'] ?? 8));
        db_query("UPDATE settings SET `value`=? WHERE `key`='dropdown_cat_limit'", [$limit]);
        flash('success', 'Dropdown limit updated.');
    }
    redirect(BASE_URL.'/admin/categories.php');
}

$edit_id = (int)($_GET['edit'] ?? 0);
$edit_cat = null;
if ($edit_id) {
    $edit_cat = db_fetch("SELECT * FROM categories WHERE id=?", [$edit_id]);
}

$cats = db_fetchAll("SELECT c.*,(SELECT COUNT(*) FROM videos WHERE category_id=c.id) as vcount FROM categories c ORDER BY sort_order");
$meta_title='Categories';
require_once __DIR__.'/partials/admin_head.php';
?>
<div class="admin-content">
  <div class="admin-page-header" style="justify-content:flex-end">
    <?php if ($edit_cat): ?>
    <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-primary btn-sm admin-page-action">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Category
    </a>
    <?php else: ?>
    <button type="button" class="btn btn-primary btn-sm admin-page-action" onclick="openCatModal()">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Category
    </button>
    <?php endif; ?>
  </div>
  <?php foreach(get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>

  <div class="admin-categories-list">
    <div class="table-wrap admin-table-scroll">
      <table class="admin-categories-table">
        <thead><tr><th>Image</th><th>Name</th><th class="admin-col-hide-sm">Slug</th><th class="admin-col-hide-sm">Color</th><th class="admin-col-hide-sm">Order</th><th>Videos</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($cats as $c): ?>
        <tr>
          <td>
            <img src="<?= category_image_url($c['image']) ?>" style="width:50px;height:30px;object-fit:cover;border-radius:4px" loading="lazy">
          </td>
          <td><div class="flex gap-2"><div style="width:12px;height:12px;background:<?= e($c['color']) ?>;border-radius:50%;flex-shrink:0"></div><span style="font-weight:600;font-size:.875rem"><?= e($c['name']) ?></span></div></td>
          <td class="admin-col-hide-sm text-sm text-muted"><?= e($c['slug']) ?></td>
          <td class="admin-col-hide-sm"><code style="font-size:.75rem;background:var(--bg3);padding:2px 6px;border-radius:4px"><?= e($c['color']) ?></code></td>
          <td class="admin-col-hide-sm text-sm"><?= (int)$c['sort_order'] ?></td>
          <td class="text-sm"><?= $c['vcount'] ?></td>
          <td><span class="badge badge-<?= $c['is_active']?'green':'gray' ?>"><?= $c['is_active']?'Active':'Hidden' ?></span></td>
          <td>
            <div class="admin-row-actions">
              <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
              <form method="POST" class="admin-row-actions-form">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                <button name="action" value="toggle" class="btn btn-sm btn-outline"><?= $c['is_active']?'Hide':'Show' ?></button>
                <button name="action" value="delete" class="btn btn-sm btn-outline" style="color:var(--red)" onclick="return confirm('Delete category?')">&#128465;</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Dropdown display limit setting -->
    <div class="card" style="margin-top: 24px; max-width: 400px">
      <h3 style="font-weight:700;margin-bottom:12px;font-size:.95rem">Dropdown Display Settings</h3>
      <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="update_limit">
        <div class="form-group">
          <label class="form-label">Max Categories in Dropdown</label>
          <input class="form-input" type="number" name="dropdown_cat_limit" value="<?= e(setting('dropdown_cat_limit', '8')) ?>" min="1" max="50">
        </div>
        <button type="submit" class="btn btn-outline w-full" style="justify-content:center">Save Limit</button>
      </form>
    </div>
  </div>

  <!-- Category Popup Modal Form -->
  <div class="modal-backdrop <?= ($edit_cat) ? 'open' : '' ?>" id="cat-form-modal">
    <div class="modal" style="max-width: 500px">
      <div class="modal-header">
        <span class="modal-title" style="font-weight:700;font-size:1.05rem"><?= $edit_cat ? 'Edit Category' : 'Add Category' ?></span>
        <button type="button" class="btn-icon" onclick="closeCatModal()" style="background:none;border:none;color:var(--text2);cursor:pointer;font-size:1.25rem">&times;</button>
      </div>
      <div class="modal-body">
        <?php if ($edit_cat): ?>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="cat_id" value="<?= $edit_cat['id'] ?>">
            <div class="form-group">
              <label class="form-label">Name *</label>
              <input class="form-input" type="text" name="name" required value="<?= e($edit_cat['name']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Category Image</label>
              <?php if ($edit_cat['image']): ?>
                <div style="margin-bottom:8px">
                  <img src="<?= category_image_url($edit_cat['image']) ?>" style="width:100px;height:60px;object-fit:cover;border-radius:4px">
                </div>
              <?php endif; ?>
              <input class="form-input" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="form-group">
              <label class="form-label">Icon (Feather icon name)</label>
              <input class="form-input" type="text" name="icon" value="<?= e($edit_cat['icon']??'play') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Color</label>
              <input type="color" name="color" value="<?= e($edit_cat['color']??'#6366f1') ?>" style="width:100%;height:40px;border:none;border-radius:8px;cursor:pointer;background:none">
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-input" name="description" rows="3"><?= e($edit_cat['description']??'') ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input class="form-input" type="number" name="sort_order" value="<?= (int)($edit_cat['sort_order']??0) ?>" min="0">
            </div>
            <div class="form-group flex gap-2" style="align-items:center">
              <input type="checkbox" name="is_active" id="is_active" value="1" <?= $edit_cat['is_active'] ? 'checked' : '' ?> style="width:16px;height:16px">
              <label for="is_active" class="form-label" style="margin:0;cursor:pointer">Active (Visible)</label>
            </div>
            <div class="flex gap-2" style="justify-content:flex-end">
              <button type="button" class="btn btn-outline" onclick="closeCatModal()">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        <?php else: ?>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
              <label class="form-label">Name *</label>
              <input class="form-input" type="text" name="name" required placeholder="e.g. Gaming">
            </div>
            <div class="form-group">
              <label class="form-label">Category Image</label>
              <input class="form-input" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="form-group">
              <label class="form-label">Icon (Feather icon name)</label>
              <input class="form-input" type="text" name="icon" placeholder="gamepad-2" value="play">
            </div>
            <div class="form-group">
              <label class="form-label">Color</label>
              <input type="color" name="color" value="#6366f1" style="width:100%;height:40px;border:none;border-radius:8px;cursor:pointer;background:none">
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-input" name="description" rows="3" placeholder="Optional description"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input class="form-input" type="number" name="sort_order" value="0" min="0">
            </div>
            <div class="flex gap-2" style="justify-content:flex-end">
              <button type="button" class="btn btn-outline" onclick="closeCatModal()">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<script>
function openCatModal() {
  <?php if ($edit_cat): ?>
    window.location.href = 'categories.php';
    return;
  <?php endif; ?>
  document.getElementById('cat-form-modal').classList.add('open');
}

function closeCatModal() {
  document.getElementById('cat-form-modal').classList.remove('open');
  <?php if ($edit_cat): ?>
    window.location.href = 'categories.php';
  <?php endif; ?>
}

document.getElementById('cat-form-modal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeCatModal();
  }
});
</script>
<?php require_once __DIR__.'/partials/admin_foot.php'; ?>
