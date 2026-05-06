<?php
/**
 * Plugin Name: Anvil Block Editor
 * Plugin URI: https://voidforge.dev/plugins/anvil
 * Description: A powerful block-based content editor with frontend visual editing. Features 21+ block types, drag-and-drop, inline editing, and real-time preview.
 * Version: 1.0.0
 * Author: VoidForge
 * Author URI: https://voidforge.dev
 * License: MIT
 * Requires: 0.2.5
 */

defined('CMS_ROOT') or die('Direct access not allowed');

// Plugin constants
define('ANVIL_VERSION', '1.0.0');
define('ANVIL_PATH', __DIR__);
define('ANVIL_URL', PLUGINS_URL . '/anvil');

// Load the Anvil classes immediately so they're available in admin
require_once ANVIL_PATH . '/includes/class-anvil.php';
require_once ANVIL_PATH . '/includes/class-anvil-live.php';

/**
 * Initialize the Anvil plugin on init action (for frontend hooks)
 */
function anvil_init(): void
{
    // Initialize both systems (registers hooks)
    Anvil::init();
    AnvilLive::init();
}

// Hook into init action to register hooks (primarily for frontend)
Plugin::addAction('init', 'anvil_init', 5);

// Initialize immediately for admin context (plugins are loaded after admin bootstrap)
// This ensures blocks are registered before post-edit.php tries to render them
Plugin::addAction('plugins_loaded', function() {
    // Initialize Anvil immediately so blocks are registered
    Anvil::init();
    AnvilLive::init();
}, 1); // Priority 1 to run early

/**
 * Register plugin assets for admin
 */
Plugin::addAction('admin_enqueue_scripts', function() {
    // Additional admin styles/scripts if needed
});

/**
 * Add Anvil-specific admin bar items
 */
Plugin::addFilter('admin_bar_items', function($items) {
    global $post;
    
    if (!$post || empty($post['id'])) {
        return $items;
    }
    
    if (class_exists('AnvilLive') && AnvilLive::isAvailable($post['post_type'] ?? 'post')) {
        $items['anvil_live'] = [
            'label' => 'Anvil Live',
            'url' => AnvilLive::getEditUrl($post),
            'icon' => 'layers',
            'class' => 'vf-admin-bar-anvil',
        ];
    }
    
    return $items;
});

// ============================================================================
// Template Helper Functions (available when plugin is active)
// ============================================================================

/**
 * Get the Anvil Live edit URL for a post
 */
function get_anvil_live_edit_url($post): string
{
    if (!class_exists('AnvilLive')) {
        return '';
    }
    
    if (is_int($post)) {
        $post = Post::find($post);
    }
    
    if (!$post) {
        return '';
    }
    
    return AnvilLive::getEditUrl($post);
}

/**
 * Check if currently in Anvil Live editor mode
 */
function is_anvil_live_editing(): bool
{
    return class_exists('AnvilLive') && AnvilLive::isEditorMode();
}

/**
 * Output the backend "Edit with Anvil Live" button
 */
function anvil_live_backend_button($post): string
{
    if (!class_exists('AnvilLive')) {
        return '';
    }
    
    if (is_int($post)) {
        $post = Post::find($post);
    }
    
    if (!$post) {
        return '';
    }
    
    return AnvilLive::renderBackendButton($post);
}

/**
 * Output the frontend edit bar manually
 */
function anvil_live_edit_bar(): void
{
    if (class_exists('AnvilLive')) {
        AnvilLive::maybeRenderEditBar();
    }
}

/**
 * Wrap content with Anvil Live page settings
 */
