<?php
/**
 * Post Editor - VoidForge CMS
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/taxonomy.php';
require_once CMS_ROOT . '/includes/seo.php';

Post::init();
Plugin::init(); // This loads the Anvil plugin if active
Taxonomy::init();

User::startSession();
User::requireLogin();

$postId = (int)($_GET['id'] ?? 0);
$postType = $_GET['type'] ?? 'post';

if ($postId) {
    $post = Post::find($postId);
    if (!$post) {
        redirect(ADMIN_URL . '/posts.php?type=' . $postType);
    }
    $postType = $post['post_type'];
} else {
    $post = null;
}

$typeConfig = Post::getType($postType);
if (!$typeConfig) {
    redirect(ADMIN_URL . '/');
}

// Classic editor is always used in backend
// Anvil Live (frontend editor) is accessed via button

$pageTitle = $post ? 'Edit ' . $typeConfig['singular'] : 'New ' . $typeConfig['singular'];
$errors = [];

// Handle restore revision action
if (isset($_GET['restore_revision']) && $post && verifyCsrf($_GET['csrf'] ?? '')) {
    $revisionId = (int)$_GET['restore_revision'];
    try {
        if (Post::restoreRevision($revisionId)) {
            setFlash('success', 'Revision restored successfully.');
            redirect(ADMIN_URL . '/post-edit.php?id=' . $post['id']);
        } else {
            setFlash('error', 'Failed to restore revision.');
        }
    } catch (Exception $e) {
        setFlash('error', 'Revisions feature not available. Please run system update.');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $saveAction = $_POST['save_action'] ?? 'draft';
    $scheduleDate = trim($_POST['schedule_date'] ?? '');
    $scheduleTime = trim($_POST['schedule_time'] ?? '');
    
    // Determine status based on save action
    if ($saveAction === 'schedule' && $scheduleDate && $scheduleTime) {
        $scheduledAt = $scheduleDate . ' ' . $scheduleTime . ':00';
        $status = 'scheduled';
    } elseif ($saveAction === 'publish') {
        $status = 'published';
        $scheduledAt = null;
    } else {
        $status = 'draft';
        $scheduledAt = null;
    }
    
    $data = [
        'post_type' => $postType,
        'title' => trim($_POST['title'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'content' => $_POST['content'] ?? '',
        'excerpt' => trim($_POST['excerpt'] ?? ''),
        'status' => $status,
        'featured_image_id' => !empty($_POST['featured_image_id']) ? (int)$_POST['featured_image_id'] : null,
    ];

    if ($typeConfig['hierarchical']) {
        $data['parent_id'] = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $data['menu_order'] = (int)($_POST['menu_order'] ?? 0);
    }

    if (empty($data['title'])) {
        $errors['title'] = 'Title is required';
    }

    if (empty($data['slug'])) {
        $data['slug'] = slugify($data['title']);
    } else {
        $data['slug'] = slugify($data['slug']);
    }

    if (empty($errors)) {
        if ($post) {
            // Create a revision before updating (only for existing posts)
            try {
                Post::createRevision($post['id']);
            } catch (Exception $e) {
                // Revisions table may not exist yet, continue without revision
            }
            
            if ($data['slug'] !== $post['slug']) {
                $data['slug'] = uniqueSlug($data['slug'], $postType, $post['id']);
            }
            Post::update($post['id'], $data);
            $savedPostId = $post['id'];
            
            // Handle scheduled_at
            if ($status === 'scheduled' && !empty($scheduledAt)) {
                Post::schedule($savedPostId, $scheduledAt);
            } elseif (($post['status'] ?? '') === 'scheduled' && $status !== 'scheduled') {
                // Clear scheduled_at if no longer scheduled
                try {
                    Database::execute(
                        "UPDATE " . Database::table('posts') . " SET scheduled_at = NULL WHERE id = ?",
                        [$savedPostId]
                    );
                } catch (Exception $e) {
                    // Column might not exist yet
                }
            }
            
            $message = $status === 'scheduled' 
                ? $typeConfig['singular'] . ' scheduled for ' . formatDate($scheduledAt, 'M j, Y g:i a') . '.'
                : $typeConfig['singular'] . ' updated successfully.';
            setFlash('success', $message);
        } else {
            $data['slug'] = uniqueSlug($data['slug'], $postType);
            $savedPostId = Post::create($data);
            
            // Handle scheduled_at for new posts
            if ($status === 'scheduled' && !empty($scheduledAt)) {
                Post::schedule($savedPostId, $scheduledAt);
            }
            
            $message = $status === 'scheduled' 
                ? $typeConfig['singular'] . ' scheduled for ' . formatDate($scheduledAt, 'M j, Y g:i a') . '.'
                : $typeConfig['singular'] . ' created successfully.';
            setFlash('success', $message);
        }
        
        // Save custom fields
        $customFieldsDefs = get_post_type_fields($postType);
        foreach ($customFieldsDefs as $field) {
            $fieldKey = $field['key'];
            $fieldType = $field['type'];
            
            // Handle repeater fields
            if ($fieldType === 'repeater') {
                $repeaterData = [];
                $rowCount = (int)($_POST['cf_' . $fieldKey . '_count'] ?? 0);
                for ($i = 0; $i < $rowCount; $i++) {
                    $row = [];
                    foreach ($field['sub_fields'] ?? [] as $sf) {
                        $sfKey = 'cf_' . $fieldKey . '_' . $i . '_' . $sf['key'];
                        $row[$sf['key']] = $_POST[$sfKey] ?? '';
                    }
                    $repeaterData[] = $row;
                }
                set_custom_field($fieldKey, json_encode($repeaterData), $savedPostId);
            }
            // Handle group fields
            elseif ($fieldType === 'group') {
                $groupData = [];
                foreach ($field['sub_fields'] ?? [] as $sf) {
                    $sfKey = 'cf_' . $fieldKey . '_' . $sf['key'];
                    $groupData[$sf['key']] = $_POST[$sfKey] ?? '';
                }
                set_custom_field($fieldKey, json_encode($groupData), $savedPostId);
            }
            // Handle regular fields
            elseif (isset($_POST['cf_' . $fieldKey])) {
                $fieldValue = $_POST['cf_' . $fieldKey];
                // Handle checkbox (unchecked = not sent)
                if ($fieldType === 'checkbox') {
                    $fieldValue = $fieldValue ? '1' : '0';
                }
                set_custom_field($fieldKey, $fieldValue, $savedPostId);
            } elseif ($fieldType === 'checkbox') {
                // Checkbox unchecked
                set_custom_field($fieldKey, '0', $savedPostId);
            }
        }
        
        // Save taxonomy terms
        $postTaxonomies = Taxonomy::getForPostType($postType);
        foreach ($postTaxonomies as $taxSlug => $tax) {
            $termIds = $_POST['tax_' . $taxSlug] ?? [];
            if (!is_array($termIds)) {
                $termIds = array_filter(array_map('intval', explode(',', $termIds)));
            }
            Taxonomy::setPostTerms($savedPostId, $taxSlug, $termIds);
        }
        
        // Save comments enabled setting
        $commentsEnabled = isset($_POST['comments_enabled']) ? '1' : '0';
        Post::setMeta($savedPostId, '_comments_enabled', $commentsEnabled);
        
        // Save SEO meta data
        SEO::savePostMeta($savedPostId, [
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'seo_keywords' => $_POST['seo_keywords'] ?? '',
            'seo_canonical' => $_POST['seo_canonical'] ?? '',
            'seo_robots_index' => $_POST['seo_robots_index'] ?? 'index',
            'seo_robots_follow' => $_POST['seo_robots_follow'] ?? 'follow',
            'seo_og_title' => $_POST['seo_og_title'] ?? '',
            'seo_og_description' => $_POST['seo_og_description'] ?? '',
            'seo_og_image' => (int)($_POST['seo_og_image'] ?? 0),
            'seo_twitter_title' => $_POST['seo_twitter_title'] ?? '',
            'seo_twitter_description' => $_POST['seo_twitter_description'] ?? '',
            'seo_focus_keyword' => $_POST['seo_focus_keyword'] ?? '',
        ]);
        
        redirect(ADMIN_URL . '/post-edit.php?id=' . $savedPostId);
    }
}

$parentOptions = [];
if ($typeConfig['hierarchical']) {
    $parentOptions = Post::getParentOptions($postType, $post['id'] ?? null);
}

$featuredImage = null;
if ($post && $post['featured_image_id']) {
    $featuredImage = Media::find($post['featured_image_id']);
}

// Get comments enabled setting for this post
$postCommentsEnabled = true; // Default to enabled
if ($post) {
    $commentsEnabledMeta = Post::getMeta($post['id'], '_comments_enabled');
    // If meta exists and is '0' or 0, comments are disabled
    // If meta doesn't exist (null), default to enabled
    $postCommentsEnabled = ($commentsEnabledMeta !== '0' && $commentsEnabledMeta !== 0);
}

// Get custom fields for this post type
$customFieldsDefs = get_post_type_fields($postType);
$customFieldsValues = [];

// Get taxonomies for this post type
$postTaxonomies = Taxonomy::getForPostType($postType);
$postTermsData = [];

if ($post && $post['id']) {
    foreach ($postTaxonomies as $taxSlug => $tax) {
        $postTermsData[$taxSlug] = Taxonomy::getPostTermIds($post['id'], $taxSlug);
    }
    $customFieldsValues = get_all_custom_fields($post['id']);
}

// Load SEO meta data
$seoMeta = [];
if ($post && $post['id']) {
    $seoMeta = SEO::getPostMeta($post['id']);
}

$permalinkBase = SITE_URL . '/';
if ($postType !== 'page') {
    $permalinkBase .= $postType . '/';
}

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* Post Editor Styles */
.editor-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
    align-items: start;
}

