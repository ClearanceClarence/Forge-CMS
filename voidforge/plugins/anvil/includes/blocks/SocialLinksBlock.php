<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class SocialLinksBlock extends AnvilBlock
{
    protected static string $name = 'sociallinks';
    protected static string $label = 'Social Links';
    protected static string $description = 'Social media icon links';
    protected static string $category = 'layout';
    protected static string $icon = 'share-2';
    
    protected static array $attributes = [
        'facebook' => ['type' => 'string', 'default' => ''],
        'twitter' => ['type' => 'string', 'default' => ''],
        'instagram' => ['type' => 'string', 'default' => ''],
        'linkedin' => ['type' => 'string', 'default' => ''],
        'youtube' => ['type' => 'string', 'default' => ''],
        'github' => ['type' => 'string', 'default' => ''],
        'tiktok' => ['type' => 'string', 'default' => ''],
        'email' => ['type' => 'string', 'default' => ''],
        'style' => ['type' => 'string', 'default' => 'default'],
        'size' => ['type' => 'string', 'default' => 'medium'],
        'align' => ['type' => 'string', 'default' => 'center'],
    ];
    
    protected static array $supports = ['align', 'className'];
    
    private static array $icons = [
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'twitter' => '<path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
        'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'youtube' => '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>',
        'github' => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>',
        'tiktok' => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>',
        'email' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
    ];
    
    private static array $colors = [
        'facebook' => '#1877f2',
        'twitter' => '#1da1f2',
        'instagram' => '#e4405f',
        'linkedin' => '#0a66c2',
        'youtube' => '#ff0000',
        'github' => '#333333',
        'tiktok' => '#000000',
        'email' => '#6366f1',
    ];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'sociallinks');
        $style = $attrs['style'] ?? 'default';
        $size = $attrs['size'] ?? 'medium';
        $align = $attrs['align'] ?? 'center';
        
        $sizeMap = ['small' => 20, 'medium' => 24, 'large' => 32];
        $iconSize = $sizeMap[$size] ?? 24;
        
        $linksHtml = '';
        foreach (self::$icons as $platform => $iconPath) {
            $url = $attrs[$platform] ?? '';
            if (empty($url)) continue;
            
                if ($platform === 'email') {
                if (!str_starts_with($url, 'mailto:')) {
                    $url = 'mailto:' . $url;
                }
            } else {
                        if (!preg_match('/^https?:\/\//i', $url) && !str_starts_with($url, '/')) {
                    $url = 'https://' . $url;
                }
            }
            
            $color = self::$colors[$platform] ?? '#6366f1';
            $linksHtml .= sprintf(
                '<a href="%s" class="anvil-social-link anvil-social-link--%s" target="_blank" rel="noopener noreferrer" style="--social-color: %s" aria-label="%s">
                    <svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">%s</svg>
                </a>',
                esc($url),
                esc($platform),
                esc($color),
                esc(ucfirst($platform)),
                $iconSize,
                $iconSize,
                $iconPath
            );
        }
        
        if (empty($linksHtml)) {
            $linksHtml = '<span class="anvil-sociallinks-empty">Add social media URLs in block settings</span>';
        }
        
        return sprintf(
            '<div class="%s anvil-sociallinks--%s anvil-sociallinks--size-%s anvil-sociallinks--align-%s">%s</div>',
            esc(self::classString($classes)),
            esc($style),
            esc($size),
            esc($align),
            $linksHtml
        );
    }
}