function anvil_wrap_content_with_page_settings(string $content, int $postId): string
{
    if (!class_exists('AnvilLive')) {
        return $content;
    }
    
    $settings = AnvilLive::getPageSettings($postId);
    
    $isFull = $settings['contentWidthFull'] ?? false;
    $isFullWidth = ($isFull === true || $isFull === 'true' || $isFull === '1' || $isFull === 1);
    
    $hasCustomSettings = $isFullWidth || 
                         ($settings['contentWidth'] ?? '1200') !== '1200' ||
                         ($settings['paddingTop'] ?? '0') !== '0' ||
                         ($settings['paddingRight'] ?? '0') !== '0' ||
                         ($settings['paddingBottom'] ?? '0') !== '0' ||
                         ($settings['paddingLeft'] ?? '0') !== '0';
    
    if (!$hasCustomSettings) {
        return $content;
    }
    
    $styles = [];
    $classes = ['anvil-content-wrapper'];
    
    // Content width
    if ($isFullWidth) {
        $classes[] = 'anvil-full-width';
        // Full viewport width with breakout
        $styles[] = 'width: 100vw';
        $styles[] = 'position: relative';
        $styles[] = 'left: 50%';
        $styles[] = 'right: 50%';
        $styles[] = 'margin-left: -50vw';
        $styles[] = 'margin-right: -50vw';
    } else {
        $width = $settings['contentWidth'] ?? '1200';
        $unit = $settings['contentWidthUnit'] ?? 'px';
        $styles[] = 'max-width: ' . esc($width) . esc($unit);
        $styles[] = 'width: 100%';
        
        // Margin for non-full-width
        $mUnit = $settings['marginUnit'] ?? 'px';
        $mTop = $settings['marginTop'] ?? '0';
        $mRight = $settings['marginRight'] ?? 'auto';
        $mBottom = $settings['marginBottom'] ?? '0';
        $mLeft = $settings['marginLeft'] ?? 'auto';
        
        $mTopVal = $mTop === 'auto' ? 'auto' : esc($mTop) . esc($mUnit);
        $mRightVal = $mRight === 'auto' ? 'auto' : esc($mRight) . esc($mUnit);
        $mBottomVal = $mBottom === 'auto' ? 'auto' : esc($mBottom) . esc($mUnit);
        $mLeftVal = $mLeft === 'auto' ? 'auto' : esc($mLeft) . esc($mUnit);
        
        $styles[] = 'margin: ' . $mTopVal . ' ' . $mRightVal . ' ' . $mBottomVal . ' ' . $mLeftVal;
    }
    
    // Padding (applies to both full and non-full width)
    $pUnit = $settings['paddingUnit'] ?? 'px';
    $pTop = $settings['paddingTop'] ?? '0';
    $pRight = $settings['paddingRight'] ?? '0';
    $pBottom = $settings['paddingBottom'] ?? '0';
    $pLeft = $settings['paddingLeft'] ?? '0';
    
    if ($pTop !== '0' || $pRight !== '0' || $pBottom !== '0' || $pLeft !== '0') {
        $styles[] = 'padding: ' . esc($pTop) . esc($pUnit) . ' ' . esc($pRight) . esc($pUnit) . ' ' . esc($pBottom) . esc($pUnit) . ' ' . esc($pLeft) . esc($pUnit);
    }
    
    $styles[] = 'box-sizing: border-box';
    
    $classAttr = implode(' ', $classes);
    $styleAttr = implode('; ', $styles);
    
    return '<div class="' . $classAttr . '" style="' . $styleAttr . '">' . $content . '</div>';
}

/**
 * Filter the_content to render Anvil blocks
 */
Plugin::addFilter('the_content', function(string $content, ?array $post = null) {
    // If no Anvil class, return content as-is
    if (!class_exists('Anvil')) {
        return $content;
    }
    
    // If Anvil Live is editing, don't render blocks normally - let AnvilLive handle it
    if (class_exists('AnvilLive') && AnvilLive::isEditing()) {
        return $content;
    }
    
    if ($post === null) {
        global $post;
    }
    
    $isAnvilContent = false;
    if (!empty($content) && is_string($content) && isset($content[0]) && $content[0] === '[') {
        $blocks = Anvil::parseBlocks($content);
        if (!empty($blocks)) {
            $content = Anvil::renderBlocks($blocks);
            $isAnvilContent = true;
        }
    }
    
    if ($isAnvilContent && class_exists('AnvilLive') && !empty($post['id'])) {
        $content = anvil_wrap_content_with_page_settings($content, $post['id']);
    }
    
    return $content;
}, 5, 2);

// ============================================================================
// Frontend JS — runs once on every page that has Anvil content.
// Handles:
//   • Accordion single-open mode  (data-single="true")
//   • Animation duration/delay CSS vars from data-anvil-* attributes
// ============================================================================
Plugin::addAction('vf_footer', function() {
    ?>
<script>
(function() {
    'use strict';

    // --- Accordion: single-open mode ---
    // When data-single="true" is set, closing all other items when one opens.
    document.querySelectorAll('.anvil-block-accordion[data-single="true"]').forEach(function(accordion) {
        accordion.addEventListener('toggle', function(e) {
            if (e.target.tagName !== 'DETAILS' || !e.target.open) return;
            accordion.querySelectorAll('details').forEach(function(detail) {
                if (detail !== e.target) detail.removeAttribute('open');
            });
        }, true); // capture phase so we see the event before the browser paints
    });

    // --- Entrance animations: read duration/delay from data attributes ---
    // Blocks emit e.g. data-anvil-duration="800" data-anvil-delay="200"
    // on the .anvil-block-wrapper. We convert them to CSS custom properties so
    // the animation keyframes pick them up.
    document.querySelectorAll('[data-anvil-duration], [data-anvil-delay]').forEach(function(el) {
        var duration = el.getAttribute('data-anvil-duration');
        var delay    = el.getAttribute('data-anvil-delay');
        if (duration) el.style.setProperty('animation-duration',  duration + 'ms');
        if (delay)    el.style.setProperty('animation-delay',     delay + 'ms');
    });

    // --- Hover transition duration ---
    document.querySelectorAll('[data-anvil-transition]').forEach(function(el) {
        var ms = el.getAttribute('data-anvil-transition');
        if (ms) el.style.setProperty('--anvil-transition', ms + 'ms');
    });
}());
</script>
    <?php
}, 20);