.editor-main { min-width: 0; }

.editor-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    line-height: 1.5;
    color: var(--text-primary);
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: all 0.15s;
    font-family: inherit;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--forge-primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.form-textarea { min-height: 100px; resize: vertical; }

/* Permalink */
.permalink-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--bg-card-header);
    border-radius: var(--border-radius);
    font-size: 0.8125rem;
}

.permalink-wrap svg { color: var(--text-muted); flex-shrink: 0; }
.permalink-base { color: var(--text-muted); }
.permalink-slug { color: var(--forge-primary); font-weight: 500; }

.permalink-edit {
    margin-left: auto;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    cursor: pointer;
    color: var(--text-secondary);
}

.permalink-edit:hover { border-color: var(--forge-primary); color: var(--forge-primary); }

/* Sidebar cards */
.sidebar-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}

.sidebar-card-header {
    padding: 0.875rem 1rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
    font-weight: 600;
    font-size: 0.875rem;
}

.sidebar-card-body { padding: 1rem; }

/* Featured image */
.featured-image-preview {
    margin-bottom: 0.75rem;
}

.featured-image-preview img {
    width: 100%;
    border-radius: var(--border-radius);
    display: block;
}

/* Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.modal-backdrop.active { display: flex; }

.modal-box {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    width: 100%;
    max-width: 700px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title { font-weight: 600; }

.modal-close {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1.5rem;
    line-height: 1;
    color: var(--text-muted);
}

.modal-body {
    padding: 1rem;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.media-select-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
}

.media-select-item {
    aspect-ratio: 1;
    border-radius: var(--border-radius);
    overflow: hidden;
    cursor: pointer;
    border: 2px solid var(--border-color);
    transition: all 0.15s;
}

.media-select-item:hover { border-color: var(--forge-primary); }
.media-select-item.selected { border-color: var(--forge-primary); box-shadow: 0 0 0 2px rgba(99,102,241,0.2); }
.media-select-item img { width: 100%; height: 100%; object-fit: cover; }

@media (max-width: 1024px) {
    .editor-layout { grid-template-columns: 1fr; }
    .editor-sidebar { order: -1; }
}

/* Repeater & Group Fields */
.cf-repeater, .cf-group {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-card-header);
}

.cf-group {
    padding: 0.75rem;
}

.cf-repeater-rows {
    max-height: 400px;
    overflow-y: auto;
}

.cf-repeater-row {
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-card);
}

.cf-repeater-row:last-child {
    border-bottom: none;
}

.cf-repeater-row-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}

.cf-repeater-row-num {
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--text-muted);
    background: var(--border-color);
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.cf-repeater-row-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.15s;
}

.cf-repeater-row-remove:hover {
    background: rgba(239, 68, 68, 0.1);
    color: var(--forge-danger);
}

