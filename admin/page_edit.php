<?php
// ============================================================
// FreeHub.Live — Admin Page Builder / Editor (CMS)
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
$page = null;

if ($id > 0) {
    $page = db_fetch("SELECT * FROM pages WHERE id = ?", [$id]);
    if (!$page) {
        flash('error', 'Page not found.');
        redirect(BASE_URL . '/admin/pages.php');
    }
}

$sections = db_fetchAll("SELECT * FROM footer_sections ORDER BY sort_order ASC, id ASC");

// Define header action buttons to render in sticky header (removes second heading, moves button)
$header_actions = '
    <button type="button" class="btn btn-outline btn-sm" id="btn-preview-page" style="margin-right: 8px; display: inline-flex; align-items: center; gap: 6px;">
        👁️ Preview Page
    </button>
    <a href="' . BASE_URL . '/admin/pages.php" class="btn btn-outline btn-sm">
        ← Back to Pages
    </a>
';

// Handle Page Save
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf'] ?? '')) {
    $title = trim($_POST['title'] ?? '');
    $slug = slugify(trim($_POST['slug'] ?? ''));
    $content = $_POST['content'] ?? ''; // Keep raw HTML from TinyMCE
    $status = in_array($_POST['status'] ?? '', ['published', 'draft', 'private', 'scheduled'], true) ? $_POST['status'] : 'draft';
    
    $publish_at = null;
    if ($status === 'scheduled') {
        $publish_at = !empty($_POST['publish_at']) ? date('Y-m-d H:i:s', strtotime($_POST['publish_at'])) : date('Y-m-d H:i:s');
    }
    
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_desc = trim($_POST['meta_desc'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');

    if (empty($title)) {
        $errors[] = 'Page title is required.';
    }
    if (empty($slug)) {
        $errors[] = 'URL slug is required.';
    }

    // Check slug uniqueness
    $slug_check = db_fetch(
        "SELECT id FROM pages WHERE slug = ? AND id != ?",
        [$slug, $id]
    );
    if ($slug_check) {
        $errors[] = 'A page with this URL slug already exists. Please choose a different slug.';
    }

    if (empty($errors)) {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'meta_title' => $meta_title ?: null,
            'meta_desc' => $meta_desc ?: null,
            'meta_keywords' => $meta_keywords ?: null,
            'status' => $status,
            'publish_at' => $publish_at,
            'footer_section_id' => $_POST['footer_section_id'] === '' ? null : (int)$_POST['footer_section_id'],
        ];

        if ($page) {
            db_update('pages', $data, 'id = ?', [$id]);
            flash('success', 'Page updated successfully.');
        } else {
            db_insert('pages', $data);
            flash('success', 'Page created successfully.');
        }
        redirect(BASE_URL . '/admin/pages.php');
    }
}

