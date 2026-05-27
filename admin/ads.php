<?php
// Admin — Ads Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

define('ADS_PATH', __DIR__ . '/../uploads/ads/');

if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf']??'')) {
    $action = $_POST['action']??'';
    if ($action==='add' || $action==='edit') {
        $title          = trim($_POST['title']??'');
        $content_type   = in_array($_POST['content_type']??'',['image','html','banner'])?$_POST['content_type']:'image';
        $content        = trim($_POST['content']??'');
        $target_url     = trim($_POST['target_url']??'');
        $device_target  = in_array($_POST['device_target']??'',['all','desktop','mobile'])?$_POST['device_target']:'all';
        $ad_width       = !empty($_POST['ad_width'])?(int)$_POST['ad_width']:null;
        $ad_height      = !empty($_POST['ad_height'])?(int)$_POST['ad_height']:null;
        $position_after = (int)($_POST['position_after']??1);
        $start_date     = !empty($_POST['start_date'])?$_POST['start_date']:null;
        $end_date       = !empty($_POST['end_date'])?$_POST['end_date']:null;

        $image_fn = null;
        if ($action==='edit') {
            $id = (int)$_POST['ad_id'];
            $existing_ad = db_fetch("SELECT image_url FROM ads WHERE id=?", [$id]);
            $image_fn = $existing_ad['image_url'] ?? null;
        }

        // Handle Image Upload
        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $mime = mime_content_type($file['tmp_name']);
            if (allowed_image($mime)) {
                if (!is_dir(ADS_PATH)) mkdir(ADS_PATH, 0755, true);
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $image_fn = unique_filename($ext ?: 'jpg');
                move_uploaded_file($file['tmp_name'], ADS_PATH . $image_fn);
            }
        }

        $data = [
            'title'          => $title,
            'content_type'   => $content_type,
            'content'        => $content,
            'image_url'      => $image_fn,
            'target_url'     => $target_url,
            'device_target'  => $device_target,
            'ad_width'       => $ad_width,
            'ad_height'      => $ad_height,
            'position_after' => $position_after,
            'start_date'     => $start_date,
            'end_date'       => $end_date,
        ];

        if ($action==='add') {
            $data['is_active'] = 1;
            db_insert('ads', $data);
            flash('success', 'Ad created successfully.');
        } else {
            db_update('ads', $data, 'id=?', [$id]);
            flash('success', 'Ad updated successfully.');
        }
        redirect(BASE_URL.'/admin/ads.php');
    }
    if ($action==='toggle') {
        $id=(int)($_POST['ad_id']??0);
        db_query("UPDATE ads SET is_active=1-is_active WHERE id=?",[$id]);
        redirect(BASE_URL.'/admin/ads.php');
    }
    if ($action==='delete') {
        $id=(int)($_POST['ad_id']??0);
        $old = db_fetch("SELECT image_url FROM ads WHERE id=?", [$id]);
        if ($old && $old['image_url'] && file_exists(ADS_PATH . $old['image_url'])) {
            @unlink(ADS_PATH . $old['image_url']);
        }
        db_query("DELETE FROM ads WHERE id=?",[$id]);
        flash('success','Ad deleted.');
        redirect(BASE_URL.'/admin/ads.php');
    }
}

$edit_id = (int)($_GET['edit'] ?? 0);
$edit_ad = null;
if ($edit_id) {
    $edit_ad = db_fetch("SELECT * FROM ads WHERE id=?", [$edit_id]);
}

