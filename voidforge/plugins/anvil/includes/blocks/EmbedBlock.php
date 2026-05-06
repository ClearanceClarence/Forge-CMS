<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class EmbedBlock extends AnvilBlock
{
    protected static string $name = 'embed';
    protected static string $label = 'Embed';
    protected static string $description = 'Embed external content (YouTube, Vimeo, etc.)';
    protected static string $category = 'embed';
    protected static string $icon = 'external-link';
    
    protected static array $attributes = [
        'url' => ['type' => 'string', 'default' => ''],
        'caption' => ['type' => 'string', 'default' => ''],
        'provider' => ['type' => 'string', 'default' => ''],
        'aspectRatio' => ['type' => 'string', 'default' => '16:9'],
    ];
    
    protected static array $supports = ['align', 'className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'embed');
        $url = $attrs['url'] ?? '';
        
        if (!$url) {
            return '';
        }
        
        $embedHtml = self::getEmbedHtml($url, $attrs);
        $caption = !empty($attrs['caption']) 
            ? '<figcaption>' . esc($attrs['caption']) . '</figcaption>' 
            : '';
            
        return sprintf(
            '<figure class="%s">%s%s</figure>',
            esc(self::classString($classes)),
            $embedHtml,
            $caption
        );
    }
    
    private static function getEmbedHtml(string $url, array $attrs): string
    {
        $aspectRatio = $attrs['aspectRatio'] ?? '16:9';
        [$w, $h] = explode(':', $aspectRatio . ':9');
        $paddingTop = ((int)$h / max(1, (int)$w)) * 100;
        
        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return sprintf(
                '<div class="anvil-embed-wrapper" style="position:relative;padding-top:%.2f%%;">
                    <iframe src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen 
                        style="position:absolute;top:0;left:0;width:100%%;height:100%%;"></iframe>
                </div>',
                $paddingTop,
                esc($m[1])
            );
        }
        
        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return sprintf(
                '<div class="anvil-embed-wrapper" style="position:relative;padding-top:%.2f%%;">
                    <iframe src="https://player.vimeo.com/video/%s" frameborder="0" allowfullscreen
                        style="position:absolute;top:0;left:0;width:100%%;height:100%%;"></iframe>
                </div>',
                $paddingTop,
                esc($m[1])
            );
        }
        
        // Generic iframe fallback
        return sprintf(
            '<div class="anvil-embed-wrapper"><iframe src="%s" frameborder="0"></iframe></div>',
            esc($url)
        );
    }
}
