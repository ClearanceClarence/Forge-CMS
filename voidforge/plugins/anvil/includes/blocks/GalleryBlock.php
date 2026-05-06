<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class GalleryBlock extends AnvilBlock
{
    protected static string $name = 'gallery';
    protected static string $label = 'Gallery';
    protected static string $description = 'Add an image gallery';
    protected static string $category = 'media';
    protected static string $icon = 'grid';
    
    protected static array $attributes = [
        'images' => ['type' => 'array', 'default' => []],
        'columns' => ['type' => 'integer', 'default' => 3],
        'gap' => ['type' => 'integer', 'default' => 16],
        'linkTo' => ['type' => 'string', 'default' => 'none'],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'gallery');
        $images = $attrs['images'] ?? [];
        $cols = max(1, min(6, (int)($attrs['columns'] ?? 3)));
        $gap = (int)($attrs['gap'] ?? 16);
        
        $style = sprintf(
            'display:grid;grid-template-columns:repeat(%d,1fr);gap:%dpx;',
            $cols,
            $gap
        );
        
        $imagesHtml = '';
        foreach ($images as $img) {
            $url = $img['url'] ?? '';
            if (!$url && !empty($img['id'])) {
                $media = Media::find((int)$img['id']);
                $url   = $media ? Media::url($media) : '';
            }
            if (!$url) continue;

            $imgTag = sprintf(
                '<img src="%s" alt="%s" loading="lazy">',
                esc($url),
                esc($img['alt'] ?? '')
            );

            // Wrap in link based on linkTo setting
            $linkTo = $attrs['linkTo'] ?? 'none';
            if ($linkTo === 'media') {
                $imgTag = sprintf('<a href="%s">%s</a>', esc($url), $imgTag);
            } elseif ($linkTo === 'custom' && !empty($img['link'])) {
                $imgTag = sprintf('<a href="%s">%s</a>', esc($img['link']), $imgTag);
            }

            $imagesHtml .= sprintf(
                '<figure class="anvil-gallery-item">%s</figure>',
                $imgTag
            );
        }
        
        return sprintf(
            '<div class="%s" style="%s">%s</div>',
            esc(self::classString($classes)),
            $style,
            $imagesHtml
        );
    }
}