$ads = db_fetchAll("SELECT * FROM ads ORDER BY created_at DESC");
$meta_title = 'Ad Management';
require_once __DIR__.'/partials/admin_head.php';
?>
<div class="admin-content">
  <div class="admin-page-header" style="justify-content:flex-end">
    <?php if ($edit_ad): ?>
    <a href="<?= BASE_URL ?>/admin/ads.php" class="btn btn-primary btn-sm admin-page-action">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Ad
    </a>
    <?php else: ?>
    <button type="button" class="btn btn-primary btn-sm admin-page-action" onclick="openAdModal()">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Ad
    </button>
    <?php endif; ?>
  </div>
  <?php foreach(get_flash() as $f): ?><div class="alert alert-<?= $f['type'] ?>"><?= e($f['msg']) ?></div><?php endforeach; ?>

  <div class="admin-ads-list">
    <?php foreach($ads as $ad): ?>
    <div class="card card-sm" style="margin-bottom:12px">
      <div class="flex gap-3" style="justify-content:space-between;flex-wrap:wrap;gap:8px">
        <div>
          <div style="font-weight:700;font-size:.9rem"><?= e($ad['title']) ?></div>
          <div class="flex gap-2" style="margin-top:4px;flex-wrap:wrap">
            <span class="badge badge-blue"><?= e($ad['content_type']) ?></span>
            <?php if ($ad['placement']): ?>
              <span class="badge badge-purple">Placement ID: <?= (int)$ad['placement'] ?></span>
            <?php else: ?>
              <span class="badge badge-gray">Unassigned</span>
            <?php endif; ?>
            <span class="badge badge-orange">Device: <?= e($ad['device_target']) ?></span>
            <?php if($ad['ad_width'] || $ad['ad_height']): ?>
              <span class="badge badge-gray"><?= (int)$ad['ad_width'] ?>x<?= (int)$ad['ad_height'] ?></span>
            <?php endif; ?>
            <span class="badge badge-<?= $ad['is_active']?'green':'gray' ?>"><?= $ad['is_active']?'Active':'Paused' ?></span>
          </div>
          <div class="text-xs text-muted" style="margin-top:6px">
            &#128065; <?= number_format($ad['impressions']) ?> impressions &nbsp;&#128432; <?= number_format($ad['clicks']) ?> clicks
            <?php if($ad['impressions']>0): ?>
            &nbsp; CTR: <?= round(($ad['clicks']/$ad['impressions'])*100,2) ?>%
            <?php endif; ?>
            &nbsp; Show after category section: <?= (int)$ad['position_after'] ?>
          </div>
        </div>
        <div class="flex gap-1">
          <a href="?edit=<?= $ad['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
          <form method="POST" style="display:inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="ad_id" value="<?= $ad['id'] ?>">
            <button name="action" value="toggle" class="btn btn-sm btn-outline"><?= $ad['is_active']?'Pause':'Resume' ?></button>
            <button name="action" value="delete" class="btn btn-sm btn-outline" style="color:var(--red)" onclick="return confirm('Delete ad?')">&#128465;</button>
          </form>
        </div>
      </div>

      <!-- Preview -->
      <div style="margin-top:10px;padding:10px;background:var(--bg3);border-radius:6px;font-size:.78rem;color:var(--text2);overflow:hidden;max-height:80px">
        <?php if ($ad['content_type'] === 'html'): ?>
          <code><?= e(truncate($ad['content'], 150)) ?></code>
        <?php elseif ($ad['content_type'] === 'image' && $ad['image_url']): ?>
          <div style="display:flex;align-items:center;gap:10px">
            <img src="<?= BASE_URL ?>/uploads/ads/<?= e($ad['image_url']) ?>" style="max-height:40px;border-radius:4px">
            <a href="<?= e($ad['target_url']) ?>" target="_blank" class="text-muted"><?= e(truncate($ad['target_url'], 50)) ?></a>
          </div>
        <?php else: ?>
          <a href="<?= e($ad['target_url']) ?>" target="_blank" class="text-muted"><?= e(truncate($ad['content'] ?: $ad['target_url'], 100)) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(!$ads): ?><p class="text-muted text-sm">No ads created yet.</p><?php endif; ?>
  </div>

  <!-- Ad Popup Modal Form -->
  <div class="modal-backdrop <?= ($edit_ad) ? 'open' : '' ?>" id="ad-form-modal">
    <style>
      #ad-form-modal .modal {
        border-radius: 12px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.3);
      }
      #ad-form-modal .modal-header {
        padding: 14px 20px !important;
      }
      #ad-form-modal .modal-body {
        padding: 0 !important;
      }
      #ad-form-modal .form-group {
        margin-bottom: 8px !important;
      }
      #ad-form-modal .form-label {
        margin-bottom: 3px !important;
        font-size: 0.76rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        opacity: 0.85;
      }
      #ad-form-modal .form-input {
        height: 32px !important;
        padding: 4px 8px !important;
        font-size: 0.8rem !important;
        border-radius: 6px !important;
      }
      #ad-form-modal select.form-input {
        height: 32px !important;
        padding: 0 8px !important;
      }
      #ad-form-modal textarea.form-input {
        height: auto !important;
        font-size: 0.8rem !important;
        padding: 6px 8px !important;
      }
      #ad-form-modal .form-row-grid {
        gap: 8px !important;
      }
      #ad-form-modal .btn {
        height: 32px !important;
        padding: 0 16px !important;
        font-size: 0.8rem !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
      }
      #ad-form-modal .modal-body > div {
        padding: 14px !important;
      }
    </style>
    <div class="modal" style="max-width: 950px; width: 95%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; padding: 0;">
      <div class="modal-header" style="flex-shrink: 0; padding: 20px; border-bottom: 1px solid var(--border);">
        <span class="modal-title" style="font-weight:700;font-size:1.05rem"><?= $edit_ad ? 'Edit Ad' : 'Create Ad' ?></span>
        <button type="button" class="btn-icon" onclick="closeAdModal()" style="background:none;border:none;color:var(--text2);cursor:pointer;font-size:1.25rem">&times;</button>
      </div>
      <div class="modal-body" style="padding: 0; display: flex; flex-wrap: wrap; overflow-y: auto; flex: 1;">
        
        <!-- Left Side: Form -->
        <div style="flex: 1 1 50%; min-width: 300px; padding: 20px; border-right: 1px solid var(--border);">
          <form method="POST" enctype="multipart/form-data" id="ad-form">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="<?= $edit_ad ? 'edit' : 'add' ?>">
            <?php if ($edit_ad): ?>
              <input type="hidden" name="ad_id" value="<?= $edit_ad['id'] ?>">
            <?php endif; ?>

            <div class="form-row-grid">
              <div class="form-group">
                <label class="form-label">Ad Title *</label>
                <input class="form-input" type="text" name="title" id="input_title" required value="<?= e($edit_ad['title'] ?? '') ?>" oninput="updateAdPreview()">
              </div>
              <div class="form-group">
                <label class="form-label">Creative Type</label>
                <select class="form-input form-select" name="content_type" id="content_type_select" onchange="toggleCreativeFields(); updateAdPreview()">
                  <option value="image" <?= ($edit_ad['content_type']??'')==='image'?'selected':'' ?>>Image / Banner File</option>
                  <option value="html" <?= ($edit_ad['content_type']??'')==='html'?'selected':'' ?>>HTML / JS Embed Code</option>
                  <option value="banner" <?= ($edit_ad['content_type']??'')==='banner'?'selected':'' ?>>Redirect URL (Text/Link Only)</option>
                </select>
              </div>
            </div>

            <!-- Image Creative upload -->
            <div class="form-group creative-field" id="field_image">
              <label class="form-label">Banner Image File</label>
              <?php if ($edit_ad && $edit_ad['image_url']): ?>
                <div style="margin-bottom:8px">
                  <img id="current_image" src="<?= BASE_URL ?>/uploads/ads/<?= e($edit_ad['image_url']) ?>" style="max-width:100%;max-height:80px;border-radius:4px">
                </div>
              <?php endif; ?>
              <input class="form-input" type="file" name="image" id="input_image" accept="image/*" onchange="previewImage(this)">
            </div>

            <!-- HTML Code field -->
            <div class="form-group creative-field" id="field_html">
              <label class="form-label">HTML Embed Code / Script</label>
              <textarea class="form-input" name="content" id="input_html" rows="4" placeholder="Enter Javascript/HTML code here" oninput="updateAdPreview()"><?= e($edit_ad['content'] ?? '') ?></textarea>
            </div>

            <div class="form-row-grid">
              <div class="form-group">
                <label class="form-label">Target URL / Link</label>
                <input class="form-input" type="url" name="target_url" id="input_url" placeholder="https://advertiser.com/landing" value="<?= e($edit_ad['target_url'] ?? '') ?>" oninput="updateAdPreview()">
              </div>
              <div class="form-group">
                <label class="form-label">Show After Section</label>
                <input class="form-input" type="number" name="position_after" min="1" value="<?= (int)($edit_ad['position_after'] ?? 1) ?>">
              </div>
            </div>

            <div class="form-row-grid">
              <div class="form-group">
                <label class="form-label">Device Targeting</label>
                <select class="form-input form-select" name="device_target">
                  <option value="all" <?= ($edit_ad['device_target']??'')==='all'?'selected':'' ?>>All Devices</option>
                  <option value="desktop" <?= ($edit_ad['device_target']??'')==='desktop'?'selected':'' ?>>Desktop Only</option>
                  <option value="mobile" <?= ($edit_ad['device_target']??'')==='mobile'?'selected':'' ?>>Mobile Only</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Custom Width (px)</label>
                <input class="form-input" type="number" name="ad_width" id="input_width" placeholder="e.g. 728" value="<?= e($edit_ad['ad_width'] ?? '') ?>" oninput="updateAdPreview()">
              </div>
              <div class="form-group">
                <label class="form-label">Custom Height (px)</label>
                <input class="form-input" type="number" name="ad_height" id="input_height" placeholder="e.g. 90" value="<?= e($edit_ad['ad_height'] ?? '') ?>" oninput="updateAdPreview()">
              </div>
            </div>

            <div class="form-row-grid">
              <div class="form-group">
                <label class="form-label">Start Date</label>
                <input class="form-input" type="date" name="start_date" value="<?= e($edit_ad['start_date'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">End Date</label>
                <input class="form-input" type="date" name="end_date" value="<?= e($edit_ad['end_date'] ?? '') ?>">
              </div>
            </div>

            <div class="flex gap-2" style="margin-top:20px;justify-content:flex-start">
              <button type="submit" class="btn btn-primary">Save Ad</button>
              <button type="button" class="btn btn-outline" onclick="closeAdModal()">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Right Side: Preview -->
        <div style="flex: 1 1 40%; min-width: 300px; padding: 20px; background: var(--bg2); display: flex; flex-direction: column;">
          <h3 style="font-size:1rem; font-weight:700; margin-bottom:15px; color: var(--text);">Live Preview</h3>
          <div style="flex:1; border: 1px dashed var(--border); border-radius: 8px; background: var(--bg3); display: flex; align-items: center; justify-content: center; padding: 15px; overflow: hidden; min-height: 200px;" id="preview_container">
             <div class="text-muted text-sm">Preview will appear here</div>
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