.cf-repeater-row-fields {
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

.cf-sub-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.cf-sub-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.cf-sub-field .form-input,
.cf-sub-field .form-select,
.cf-sub-field .form-textarea {
    font-size: 0.8125rem;
    padding: 0.375rem 0.5rem;
}

.cf-repeater-add {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    width: 100%;
    padding: 0.625rem;
    background: transparent;
    border: none;
    border-top: 1px dashed var(--border-color);
    color: var(--text-muted);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
}

.cf-repeater-add:hover {
    background: rgba(99, 102, 241, 0.05);
    color: var(--forge-primary);
}

.cf-group .cf-sub-field {
    margin-bottom: 0.5rem;
}

.cf-group .cf-sub-field:last-child {
    margin-bottom: 0;
}
</style>

<form method="post">
    <?= csrfField() ?>
    
    <div class="page-header" style="margin-bottom: 1.5rem;">
        <h2><?= esc($pageTitle) ?></h2>
    </div>
    
    <div class="editor-layout">
        <div class="editor-main">
            <!-- Title -->
            <div class="card" style="margin-bottom: 1rem;">
                <div class="card-body">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?= esc($post['title'] ?? $_POST['title'] ?? '') ?>"
                               placeholder="Enter title..." 
                               style="font-size: 1.25rem; font-weight: 600; padding: 0.75rem 1rem;"
                               autofocus>
                        <?php if (isset($errors['title'])): ?>
                            <div class="form-error" style="margin-top: 0.5rem; color: var(--forge-danger); font-size: 0.875rem;"><?= esc($errors['title']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <input type="hidden" id="slug" name="slug" value="<?= esc($post['slug'] ?? $_POST['slug'] ?? '') ?>">
                    <div class="permalink-wrap">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                        <span class="permalink-base"><?= esc($permalinkBase) ?></span>
                        <span class="permalink-slug" id="permalinkSlug"><?= esc($post['slug'] ?? 'your-url') ?></span>
                        <button type="button" class="permalink-edit" onclick="editPermalink()">Edit</button>
                    </div>
                </div>
            </div>
            
            <?php 
            // Fire edit_form_after_title action for plugins to add content
            Plugin::doAction('edit_form_after_title', $post, $postType); 
            ?>
            
            <?php if (Post::typeSupports($postType, 'editor')): ?>
            <?php 
            // Get content - convert blocks to HTML if needed for display
            $editorContent = $post['content'] ?? $_POST['content'] ?? '';
            $hasBlockContent = false;
            if (!empty($editorContent) && class_exists('Anvil')) {
                $trimmed = trim($editorContent);
                if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                    $blocks = json_decode($editorContent, true);
                    if (is_array($blocks) && json_last_error() === JSON_ERROR_NONE) {
                        $hasBlockContent = true;
                        // Convert blocks to HTML for classic editor display
                        $editorContent = Anvil::renderBlocks($blocks);
                    }
                }
            }
            
            // Check if Anvil Live is available for this post
            $anvilLiveAvailable = $post && class_exists('AnvilLive') && AnvilLive::isAvailable($postType);
            $anvilLiveUrl = $anvilLiveAvailable ? AnvilLive::getEditUrl($post) : '';
            ?>
            
            <!-- Edit with Anvil Button (prominent, above editor) -->
            <?php if ($anvilLiveAvailable && $post): ?>
            <div class="anvil-editor-cta" style="margin-bottom: 1rem; padding: 1rem; background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(109, 40, 217, 0.1) 100%); border: 1px solid rgba(124, 58, 237, 0.3); border-radius: var(--border-radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); font-size: 0.9375rem;">Visual Block Editor</div>
                            <div style="font-size: 0.8125rem; color: var(--text-muted);">Edit with drag-and-drop blocks on the frontend</div>
                        </div>
                    </div>
                    <a href="<?= esc($anvilLiveUrl) ?>" target="_blank" class="btn" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: white; border: none; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; font-weight: 500;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                        Edit with Anvil
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                    </a>
                </div>
                <?php if ($hasBlockContent): ?>
                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(124, 58, 237, 0.2); font-size: 0.75rem; color: var(--text-muted);">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 0.25rem;">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    This content uses Anvil blocks. The HTML preview below is read-only. Use "Edit with Anvil" to modify block content.
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <!-- For new posts, show info about Anvil Live -->
            <div class="anvil-editor-cta" style="margin-bottom: 1rem; padding: 1rem; background: var(--bg-card-header); border: 1px solid var(--border-color); border-radius: var(--border-radius-lg);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: var(--border-color); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--text-secondary); font-size: 0.9375rem;">Visual Block Editor</div>
                        <div style="font-size: 0.8125rem; color: var(--text-muted);">Save this <?= strtolower(esc($typeConfig['singular'])) ?> first, then use "Edit with Anvil" for visual editing</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Classic HTML Editor -->
            <div class="card" style="position: relative;">
                <?php if ($hasBlockContent): ?>
                <!-- Overlay for Anvil Block Content -->
                <div class="anvil-content-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(124, 58, 237, 0.03); backdrop-filter: blur(2px); z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: var(--border-radius-lg);">
                    <div style="text-align: center; padding: 2rem; max-width: 400px;">
                        <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <h3 style="margin: 0 0 0.5rem; font-size: 1.25rem; font-weight: 700; color: var(--text-primary);">Anvil Block Content</h3>
                        <p style="margin: 0 0 1.5rem; color: var(--text-muted); font-size: 0.9375rem; line-height: 1.6;">
                            This page was created with Anvil Live visual editor. Use the button below to edit the content.
                        </p>
                        <a href="<?= esc($anvilLiveUrl) ?>" target="_blank" class="btn" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: white; border: none; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; font-weight: 600; font-size: 0.9375rem; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                            Edit with Anvil Live
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                        </a>
                        <div style="margin-top: 1rem;">
                            <button type="button" onclick="removeAnvilOverlay()" style="background: none; border: none; color: var(--text-muted); font-size: 0.8125rem; cursor: pointer; text-decoration: underline;">
                                View HTML (read-only)
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 class="card-title">Content</h3>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 0.25rem;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        HTML Editor
                    </span>
                </div>
                <div class="card-body" style="padding: 0;">
                    <textarea id="content" name="content" class="classic-editor" 
                              style="width: 100%; min-height: 400px; padding: 1rem; border: none; 
                                     font-family: 'JetBrains Mono', monospace; font-size: 0.875rem; 
                                     line-height: 1.6; resize: vertical; background: var(--bg-card);
                                     color: var(--text-primary);"
                              placeholder="Write your content here... HTML is supported."
                              <?= $hasBlockContent ? 'readonly' : '' ?>><?= esc($editorContent) ?></textarea>
                </div>
                <?php if ($hasBlockContent): ?>
                <div class="card-footer" style="padding: 0.75rem 1rem; background: var(--bg-card-header); border-top: 1px solid var(--border-color);">
                    <button type="button" onclick="enableHtmlEditing()" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 0.25rem;">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit HTML Directly
                    </button>
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.75rem;">
                        Warning: Editing HTML will convert blocks to plain HTML
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php 
            // Fire edit_form_after_editor action for plugins to add content
            Plugin::doAction('edit_form_after_editor', $post, $postType); 
            ?>
            
            <?php if (Post::typeSupports($postType, 'excerpt')): ?>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Excerpt</h3></div>
                <div class="card-body">
                    <textarea name="excerpt" class="form-textarea" rows="3" placeholder="Write a short summary..."><?= esc($post['excerpt'] ?? $_POST['excerpt'] ?? '') ?></textarea>
                    <div class="form-hint" style="margin-top: 0.5rem; font-size: 0.8125rem; color: var(--text-muted);">A brief summary shown in listings.</div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SEO Section -->
            <?php include CMS_ROOT . '/admin/includes/seo-metabox.php'; ?>
        </div>
        
        <div class="editor-sidebar">
            <!-- Publish -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">Publish</div>
                <div class="sidebar-card-body">
                    <?php 
                    $currentStatus = $post['status'] ?? 'draft';
                    $statusColors = [
                        'draft' => '#f59e0b',
                        'published' => '#22c55e',
                        'scheduled' => '#6366f1',
                        'trash' => '#dc2626',
                    ];
                    ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
                        <strong>Status:</strong> 
                        <span style="color: <?= $statusColors[$currentStatus] ?? '#6b7280' ?>;">
                            <?= Post::STATUS_LABELS[$currentStatus] ?? ucfirst($currentStatus) ?>
                        </span>
                    </div>
                    
                    <?php if ($post): ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.6;">
                        <?php if ($currentStatus === 'scheduled' && !empty($post['scheduled_at'])): ?>
                        <div style="color: #6366f1;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: -2px; margin-right: 0.25rem;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Scheduled: <?= formatDate($post['scheduled_at'], 'M j, Y g:i a') ?>
                        </div>
                        <?php elseif ($post['published_at']): ?>
                        <div>Published: <?= formatDate($post['published_at'], 'M j, Y g:i a') ?></div>
                        <?php endif; ?>
                        <?php if ($post['updated_at'] && $post['updated_at'] !== $post['created_at']): ?>
                        <div>Updated: <?= formatDate($post['updated_at'], 'M j, Y g:i a') ?></div>
                        <?php endif; ?>
                        <div style="color: var(--text-muted); opacity: 0.7;">Created: <?= formatDate($post['created_at'], 'M j, Y') ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Schedule Options (for draft or scheduled posts) -->
                    <?php if (!$post || $currentStatus !== 'published'): ?>
                    <div id="scheduleSection" style="margin-bottom: 1rem; padding: 0.75rem; background: var(--bg-card-header); border-radius: var(--border-radius);">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; cursor: pointer; margin-bottom: 0.75rem;">
                            <input type="checkbox" id="scheduleToggle" <?= $currentStatus === 'scheduled' ? 'checked' : '' ?> style="accent-color: var(--forge-primary);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Schedule for later
                        </label>
                        <div id="scheduleFields" style="display: <?= $currentStatus === 'scheduled' ? 'flex' : 'none' ?>; gap: 0.5rem;">
                            <input type="date" name="schedule_date" id="scheduleDate" class="form-input" style="flex: 1; font-size: 0.8125rem; padding: 0.5rem;"
                                   value="<?= $currentStatus === 'scheduled' && !empty($post['scheduled_at']) ? date('Y-m-d', strtotime($post['scheduled_at'])) : date('Y-m-d', strtotime('+1 day')) ?>"
                                   min="<?= date('Y-m-d') ?>">
                            <input type="time" name="schedule_time" id="scheduleTime" class="form-input" style="width: 100px; font-size: 0.8125rem; padding: 0.5rem;"
                                   value="<?= $currentStatus === 'scheduled' && !empty($post['scheduled_at']) ? date('H:i', strtotime($post['scheduled_at'])) : '09:00' ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 0.5rem;">
                        <?php if (!$post || $currentStatus !== 'published'): ?>
                        <button type="submit" name="save_action" value="draft" class="btn btn-secondary" style="flex: 1;">
                            Save Draft
                        </button>
                        <button type="submit" name="save_action" value="publish" id="publishBtn" class="btn btn-primary" style="flex: 1; <?= $currentStatus === 'scheduled' ? 'display: none;' : '' ?>">
                            Publish
                        </button>
                        <button type="submit" name="save_action" value="schedule" id="scheduleBtn" class="btn btn-primary" style="flex: 1; <?= $currentStatus === 'scheduled' ? '' : 'display: none;' ?>">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.25rem;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Schedule
                        </button>
                        <?php else: ?>
                        <button type="submit" name="save_action" value="publish" class="btn btn-primary" style="flex: 1;">
                            Update
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($post && $currentStatus === 'published'): ?>
                    <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                        <button type="submit" name="save_action" value="draft" class="btn btn-secondary" style="flex: 1; font-size: 0.75rem;">
                            Revert to Draft
                        </button>
                        <a href="<?= esc(Post::permalink($post)) ?>" target="_blank" class="btn btn-secondary" style="flex: 1; font-size: 0.75rem; text-align: center;">View <?= esc($typeConfig['singular']) ?></a>
                    </div>
                    <?php elseif ($post && $currentStatus === 'scheduled'): ?>
                    <div style="margin-top: 0.5rem;">
                        <button type="submit" name="save_action" value="publish" class="btn btn-secondary" style="width: 100%; font-size: 0.75rem;">
                            Publish Now
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    
                    <?php 
                    // Fire post_submitbox_actions for plugins to add actions
                    Plugin::doAction('post_submitbox_actions', $post, $postType); 
                    ?>
                </div>
            </div>
            
            <?php 
            // Revisions card - only show for existing posts
            if ($post):
                $revisions       = Post::getRevisions($post['id'], 20);
                $revisionCount   = Post::getRevisionCount($post['id']);
                $maxRevisions    = Post::getMaxRevisions($postType);
                $revisionsEnabled = true;
            ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Revisions</span>
                    <?php if ($revisionCount > 0): ?>
                    <span style="background: var(--primary-color); color: white; font-size: 0.6875rem; padding: 0.125rem 0.5rem; border-radius: 9999px;"><?= $revisionCount ?></span>
                    <?php endif; ?>
                </div>
                <div class="sidebar-card-body">
                    <?php if (!$revisionsEnabled): ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted);">
                        Revisions table not found. Please run <a href="update.php" style="color: var(--primary-color);">system update</a> to enable revisions.
                    </div>
                    <?php elseif ($maxRevisions === 0): ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted);">
                        Revisions are disabled for this post type.
                    </div>
                    <?php elseif (empty($revisions)): ?>
                    <div style="font-size: 0.8125rem; color: var(--text-muted);">
                        No revisions yet. Revisions are created when you update the <?= strtolower($typeConfig['singular']) ?>.
                    </div>
                    <?php else: ?>
                    <div style="max-height: 250px; overflow-y: auto; margin: -0.5rem; padding: 0.5rem;">
                        <?php foreach ($revisions as $i => $rev): ?>
                        <div class="revision-item" style="padding: 0.5rem; border-radius: 6px; margin-bottom: 0.5rem; background: var(--bg-tertiary); <?= $i === 0 ? 'border-left: 3px solid var(--primary-color);' : '' ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-color);">
                                        #<?= $rev['revision_number'] ?><?= $i === 0 ? ' (Latest)' : '' ?>
                                    </div>
                                    <div style="font-size: 0.6875rem; color: var(--text-muted); margin-top: 0.125rem;">
                                        <?= formatDate($rev['created_at'], 'M j, Y g:i a') ?>
                                    </div>
                                    <div style="font-size: 0.6875rem; color: var(--text-muted);">
                                        by <?= esc($rev['author_name'] ?? 'Unknown') ?>
                                    </div>
                                </div>
                                <div style="flex-shrink: 0;">
                                    <button type="button" 
                                            onclick="restoreRevision(<?= $rev['id'] ?>, <?= $rev['revision_number'] ?>)" 
                                            class="btn btn-secondary" 
                                            style="font-size: 0.6875rem; padding: 0.25rem 0.5rem;">
                                        Restore
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($revisionCount > 20): ?>
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 0.5rem;">
                        Showing latest 20 of <?= $revisionCount ?> revisions
                    </div>
                    <?php endif; ?>
                    <?php if ($revisionCount >= 2): ?>
                    <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                        <a href="<?= ADMIN_URL ?>/compare-revisions.php?post_id=<?= $post['id'] ?>" 
                           class="btn btn-secondary" 
                           style="width: 100%; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                            Compare Revisions
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($typeConfig['hierarchical']): ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header">Page Attributes</div>
                <div class="sidebar-card-body">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" style="font-size: 0.8125rem; margin-bottom: 0.375rem; display: block;">Parent</label>
                        <select name="parent_id" class="form-select">
                            <option value="">(no parent)</option>
                            <?php foreach ($parentOptions as $opt): ?>
                            <option value="<?= $opt['id'] ?>" <?= ($post['parent_id'] ?? '') == $opt['id'] ? 'selected' : '' ?>><?= esc($opt['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 0.8125rem; margin-bottom: 0.375rem; display: block;">Order</label>
                        <input type="number" name="menu_order" class="form-input" value="<?= esc($post['menu_order'] ?? 0) ?>" min="0">
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (Post::typeSupports($postType, 'featured_image')): ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header">Featured Image</div>
                <div class="sidebar-card-body">
                    <input type="hidden" id="featured_image_id" name="featured_image_id" value="<?= $post['featured_image_id'] ?? '' ?>">
                    
                    <div id="featuredPreview" style="<?= $featuredImage ? '' : 'display:none;' ?>">
                        <?php if ($featuredImage): ?>
                        <div class="featured-image-preview">
                            <img src="<?= esc($featuredImage['url']) ?>" alt="">
                        </div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeFeatured()" style="width: 100%;">Remove</button>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="openMediaModal()" id="setFeaturedBtn" style="width: 100%; <?= $featuredImage ? 'display:none;' : '' ?>">
                        Set Featured Image
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Discussion -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">Discussion</div>
                <div class="sidebar-card-body">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="comments_enabled" value="1" <?= $postCommentsEnabled ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--primary);">
                        <span style="font-size: 0.875rem;">Allow comments</span>
                    </label>
                    <p style="margin: 8px 0 0; font-size: 0.75rem; color: var(--text-muted);">
                        Uncheck to disable comments on this <?= strtolower($typeConfig['singular']) ?>.
                    </p>
                </div>
            </div>
            
            <?php foreach ($postTaxonomies as $taxSlug => $tax): 
                $allTerms = Taxonomy::getTerms($taxSlug);
                $selectedTerms = $postTermsData[$taxSlug] ?? [];
            ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <?= esc($tax['label']) ?>
                    <a href="terms.php?taxonomy=<?= esc($taxSlug) ?>" style="font-size: 0.6875rem; color: var(--text-muted); text-decoration: none;">+ Add New</a>
                </div>
                <div class="sidebar-card-body" style="max-height: 200px; overflow-y: auto;">
                    <?php if (empty($allTerms)): ?>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; margin: 0;">No <?= esc(strtolower($tax['label'])) ?> yet. <a href="terms.php?taxonomy=<?= esc($taxSlug) ?>">Create one</a>.</p>
                    <?php elseif ($tax['hierarchical']): ?>
                    <?php 
                    $termsTree = Taxonomy::getTermsTree($taxSlug);
                    function renderTermCheckboxes($terms, $taxSlug, $selected, $depth = 0) {
                        foreach ($terms as $term): 
                    ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0; padding-left: <?= $depth * 1.25 ?>rem; cursor: pointer;">
                        <input type="checkbox" name="tax_<?= esc($taxSlug) ?>[]" value="<?= $term['id'] ?>" <?= in_array($term['id'], $selected) ? 'checked' : '' ?> style="accent-color: var(--forge-primary);">
                        <span style="font-size: 0.8125rem; color: var(--text-primary);"><?= esc($term['name']) ?></span>
                    </label>
                    <?php 
                        if (!empty($term['children'])) {
                            renderTermCheckboxes($term['children'], $taxSlug, $selected, $depth + 1);
                        }
                        endforeach;
                    }
                    renderTermCheckboxes($termsTree, $taxSlug, $selectedTerms);
                    ?>
                    <?php else: ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                        <?php foreach ($allTerms as $term): ?>
                        <label style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.625rem; background: <?= in_array($term['id'], $selectedTerms) ? 'rgba(99,102,241,0.15)' : 'var(--bg-card-header)' ?>; border: 1px solid <?= in_array($term['id'], $selectedTerms) ? 'var(--forge-primary)' : 'var(--border-color)' ?>; border-radius: 100px; cursor: pointer; transition: all 0.15s;" onmouseover="this.style.borderColor='var(--forge-primary)'" onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--forge-primary)':'var(--border-color)'">
                            <input type="checkbox" name="tax_<?= esc($taxSlug) ?>[]" value="<?= $term['id'] ?>" <?= in_array($term['id'], $selectedTerms) ? 'checked' : '' ?> style="display: none;" onchange="this.parentElement.style.background=this.checked?'rgba(99,102,241,0.15)':'var(--bg-card-header)'; this.parentElement.style.borderColor=this.checked?'var(--forge-primary)':'var(--border-color)';">
                            <span style="font-size: 0.75rem; color: var(--text-primary);"><?= esc($term['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (!empty($customFieldsDefs)): ?>
            <div class="sidebar-card">
                <div class="sidebar-card-header">Custom Fields</div>
                <div class="sidebar-card-body">
                    <?php foreach ($customFieldsDefs as $field): 
                        $fieldKey = $field['key'];
                        $fieldValue = $customFieldsValues[$fieldKey] ?? $_POST['cf_' . $fieldKey] ?? '';
                        $fieldId = 'cf_' . $fieldKey;
                        $isRequired = !empty($field['required']);
                    ?>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label" style="font-size: 0.8125rem; margin-bottom: 0.375rem; display: block;">
                            <?= esc($field['label']) ?>
                            <?php if ($isRequired): ?><span style="color: var(--forge-danger);">*</span><?php endif; ?>
                        </label>
                        
                        <?php if ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-textarea" rows="3" <?= $isRequired ? 'required' : '' ?>><?= esc($fieldValue) ?></textarea>
                        
                        <?php elseif ($field['type'] === 'select' && !empty($field['options'])): ?>
                        <select name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-select" <?= $isRequired ? 'required' : '' ?>>
                            <option value="">— Select —</option>
                            <?php foreach ($field['options'] as $opt): ?>
                            <option value="<?= esc($opt) ?>" <?= $fieldValue === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <?php elseif ($field['type'] === 'checkbox'): ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="<?= $fieldId ?>" id="<?= $fieldId ?>" value="1" <?= $fieldValue ? 'checked' : '' ?>>
                            <span style="font-size: 0.875rem; color: var(--text-secondary);">Yes</span>
                        </label>
                        
                        <?php elseif ($field['type'] === 'number'): ?>
                        <input type="number" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" step="any" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'email'): ?>
                        <input type="email" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'url'): ?>
                        <input type="url" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'date'): ?>
                        <input type="date" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'datetime'): ?>
                        <input type="datetime-local" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        
                        <?php elseif ($field['type'] === 'color'): ?>
                        <input type="color" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue ?: '#000000') ?>" style="height: 40px; padding: 0.25rem;">
                        
                        <?php elseif ($field['type'] === 'image'): ?>
                        <div class="cf-image-field" data-field="<?= $fieldId ?>">
                            <input type="hidden" name="<?= $fieldId ?>" id="<?= $fieldId ?>" value="<?= esc($fieldValue) ?>">
                            <div class="cf-image-preview" style="<?= $fieldValue ? '' : 'display:none;' ?> margin-bottom: 0.5rem;">
                                <?php if ($fieldValue): 
                                    $cfImg = Media::find((int)$fieldValue);
                                    if ($cfImg): ?>
                                <img src="<?= esc($cfImg['url']) ?>" style="max-width: 100%; border-radius: var(--border-radius);">
                                <?php endif; endif; ?>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="openCfImageModal('<?= $fieldId ?>')" style="width: 100%;">
                                <?= $fieldValue ? 'Change Image' : 'Select Image' ?>
                            </button>
                            <?php if ($fieldValue): ?>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeCfImage('<?= $fieldId ?>')" style="width: 100%; margin-top: 0.25rem;">Remove</button>
                            <?php endif; ?>
                        </div>
                        
                        <?php elseif ($field['type'] === 'wysiwyg'): ?>
                        <textarea name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-textarea" rows="6" <?= $isRequired ? 'required' : '' ?>><?= esc($fieldValue) ?></textarea>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">HTML allowed</div>
                        
                        <?php elseif ($field['type'] === 'radio' && !empty($field['options'])): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.375rem;">
                            <?php foreach ($field['options'] as $opt): ?>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="radio" name="<?= $fieldId ?>" value="<?= esc($opt) ?>" <?= $fieldValue === $opt ? 'checked' : '' ?>>
                                <span style="font-size: 0.875rem;"><?= esc($opt) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php elseif ($field['type'] === 'repeater' && !empty($field['sub_fields'])): 
                            $repeaterData = $fieldValue ? json_decode($fieldValue, true) : [];
                            if (!is_array($repeaterData)) $repeaterData = [];
                        ?>
                        <div class="cf-repeater" data-field="<?= $fieldKey ?>" data-subfields='<?= esc(json_encode($field['sub_fields'])) ?>'>
                            <input type="hidden" name="cf_<?= $fieldKey ?>_count" class="repeater-count" value="<?= count($repeaterData) ?>">
                            <div class="cf-repeater-rows">
                                <?php foreach ($repeaterData as $rowIndex => $rowData): ?>
                                <div class="cf-repeater-row" data-row="<?= $rowIndex ?>">
                                    <div class="cf-repeater-row-header">
                                        <span class="cf-repeater-row-num"><?= $rowIndex + 1 ?></span>
                                        <button type="button" class="cf-repeater-row-remove" onclick="removeRepeaterRow(this)">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        </button>
                                    </div>
                                    <div class="cf-repeater-row-fields">
                                        <?php foreach ($field['sub_fields'] as $sf): 
                                            $sfName = 'cf_' . $fieldKey . '_' . $rowIndex . '_' . $sf['key'];
                                            $sfValue = $rowData[$sf['key']] ?? '';
                                        ?>
                                        <div class="cf-sub-field">
                                            <label class="cf-sub-label"><?= esc($sf['label']) ?></label>
                                            <?php if ($sf['type'] === 'textarea'): ?>
                                            <textarea name="<?= $sfName ?>" class="form-textarea" rows="2"><?= esc($sfValue) ?></textarea>
                                            <?php elseif ($sf['type'] === 'select' && !empty($sf['options'])): ?>
                                            <select name="<?= $sfName ?>" class="form-select">
                                                <option value="">— Select —</option>
                                                <?php foreach ($sf['options'] as $opt): ?>
                                                <option value="<?= esc($opt) ?>" <?= $sfValue === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php elseif ($sf['type'] === 'checkbox'): ?>
                                            <label style="display: flex; align-items: center; gap: 0.375rem;">
                                                <input type="checkbox" name="<?= $sfName ?>" value="1" <?= $sfValue ? 'checked' : '' ?>>
                                                <span style="font-size: 0.75rem;">Yes</span>
                                            </label>
                                            <?php elseif ($sf['type'] === 'number'): ?>
                                            <input type="number" name="<?= $sfName ?>" class="form-input" value="<?= esc($sfValue) ?>" step="any">
                                            <?php elseif ($sf['type'] === 'date'): ?>
                                            <input type="date" name="<?= $sfName ?>" class="form-input" value="<?= esc($sfValue) ?>">
                                            <?php elseif ($sf['type'] === 'color'): ?>
                                            <input type="color" name="<?= $sfName ?>" class="form-input" value="<?= esc($sfValue ?: '#000000') ?>" style="height: 32px; padding: 0.125rem;">
                                            <?php else: ?>
                                            <input type="text" name="<?= $sfName ?>" class="form-input" value="<?= esc($sfValue) ?>">
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="cf-repeater-add" onclick="addRepeaterRow(this)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                Add Row
                            </button>
                        </div>
                        
                        <?php elseif ($field['type'] === 'group' && !empty($field['sub_fields'])): 
                            $groupData = $fieldValue ? json_decode($fieldValue, true) : [];
                            if (!is_array($groupData)) $groupData = [];
                        ?>
                        <div class="cf-group" data-field="<?= $fieldKey ?>">
                            <?php foreach ($field['sub_fields'] as $sf): 
                                $sfName = 'cf_' . $fieldKey . '_' . $sf['key'];
                                $sfValue = $groupData[$sf['key']] ?? '';
                            ?>
                            <div class="cf-sub-field">
                                <label class="cf-sub-label"><?= esc($sf['label']) ?></label>
                                <?php if ($sf['type'] === 'textarea'): ?>
                                <textarea name="<?= $sfName ?>" class="form-textarea" rows="2"><?= esc($sfValue) ?></textarea>
                                <?php elseif ($sf['type'] === 'select' && !empty($sf['options'])): ?>
                                <select name="<?= $sfName ?>" class="form-select">
                                    <option value="">— Select —</option>
                                    <?php foreach ($sf['options'] as $opt): ?>
                                    <option value="<?= esc($opt) ?>" <?= $sfValue === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php elseif ($sf['type'] === 'checkbox'): ?>
                                <label style="display: flex; align-items: center; gap: 0.375rem;">
                                    <input type="checkbox" name="<?= $sfName ?>" value="1" <?= $sfValue ? 'checked' : '' ?>>
                                    <span style="font-size: 0.75rem;">Yes</span>
                                </label>
                                <?php elseif ($sf['type'] === 'number'): ?>
                                <input type="number" name="<?= $sfName ?>" class="form-input" value="<?= esc($sfValue) ?>" step="any">
                                <?php elseif ($sf['type'] === 'date'): ?>
                                <input type="date" name="<?= $sfName ?>" class="form-input" value="<?= esc($sfValue) ?>">
                                <?php elseif ($sf['type'] === 'color'): ?>
                                <input type="color" name="<?= $sfName ?>" class="form-input" value="<?= esc($sfValue ?: '#000000') ?>" style="height: 32px; padding: 0.125rem;">
                                <?php else: ?>
                                <input type="text" name="<?= $sfName ?>" class="form-input" value="<?= esc($sfValue) ?>">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php else: ?>
                        <input type="text" name="<?= $fieldId ?>" id="<?= $fieldId ?>" class="form-input" value="<?= esc($fieldValue) ?>" <?= $isRequired ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Media Modal -->