$meta_title = $page ? 'Edit Page: ' . $page['title'] : 'Create Page';
require_once __DIR__ . '/partials/admin_head.php';
?>
<div class="admin-content">
    
    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="card" style="margin-bottom: 20px; border-color: var(--red); background: rgba(239, 68, 68, 0.05); color: var(--red); padding: 12px 16px;">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="page-editor-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <!-- Gutenberg-Style Clean Sheet Card -->
        <div class="card" style="padding: 40px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--border); box-shadow: var(--shadow-sm); margin-bottom: 24px;">
            <!-- Gutenberg large borderless title -->
            <input type="text" id="title" name="title" value="<?= e($_POST['title'] ?? ($page['title'] ?? '')) ?>" required placeholder="Add title..." style="font-size: 2.2rem; font-weight: 800; border: none; background: transparent; width: 100%; margin-bottom: 24px; padding: 0; outline: none; border-bottom: 1px solid var(--border); border-radius: 0; padding-bottom: 12px; color: var(--text);">
            
            <!-- Page Content Editor -->
            <div style="margin-bottom: 0;">
                <textarea id="editor" name="content" style="visibility: hidden; min-height: 500px;"><?= $_POST['content'] ?? ($page['content'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-row-grid" style="align-items: stretch; margin-bottom: 24px;">
            <!-- ⚙️ Publishing & Visibility settings -->
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border-radius: 12px;">
                <div>
                    <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text2);">⚙️ Publishing Settings</h3>
                    
                    <div style="margin-bottom: 16px;">
                        <label for="status" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.88rem;">Page Status / Visibility</label>
                        <select id="status" name="status" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg3); color: var(--text); outline: none; cursor: pointer;">
                            <option value="published" <?= (isset($_POST['status']) ? $_POST['status'] === 'published' : ($page && $page['status'] === 'published')) ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= (isset($_POST['status']) ? $_POST['status'] === 'draft' : (!$page || $page['status'] === 'draft')) ? 'selected' : '' ?>>Draft</option>
                            <option value="private" <?= (isset($_POST['status']) ? $_POST['status'] === 'private' : ($page && $page['status'] === 'private')) ? 'selected' : '' ?>>Private</option>
                            <option value="scheduled" <?= (isset($_POST['status']) ? $_POST['status'] === 'scheduled' : ($page && $page['status'] === 'scheduled')) ? 'selected' : '' ?>>Scheduled</option>
                        </select>
                    </div>

                    <div id="schedule-datetime-container" style="display: <?= (isset($_POST['status']) ? $_POST['status'] === 'scheduled' : ($page && $page['status'] === 'scheduled')) ? 'block' : 'none' ?>; margin-bottom: 16px;">
                        <label for="publish_at" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.88rem;">Schedule Publish Date & Time</label>
                        <input type="datetime-local" id="publish_at" name="publish_at" value="<?= $page && $page['publish_at'] ? date('Y-m-d\TH:i', strtotime($page['publish_at'])) : '' ?>" style="width: 100%; padding: 9px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg3); color: var(--text); outline: none;">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="footer_section_id" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.88rem;">Footer Section / Group</label>
                        <select id="footer_section_id" name="footer_section_id" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg3); color: var(--text); outline: none; cursor: pointer;">
                            <option value="">-- None (Unassigned) --</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= $sec['id'] ?>" <?= (isset($_POST['footer_section_id']) ? (int)$_POST['footer_section_id'] === $sec['id'] : ($page && (int)$page['footer_section_id'] === $sec['id'])) ? 'selected' : '' ?>>
                                    <?= e($sec['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 0;">
                        <label for="slug" style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.88rem;">URL Slug</label>
                        <input type="text" id="slug" name="slug" value="<?= e($_POST['slug'] ?? ($page['slug'] ?? '')) ?>" required placeholder="e.g. terms-conditions" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg3); color: var(--text); outline: none; font-family: monospace;">
                        <small style="display: block; margin-top: 6px; color: var(--text2); font-size: 0.75rem;">URL path: /page.php?slug=<span>[slug]</span></small>
                    </div>
                </div>
            </div>

            <!-- 🔍 SEO settings -->
            <div class="card" style="border-radius: 12px;">
                <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text2);">🔍 SEO Metadata</h3>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label for="meta_title" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.88rem;">SEO Meta Title</label>
                        <input type="text" id="meta_title" name="meta_title" value="<?= e($_POST['meta_title'] ?? ($page['meta_title'] ?? '')) ?>" placeholder="Leave blank to use Page Title" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg3); color: var(--text); outline: none;">
                    </div>
                    <div>
                        <label for="meta_desc" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.88rem;">SEO Meta Description</label>
                        <textarea id="meta_desc" name="meta_desc" rows="3" placeholder="Leave blank to auto-generate from content" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg3); color: var(--text); outline: none; font-family: inherit; resize: vertical;"><?= e($_POST['meta_desc'] ?? ($page['meta_desc'] ?? '')) ?></textarea>
                    </div>
                    <div>
                        <label for="meta_keywords" style="display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.88rem;">SEO Meta Keywords</label>
                        <input type="text" id="meta_keywords" name="meta_keywords" value="<?= e($_POST['meta_keywords'] ?? ($page['meta_keywords'] ?? '')) ?>" placeholder="e.g. key1, key2, keywords" style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg3); color: var(--text); outline: none;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex" style="gap: 12px; justify-content: flex-end; margin-bottom: 40px;">
            <a href="<?= BASE_URL ?>/admin/pages.php" class="btn btn-outline" style="min-width: 100px; text-align: center;">Cancel</a>
            <button type="submit" class="btn btn-primary" style="min-width: 120px;">Save Page</button>
        </div>
    </form>
</div>