<script>
let uploadedImageSrc = '';

function toggleCreativeFields() {
  const type = document.getElementById('content_type_select').value;
  document.getElementById('field_image').style.display = type === 'image' ? 'block' : 'none';
  document.getElementById('field_html').style.display = type === 'html' ? 'block' : 'none';
}
toggleCreativeFields();

function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      uploadedImageSrc = e.target.result;
      updateAdPreview();
    }
    reader.readAsDataURL(input.files[0]);
  } else {
    uploadedImageSrc = '';
    updateAdPreview();
  }
}

let previewTimeout = null;
function updateAdPreview() {
  clearTimeout(previewTimeout);
  previewTimeout = setTimeout(renderAdPreview, 300);
}

function renderAdPreview() {
  const type = document.getElementById('content_type_select').value;
  const container = document.getElementById('preview_container');
  const title = document.getElementById('input_title').value || 'Ad Title';
  const url = document.getElementById('input_url').value || '#';
  const width = document.getElementById('input_width').value;
  const height = document.getElementById('input_height').value;
  
  let sizeStyle = '';
  if (width) sizeStyle += `width:${width}px;max-width:100%;`;
  if (height) sizeStyle += `height:${height}px;`;

  if (type === 'image') {
    let imgSrc = uploadedImageSrc;
    if (!imgSrc) {
      const currentImg = document.getElementById('current_image');
      imgSrc = currentImg ? currentImg.src : 'https://placehold.co/728x90?text=Ad+Image';
    }
    container.innerHTML = `<a href="${url}" target="_blank" style="display:inline-block;${sizeStyle}"><img src="${imgSrc}" alt="${title}" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:4px"></a>`;
  } else if (type === 'html') {
    const htmlContent = document.getElementById('input_html').value || '<div style="color:var(--text2);text-align:center">HTML Embed Code</div>';
    const iframe = document.createElement('iframe');
    iframe.style.cssText = `border:none; width:100%; height:100%; min-height: 200px; ${sizeStyle}`;
    iframe.srcdoc = `<!DOCTYPE html><html><head><style>body{margin:0;padding:0;display:flex;justify-content:center;align-items:center;height:100%;overflow:hidden;}</style></head><body>${htmlContent}</body></html>`;
    container.innerHTML = '';
    container.appendChild(iframe);
  } else if (type === 'banner') {
    container.innerHTML = `<a href="${url}" target="_blank" style="font-weight:bold;color:var(--accent);text-decoration:underline;font-size:1.1rem;${sizeStyle}">${title}</a>`;
  }
}

// Initial preview update
setTimeout(updateAdPreview, 100);

function openAdModal() {
  <?php if ($edit_ad): ?>
    window.location.href = 'ads.php';
    return;
  <?php endif; ?>
  document.getElementById('ad-form-modal').classList.add('open');
  updateAdPreview();
}

function closeAdModal() {
  document.getElementById('ad-form-modal').classList.remove('open');
  <?php if ($edit_ad): ?>
    window.location.href = 'ads.php';
  <?php endif; ?>
}

document.getElementById('ad-form-modal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeAdModal();
  }
});
</script>
<?php require_once __DIR__.'/partials/admin_foot.php'; ?>