<div class="modal-backdrop" id="mediaModal">
    <div class="modal-box">
        <div class="modal-header">
            <span class="modal-title">Select Image</span>
            <button type="button" class="modal-close" onclick="closeMediaModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="media-select-grid" id="mediaGrid"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeMediaModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="selectMedia()">Select</button>
        </div>
    </div>
</div>

<!-- Permalink Modal -->
<div class="modal-backdrop" id="permalinkModal">
    <div class="modal-box" style="max-width: 400px;">
        <div class="modal-header">
            <span class="modal-title">Edit Permalink</span>
            <button type="button" class="modal-close" onclick="closePermalinkModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.8125rem; margin-bottom: 0.375rem; display: block;">URL Slug</label>
                <input type="text" id="slugInput" class="form-input" placeholder="your-url-slug">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePermalinkModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="savePermalink()">Save</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
const titleInput = document.getElementById('title');
const slugHidden = document.getElementById('slug');
const permalinkSlug = document.getElementById('permalinkSlug');
const contentInput = document.getElementById('content');
let selectedMediaId = null;
let selectedMediaUrl = null;

// Enable HTML editing for block content (converts to HTML)
function enableHtmlEditing() {
    const editor = document.getElementById('content');
    if (editor && editor.hasAttribute('readonly')) {
        if (confirm('Warning: Editing the HTML directly will convert the block content to plain HTML. The visual block structure will be lost.\n\nContinue?')) {
            editor.removeAttribute('readonly');
            editor.style.opacity = '1';
            // Hide the card footer with the edit button
            const cardFooter = editor.closest('.card').querySelector('.card-footer');
            if (cardFooter) {
                cardFooter.style.display = 'none';
            }
            // Remove the overlay
            removeAnvilOverlay();
            // Update the info notice
            const ctaNotice = document.querySelector('.anvil-editor-cta');
            if (ctaNotice) {
                const noticeDiv = ctaNotice.querySelector('div[style*="border-top"]');
                if (noticeDiv) {
                    noticeDiv.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px; margin-right: 0.25rem;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>HTML editing enabled. Saving will convert blocks to plain HTML.';
                    noticeDiv.style.color = '#f59e0b';
                }
            }
        }
    }
}

