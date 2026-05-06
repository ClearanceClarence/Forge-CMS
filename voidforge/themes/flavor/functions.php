<?php
/**
 * Flavor Theme Functions
 * 
 * @package Flavor
 * @version 2.0.0
 */

defined('CMS_ROOT') or die('Direct access not allowed');

function flavor_get_settings(): array
{
    return getOption('theme_settings_flavor', []);
}

Plugin::addAction('vf_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">';
}, 5);

Plugin::addAction('vf_head', function() {
    $settings = flavor_get_settings();
    $accentColor = $settings['accent_color'] ?? '#6366f1';
    $contentWidth = $settings['content_width'] ?? 'default';
    
    $widths = [
        'narrow' => '720px',
        'default' => '860px',
        'wide' => '1080px'
    ];
    
    $width = $widths[$contentWidth] ?? '860px';
    
    echo '<style>
        :root {
            --color-primary: ' . esc($accentColor) . ';
            --color-primary-dark: ' . flavor_adjust_brightness($accentColor, -15) . ';
            --color-primary-light: ' . flavor_adjust_brightness($accentColor, 15) . ';
            --color-primary-subtle: ' . flavor_hex_to_rgba($accentColor, 0.08) . ';
            --content-width: ' . $width . ';
            --gradient-primary: linear-gradient(135deg, ' . esc($accentColor) . ' 0%, ' . flavor_adjust_brightness($accentColor, 20) . ' 100%);
        }
    </style>';
}, 20);

function flavor_adjust_brightness(string $hex, int $percent): string
{
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function flavor_hex_to_rgba(string $hex, float $alpha): string
{
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    return "rgba($r, $g, $b, $alpha)";
}

function flavor_excerpt(array $post, int $length = 160): string
{
    $content = $post['content'] ?? '';
    
    // Strip blocks JSON if present
    if (str_starts_with($content, '[{')) {
        $blocks = Anvil::parseBlocks($content);
        $content = Anvil::renderBlocks($blocks);
    }
    
    // Strip tags and get plain text
    $content = strip_tags($content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    if (strlen($content) <= $length) {
        return $content;
    }
    
    return substr($content, 0, $length) . '…';
}

function flavor_reading_time(array $post): int
{
    $content = $post['content'] ?? '';
    
    if (str_starts_with($content, '[{')) {
        $blocks = Anvil::parseBlocks($content);
        $content = Anvil::renderBlocks($blocks);
    }
    
    $wordCount = str_word_count(strip_tags($content));
    $minutes = max(1, ceil($wordCount / 200));
    
    return $minutes;
}

function flavor_date(string $date): string
{
    return date('M j, Y', strtotime($date));
}

function flavor_show_entry_title(): bool
{
    $settings = flavor_get_settings();
    return ($settings['show_entry_title'] ?? true) !== false;
}

function flavor_show_entry_meta(): bool
{
    $settings = flavor_get_settings();
    return ($settings['show_entry_meta'] ?? true) !== false;
}

function flavor_show_author(): bool
{
    $settings = flavor_get_settings();
    return ($settings['show_author'] ?? true) !== false;
}

function flavor_show_date(): bool
{
    $settings = flavor_get_settings();
    return ($settings['show_date'] ?? true) !== false;
}

function flavor_render_comment(array $comment, int $depth = 0): void
{
    $maxDepth = (int) getOption('comment_max_depth', 3);
    $authorName = Comment::getAuthorName($comment);
    $gravatar = Comment::getGravatar($comment, 52);
    $depthClass = $depth > 0 ? ' reply depth-' . min($depth, 3) : '';
    $timeAgo = flavor_time_ago($comment['created_at']);
    ?>
    <div class="comment<?= $depthClass ?>" id="comment-<?= $comment['id'] ?>">
        <div class="comment-inner">
            <div class="comment-avatar">
                <img src="<?= esc($gravatar) ?>" alt="<?= esc($authorName) ?>">
            </div>
            <div class="comment-body">
                <div class="comment-meta">
                    <span class="comment-author"><?= esc($authorName) ?></span>
                    <span class="comment-date">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?= $timeAgo ?>
                    </span>
                </div>
                <div class="comment-content">
                    <?= nl2br(esc($comment['content'])) ?>
                </div>
                <?php if ($depth < $maxDepth): ?>
                <a href="#respond" onclick="replyTo(<?= $comment['id'] ?>, '<?= esc(addslashes($authorName)) ?>')" class="comment-reply-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 17 4 12 9 7"/>
                        <path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
                    </svg>
                    Reply
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    if (!empty($comment['replies'])) {
        foreach ($comment['replies'] as $reply) {
            flavor_render_comment($reply, $depth + 1);
        }
    }
}

function flavor_time_ago(string $datetime): string
{
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return flavor_date($datetime);
    }
}
