<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class TestimonialBlock extends AnvilBlock
{
    protected static string $name = 'testimonial';
    protected static string $label = 'Testimonial';
    protected static string $description = 'Customer quote with photo and details';
    protected static string $category = 'text';
    protected static string $icon = 'message-circle';
    
    protected static array $attributes = [
        'content' => ['type' => 'string', 'default' => 'This is an amazing product! I highly recommend it to everyone.'],
        'authorName' => ['type' => 'string', 'default' => 'John Doe'],
        'authorRole' => ['type' => 'string', 'default' => 'CEO'],
        'authorCompany' => ['type' => 'string', 'default' => 'Company Inc.'],
        'authorImage' => ['type' => 'string', 'default' => ''],
        'rating' => ['type' => 'number', 'default' => 5],
        'style' => ['type' => 'string', 'default' => 'default'],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'testimonial');
        $content = self::processInlineContent($attrs['content'] ?? '');
        $authorName = $attrs['authorName'] ?? 'Anonymous';
        $authorRole = $attrs['authorRole'] ?? '';
        $authorCompany = $attrs['authorCompany'] ?? '';
        $authorImage = $attrs['authorImage'] ?? '';
        $rating = min(5, max(0, (int)($attrs['rating'] ?? 5)));
        $style = $attrs['style'] ?? 'default';
        
        $starsHtml = '';
        if ($rating > 0) {
            $starsHtml = '<div class="anvil-testimonial-stars">';
            for ($i = 1; $i <= 5; $i++) {
                $filled = $i <= $rating ? 'filled' : 'empty';
                $starsHtml .= '<svg class="anvil-star anvil-star--' . $filled . '" width="18" height="18" viewBox="0 0 24 24" fill="' . ($i <= $rating ? 'currentColor' : 'none') . '" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>';
            }
            $starsHtml .= '</div>';
        }
        
        $imageHtml = !empty($authorImage)
            ? '<img src="' . esc($authorImage) . '" alt="' . esc($authorName) . '" class="anvil-testimonial-avatar">'
            : '<div class="anvil-testimonial-avatar anvil-testimonial-avatar--placeholder">' . esc(substr($authorName, 0, 1)) . '</div>';
        
        $metaHtml = '<span class="anvil-testimonial-name">' . esc($authorName) . '</span>';
        if ($authorRole || $authorCompany) {
            $metaParts = array_filter([$authorRole, $authorCompany]);
            $metaHtml .= '<span class="anvil-testimonial-meta">' . esc(implode(', ', $metaParts)) . '</span>';
        }
        
        return sprintf(
            '<div class="%s anvil-testimonial--%s">
                %s
                <blockquote class="anvil-testimonial-quote">
                    <svg class="anvil-testimonial-quote-icon" width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11 7H7a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-1a1 1 0 0 1 1-1h2V7zm10 0h-4a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-1a1 1 0 0 1 1-1h2V7z"/>
                    </svg>
                    %s
                </blockquote>
                <div class="anvil-testimonial-author">
                    %s
                    <div class="anvil-testimonial-author-info">%s</div>
                </div>
            </div>',
            esc(self::classString($classes)),
            esc($style),
            $starsHtml,
            $content,
            $imageHtml,
            $metaHtml
        );
    }
}