// Remove the Anvil content overlay to show HTML
function removeAnvilOverlay() {
    const overlay = document.querySelector('.anvil-content-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Auto-generate slug
if (titleInput) {
    titleInput.addEventListener('input', function() {
        if (!slugHidden.dataset.manual) {
            const slug = slugify(this.value);
            slugHidden.value = slug;
            permalinkSlug.textContent = slug || 'your-url';
        }
    });
}

function slugify(text) {
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 200);
}

// Permalink
function editPermalink() {
    document.getElementById('slugInput').value = slugHidden.value;
    document.getElementById('permalinkModal').classList.add('active');
}

function closePermalinkModal() {
    document.getElementById('permalinkModal').classList.remove('active');
}

function savePermalink() {
    const val = slugify(document.getElementById('slugInput').value);
    slugHidden.value = val;
    slugHidden.dataset.manual = 'true';
    permalinkSlug.textContent = val || 'your-url';
    closePermalinkModal();
}

// Media Modal
function openMediaModal() {
    document.getElementById('mediaModal').classList.add('active');
    loadMedia();
}

function closeMediaModal() {
    document.getElementById('mediaModal').classList.remove('active');
}

async function loadMedia() {
    const grid = document.getElementById('mediaGrid');
    grid.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem;">Loading...</p>';
    
    try {
        // Use relative URL to avoid cross-origin issues
        const res = await fetch('media.php?action=list&type=image', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        
        // Check if response is OK
        if (!res.ok) {
            throw new Error('Server returned ' + res.status + ': ' + res.statusText);
        }
        
        // Get response text first for debugging
        const text = await res.text();
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error. Response:', text.substring(0, 1000));
            // Check if there's a PHP error
            if (text.includes('Fatal error') || text.includes('Warning') || text.includes('Notice')) {
                throw new Error('PHP error occurred. Check server logs.');
            }
            throw new Error('Invalid response from server. Please refresh the page.');
        }
        
        if (!data.success) {
            grid.innerHTML = '<p style="text-align:center;color:var(--forge-danger);padding:2rem;">Error: ' + (data.error || 'Unknown error') + '</p>';
            return;
        }
        
        if (!data.media || data.media.length === 0) {
            grid.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem;">No images found. <a href="media.php" target="_blank">Upload media</a></p>';
            return;
        }
        
        // Filter and render images
        const images = data.media.filter(m => m.mime_type && m.mime_type.startsWith('image/'));
        
        if (images.length === 0) {
            grid.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem;">No images found. <a href="media.php" target="_blank">Upload media</a></p>';
            return;
        }
        
        grid.innerHTML = images.map(m => {
            const thumbUrl = m.thumbnail_url || m.url;
            const displayUrl = thumbUrl || m.url;
            return `<div class="media-select-item" data-id="${m.id}" data-url="${m.url}" onclick="selectMediaItem(this)">
                <img src="${displayUrl}" alt="${m.alt_text || ''}" loading="lazy" onerror="this.onerror=null; this.src='${m.url}';">
            </div>`;
        }).join('');
        
    } catch (e) {
        console.error('Media load error:', e);
        grid.innerHTML = `<div style="text-align:center;padding:2rem;">
            <p style="color:var(--forge-danger);margin-bottom:1rem;">${e.message}</p>
            <button type="button" class="btn btn-secondary btn-sm" onclick="loadMedia()">Try Again</button>
        </div>`;
    }
}

function selectMediaItem(el) {
    document.querySelectorAll('.media-select-item').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    selectedMediaId = el.dataset.id;
    selectedMediaUrl = el.dataset.url;
}

function selectMedia() {
    if (!selectedMediaId) return;
    
    document.getElementById('featured_image_id').value = selectedMediaId;
    document.getElementById('featuredPreview').innerHTML = `<div class="featured-image-preview"><img src="${selectedMediaUrl}" alt=""></div><button type="button" class="btn btn-secondary btn-sm" onclick="removeFeatured()" style="width:100%;">Remove</button>`;
    document.getElementById('featuredPreview').style.display = 'block';
    document.getElementById('setFeaturedBtn').style.display = 'none';
    closeMediaModal();
}

function removeFeatured() {
    document.getElementById('featured_image_id').value = '';
    document.getElementById('featuredPreview').style.display = 'none';
    document.getElementById('setFeaturedBtn').style.display = 'block';
}

// Custom field image picker
let currentCfImageField = null;

function openCfImageModal(fieldId) {
    currentCfImageField = fieldId;
    document.getElementById('mediaModal').classList.add('active');
    loadMedia();
}

function removeCfImage(fieldId) {
    document.getElementById(fieldId).value = '';
    const wrapper = document.querySelector('.cf-image-field[data-field="' + fieldId + '"]');
    if (wrapper) {
        const preview = wrapper.querySelector('.cf-image-preview');
        if (preview) {
            preview.style.display = 'none';
            preview.innerHTML = '';
        }
        // Update buttons
        const buttons = wrapper.querySelectorAll('button');
        buttons.forEach((btn, i) => {
            if (i === 0) btn.textContent = 'Select Image';
            if (i === 1) btn.style.display = 'none';
        });
    }
}

// Override selectMedia to handle featured image, custom fields, and Anvil blocks
const originalSelectMedia = selectMedia;
function selectMedia() {
    if (!selectedMediaId) return;
    
    // Check if Anvil editor is requesting media
    if (window.anvilMediaCallback) {
        window.anvilMediaCallback({
            id: selectedMediaId,
            url: selectedMediaUrl
        });
        window.anvilMediaCallback = null;
        closeMediaModal();
        return;
    }
    
    if (currentCfImageField) {
        // Custom field image
        document.getElementById(currentCfImageField).value = selectedMediaId;
        const wrapper = document.querySelector('.cf-image-field[data-field="' + currentCfImageField + '"]');
        if (wrapper) {
            const preview = wrapper.querySelector('.cf-image-preview');
            if (preview) {
                preview.innerHTML = '<img src="' + selectedMediaUrl + '" style="max-width: 100%; border-radius: var(--border-radius);">';
                preview.style.display = 'block';
            }
            // Update buttons
            const buttons = wrapper.querySelectorAll('button');
            buttons.forEach((btn, i) => {
                if (i === 0) btn.textContent = 'Change Image';
                if (i === 1) btn.style.display = 'block';
            });
        }
        currentCfImageField = null;
    } else {
        // Featured image
        document.getElementById('featured_image_id').value = selectedMediaId;
        document.getElementById('featuredPreview').innerHTML = '<div class="featured-image-preview"><img src="' + selectedMediaUrl + '" alt=""></div><button type="button" class="btn btn-secondary btn-sm" onclick="removeFeatured()" style="width:100%;">Remove</button>';
        document.getElementById('featuredPreview').style.display = 'block';
        document.getElementById('setFeaturedBtn').style.display = 'none';
    }
    closeMediaModal();
}

// Restore revision function
function restoreRevision(revisionId, revisionNumber) {
    if (confirm('Restore revision #' + revisionNumber + '?\n\nThis will create a backup of the current version and restore the selected revision.')) {
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        window.location.href = 'post-edit.php?id=<?= $post['id'] ?? 0 ?>&restore_revision=' + revisionId + '&csrf=' + encodeURIComponent(csrfToken);
    }
}

// Schedule toggle functionality
(function() {
    var scheduleToggle = document.getElementById('scheduleToggle');
    var scheduleFields = document.getElementById('scheduleFields');
    var publishBtn = document.getElementById('publishBtn');
    var scheduleBtn = document.getElementById('scheduleBtn');
    
    if (scheduleToggle) {
        scheduleToggle.addEventListener('change', function() {
            if (this.checked) {
                scheduleFields.style.display = 'flex';
                if (publishBtn) publishBtn.style.display = 'none';
                if (scheduleBtn) scheduleBtn.style.display = 'flex';
            } else {
                scheduleFields.style.display = 'none';
                if (publishBtn) publishBtn.style.display = 'flex';
                if (scheduleBtn) scheduleBtn.style.display = 'none';
            }
        });
    }
})();

// Repeater field functions
function addRepeaterRow(btn) {
    const repeater = btn.closest('.cf-repeater');
    const fieldKey = repeater.dataset.field;
    const subFields = JSON.parse(repeater.dataset.subfields);
    const container = repeater.querySelector('.cf-repeater-rows');
    const countInput = repeater.querySelector('.repeater-count');
    
    const rowIndex = parseInt(countInput.value);
    countInput.value = rowIndex + 1;
    
    const row = document.createElement('div');
    row.className = 'cf-repeater-row';
    row.dataset.row = rowIndex;
    
    let fieldsHtml = '';
    subFields.forEach(sf => {
        const sfName = 'cf_' + fieldKey + '_' + rowIndex + '_' + sf.key;
        let inputHtml = '';
        
        if (sf.type === 'textarea') {
            inputHtml = '<textarea name="' + sfName + '" class="form-textarea" rows="2"></textarea>';
        } else if (sf.type === 'select' && sf.options) {
            inputHtml = '<select name="' + sfName + '" class="form-select"><option value="">— Select —</option>';
            sf.options.forEach(opt => {
                inputHtml += '<option value="' + escapeHtmlAttr(opt) + '">' + escapeHtml(opt) + '</option>';
            });
            inputHtml += '</select>';
        } else if (sf.type === 'checkbox') {
            inputHtml = '<label style="display:flex;align-items:center;gap:0.375rem;"><input type="checkbox" name="' + sfName + '" value="1"><span style="font-size:0.75rem;">Yes</span></label>';
        } else if (sf.type === 'number') {
            inputHtml = '<input type="number" name="' + sfName + '" class="form-input" step="any">';
        } else if (sf.type === 'date') {
            inputHtml = '<input type="date" name="' + sfName + '" class="form-input">';
        } else if (sf.type === 'color') {
            inputHtml = '<input type="color" name="' + sfName + '" class="form-input" value="#000000" style="height:32px;padding:0.125rem;">';
        } else {
            inputHtml = '<input type="text" name="' + sfName + '" class="form-input">';
        }
        
        fieldsHtml += '<div class="cf-sub-field"><label class="cf-sub-label">' + escapeHtml(sf.label) + '</label>' + inputHtml + '</div>';
    });
    
    row.innerHTML = '<div class="cf-repeater-row-header">' +
        '<span class="cf-repeater-row-num">' + (rowIndex + 1) + '</span>' +
        '<button type="button" class="cf-repeater-row-remove" onclick="removeRepeaterRow(this)">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
        '</button></div><div class="cf-repeater-row-fields">' + fieldsHtml + '</div>';
    
    container.appendChild(row);
    container.scrollTop = container.scrollHeight;
}

function removeRepeaterRow(btn) {
    const row = btn.closest('.cf-repeater-row');
    const repeater = row.closest('.cf-repeater');
    const countInput = repeater.querySelector('.repeater-count');
    
    row.remove();
    
    // Renumber remaining rows
    const rows = repeater.querySelectorAll('.cf-repeater-row');
    const fieldKey = repeater.dataset.field;
    
    rows.forEach((r, idx) => {
        r.dataset.row = idx;
        r.querySelector('.cf-repeater-row-num').textContent = idx + 1;
        
        // Update input names
        r.querySelectorAll('[name]').forEach(input => {
            const oldName = input.name;
            const match = oldName.match(/^cf_(.+?)_\d+_(.+)$/);
            if (match) {
                input.name = 'cf_' + match[1] + '_' + idx + '_' + match[2];
            }
        });
    });
    
    countInput.value = rows.length;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeHtmlAttr(text) {
    return text.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