<!-- Responsive Live Preview Modal -->
<div id="preview-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 999999; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: var(--bg2); width: 100%; max-width: 1200px; height: 92%; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow);">
        
        <!-- Modal Toolbar -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid var(--border); background: var(--bg3);">
            <div style="font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <span>👁️ Live Preview</span>
                <span id="preview-page-title" style="color: var(--text2); font-size: 0.88rem; font-weight: 500;"></span>
            </div>
            
            <!-- Viewport controls -->
            <div style="display: flex; gap: 4px; background: var(--bg); padding: 4px; border-radius: 8px; border: 1px solid var(--border);">
                <button type="button" class="viewport-btn active" data-width="100%" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; color: var(--text); background: var(--bg2); cursor: pointer; transition: all 0.2s;">🖥️ Desktop</button>
                <button type="button" class="viewport-btn" data-width="768px" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; color: var(--text2); background: transparent; cursor: pointer; transition: all 0.2s;">📁 Tablet</button>
                <button type="button" class="viewport-btn" data-width="375px" style="padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; color: var(--text2); background: transparent; cursor: pointer; transition: all 0.2s;">📱 Mobile</button>
            </div>
            
            <button type="button" id="close-preview-modal" style="font-size: 1.6rem; font-weight: 300; color: var(--text2); cursor: pointer; background: none; border: none; padding: 0 4px; line-height: 1;">&times;</button>
        </div>
        
        <!-- Frame view -->
        <div style="flex: 1; background: #0b0f19; display: flex; justify-content: center; align-items: center; padding: 20px; overflow: hidden; position: relative;">
            <iframe id="preview-iframe" style="width: 100%; height: 100%; border: none; background: var(--bg); border-radius: 8px; box-shadow: 0 12px 36px rgba(0,0,0,0.6); transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);"></iframe>
        </div>
    </div>
</div>

<!-- TinyMCE Editor Loader & State Manager -->
<script>
(function() {
    let editorInitialized = false;

    function showFallbackTextarea() {
        const textarea = document.getElementById('editor');
        if (textarea) {
            textarea.style.visibility = 'visible';
            textarea.style.display = 'block';
            textarea.style.background = 'var(--bg3)';
            textarea.style.color = 'var(--text)';
            textarea.style.border = '1px solid var(--border)';
            textarea.style.borderRadius = '8px';
            textarea.style.padding = '16px';
            textarea.style.fontFamily = "'Inter', sans-serif";
        }
    }

    // Destroys past instances and initializes a clean TinyMCE block editor
    function initTinyMCEEditor() {
        if (typeof tinymce !== 'undefined') {
            try {
                tinymce.remove();
            } catch (e) {
                console.error('Error removing tinymce editors:', e);
            }
        }

        const rootStyle = getComputedStyle(document.documentElement);
        const editorBg = rootStyle.getPropertyValue('--bg2').trim() || '#1a1a1a';
        const editorText = rootStyle.getPropertyValue('--text').trim() || '#efefef';
        const editorAccent = rootStyle.getPropertyValue('--accent').trim() || '#6366f1';

        if (typeof tinymce !== 'undefined') {
            try {
                tinymce.init({
                    selector: '#editor',
                    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table wordcount',
                    toolbar: 'undo redo | blocks | fontfamily fontsize | bold italic underline strikethrough forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | code fullscreen',
                    height: 600,
                    skin: 'oxide-dark',
                    content_css: 'dark',
                    branding: false,
                    promotion: false,
                    relative_urls: false,
                    remove_script_host: false,
                    convert_urls: true,
                    content_style: "@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap'); body { font-family: 'Inter', sans-serif; font-size: 15px; line-height: 1.8; padding: 24px; background-color: " + editorBg + " !important; color: " + editorText + " !important; } h1, h2, h3, h4 { color: " + editorText + " !important; font-weight: 700; } a { color: " + editorAccent + " !important; }",
                    image_dimensions: true,
                    image_description: true,
                    images_upload_url: 'page_upload.php',
                    images_upload_handler: function (blobInfo, success, failure, progress) {
                        var xhr, formData;
                        xhr = new XMLHttpRequest();
                        xhr.withCredentials = false;
                        xhr.open('POST', 'page_upload.php');
                        
                        xhr.upload.onprogress = function (e) {
                            progress(e.loaded / e.total * 100);
                        };

                        xhr.onload = function() {
                            var json;
                            if (xhr.status < 200 || xhr.status >= 300) {
                                failure('HTTP Error: ' + xhr.status);
                                return;
                            }
                            try {
                                json = JSON.parse(xhr.responseText);
                            } catch(err) {
                                failure('Invalid JSON: ' + xhr.responseText);
                                return;
                            }
                            if (!json || typeof json.location != 'string') {
                                failure(json.error || 'Invalid response from server');
                                return;
                            }
                            success(json.location);
                        };

                        xhr.onerror = function () {
                            failure('Image upload failed due to a network error.');
                        };

                        formData = new FormData();
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        formData.append('csrf', '<?= csrf_token() ?>');

                        xhr.send(formData);
                    }
                });
                editorInitialized = true;
            } catch (err) {
                console.error('TinyMCE initialization failed:', err);
                showFallbackTextarea();
            }
        } else {
            showFallbackTextarea();
        }

        // Live Theme Synchronization Observer
        if (window._themeObserver) {
            window._themeObserver.disconnect();
        }
        window._themeObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'data-theme') {
                    const newStyle = getComputedStyle(document.documentElement);
                    const newBg = newStyle.getPropertyValue('--bg2').trim();
                    const newText = newStyle.getPropertyValue('--text').trim();
                    
                    if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                        const editorBody = tinymce.activeEditor.getBody();
                        if (editorBody) {
                            editorBody.style.setProperty('background-color', newBg, 'important');
                            editorBody.style.setProperty('color', newText, 'important');
                        }
                    }
                }
            });
        });
        window._themeObserver.observe(document.documentElement, { attributes: true });
    }

    // Dynamic, sequenced script loader to prevent race conditions during PJAX transitions
    function loadAndInitEditor() {
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        const isEdit = <?= $page ? 'true' : 'false' ?>;

        // Auto Slug Generation
        if (!isEdit && titleInput && slugInput) {
            titleInput.addEventListener('input', function() {
                slugInput.value = titleInput.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            });
        }

        // Toggle Schedule Datepicker
        const statusSelect = document.getElementById('status');
        const scheduleContainer = document.getElementById('schedule-datetime-container');
        const publishAtInput = document.getElementById('publish_at');
        if (statusSelect && scheduleContainer) {
            statusSelect.addEventListener('change', function() {
                if (this.value === 'scheduled') {
                    scheduleContainer.style.display = 'block';
                    if (publishAtInput) publishAtInput.required = true;
                } else {
                    scheduleContainer.style.display = 'none';
                    if (publishAtInput) publishAtInput.required = false;
                }
            });
        }

        // Load TinyMCE Library from CDN if not already loaded, then initialize
        if (typeof tinymce !== 'undefined') {
            initTinyMCEEditor();
        } else {
            const scriptId = 'tinymce-cdn-script';
            if (!document.getElementById(scriptId)) {
                const script = document.createElement('script');
                script.id = scriptId;
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js';
                script.referrerPolicy = 'origin';
                script.onload = function() {
                    initTinyMCEEditor();
                };
                document.body.appendChild(script);
            } else {
                if (window._tinymceInterval) {
                    clearInterval(window._tinymceInterval);
                }
                let attempts = 0;
                window._tinymceInterval = setInterval(function() {
                    attempts++;
                    if (typeof tinymce !== 'undefined') {
                        clearInterval(window._tinymceInterval);
                        window._tinymceInterval = null;
                        initTinyMCEEditor();
                    }
                    if (attempts > 50) {
                        clearInterval(window._tinymceInterval);
                        window._tinymceInterval = null;
                    }
                }, 100);
            }
        }

        // Live Preview setup
        const previewBtn = document.getElementById('btn-preview-page');
        const previewModal = document.getElementById('preview-modal');
        const previewIframe = document.getElementById('preview-iframe');
        const closePreviewBtn = document.getElementById('close-preview-modal');

        if (previewBtn && previewModal && previewIframe) {
            previewBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const title = document.getElementById('title').value || 'Untitled Page';
                const content = (typeof tinymce !== 'undefined' && tinymce.activeEditor) ? tinymce.activeEditor.getContent() : document.getElementById('editor').value;
                
                document.getElementById('preview-page-title').textContent = title;
                
                const theme = document.documentElement.getAttribute('data-theme') || 'dark-minimal';
                const origin = window.location.origin;
                
                const iframeDoc = previewIframe.contentWindow.document;
                iframeDoc.open();
                iframeDoc.write(`
<!DOCTYPE html>
<html lang="en" data-theme="${theme}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${title}</title>
    <link rel="stylesheet" href="${origin}/assets/css/main.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding: 40px 16px; min-height: 100vh; display: flex; justify-content: center; }
        .wysiwyg-content { font-size: 0.96rem; line-height: 1.8; color: var(--text); }
        .wysiwyg-content p { margin-bottom: 20px; }
        .wysiwyg-content h1, .wysiwyg-content h2, .wysiwyg-content h3, .wysiwyg-content h4 { color: var(--text); font-weight: 700; margin-top: 32px; margin-bottom: 16px; line-height: 1.4; }
        .wysiwyg-content h1 { font-size: 1.8rem; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
        .wysiwyg-content h2 { font-size: 1.45rem; }
        .wysiwyg-content h3 { font-size: 1.25rem; }
        .wysiwyg-content ul, .wysiwyg-content ol { margin-top: 8px; margin-bottom: 20px; padding-left: 24px; }
        .wysiwyg-content li { margin-bottom: 8px; }
        .wysiwyg-content ul { list-style-type: disc; }
        .wysiwyg-content ol { list-style-type: decimal; }
        .wysiwyg-content table { width: 100% !important; border-collapse: collapse; margin: 24px 0; font-size: 0.88rem; background: var(--bg3); border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }
        .wysiwyg-content th, .wysiwyg-content td { padding: 12px 16px; border: 1px solid var(--border); text-align: left; }
        .wysiwyg-content th { background: rgba(255, 255, 255, 0.03); font-weight: 700; color: var(--text); }
        .wysiwyg-content img { max-width: 100%; height: auto !important; border-radius: 8px; margin: 16px 0; display: block; }
        .wysiwyg-content iframe, .wysiwyg-content video { max-width: 100%; width: 100%; aspect-ratio: 16/9; border-radius: 8px; border: 0; margin: 20px 0; }
        .wysiwyg-content a { color: var(--accent); text-decoration: underline; }
        .wysiwyg-content blockquote { border-left: 4px solid var(--accent); background: var(--bg3); padding: 16px 20px; margin: 20px 0; border-radius: 0 8px 8px 0; font-style: italic; color: var(--text2); }
    </style>
</head>
<body class="public-page">
    <div style="width: 100%; max-width: 800px;">
        <article style="padding: 40px; border-radius: 12px; background: var(--bg2); border: 1px solid var(--border);">
            <header style="margin-bottom: 36px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
                <h1 style="font-size: 2.2rem; font-weight: 800; line-height: 1.25; margin-bottom: 8px; color: var(--text);">${title}</h1>
                <div style="font-size: 0.8rem; color: var(--text2);">
                    <span>Published by FreeHub</span> • <span>Updated Just Now (Preview)</span>
                </div>
            </header>
            <div class="wysiwyg-content">
                ${content}
            </div>
        </article>
    </div>
</body>
</html>
                `);
                iframeDoc.close();
                
                const desktopBtn = document.querySelector('.viewport-btn[data-width="100%"]');
                if (desktopBtn) desktopBtn.click();
                
                previewModal.style.display = 'flex';
            });
        }

        if (closePreviewBtn && previewModal) {
            closePreviewBtn.addEventListener('click', function() {
                previewModal.style.display = 'none';
            });
            previewModal.addEventListener('click', function(e) {
                if (e.target === previewModal) {
                    previewModal.style.display = 'none';
                }
            });
        }

        const viewportBtns = document.querySelectorAll('.viewport-btn');
        viewportBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                viewportBtns.forEach(b => {
                    b.classList.remove('active');
                    b.style.color = 'var(--text2)';
                    b.style.background = 'transparent';
                });
                this.classList.add('active');
                this.style.color = 'var(--text)';
                this.style.background = 'var(--bg2)';
                
                const width = this.getAttribute('data-width');
                previewIframe.style.width = width;
            });
        });
    }

    function debouncedLoadAndInitEditor() {
        if (window._editorInitTimeout) {
            clearTimeout(window._editorInitTimeout);
        }
        window._editorInitTimeout = setTimeout(function() {
            loadAndInitEditor();
        }, 30);
    }

    // Initial Trigger
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        debouncedLoadAndInitEditor();
    } else {
        document.addEventListener('DOMContentLoaded', debouncedLoadAndInitEditor);
    }

    // Trigger on PJAX AJAX dashboard transitions
    if (window._onDashboardPageLoaded) {
        document.removeEventListener('dashboard-page-loaded', window._onDashboardPageLoaded);
    }
    window._onDashboardPageLoaded = function(e) {
        if (e.detail && e.detail.url && (e.detail.url.includes('page_edit.php') || window.location.href.includes('page_edit.php'))) {
            debouncedLoadAndInitEditor();
        }
    };
    document.addEventListener('dashboard-page-loaded', window._onDashboardPageLoaded);
})();
</script>
