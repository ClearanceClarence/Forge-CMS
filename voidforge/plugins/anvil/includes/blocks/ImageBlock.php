<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class ImageBlock extends AnvilBlock
{
    protected static string $name = 'image';
    protected static string $label = 'Image';
    protected static string $description = 'Add an image';
    protected static string $category = 'media';
    protected static string $icon = 'image';
    
    protected static array $attributes = [
        'mediaId' => ['type' => 'integer', 'default' => 0],
        'url' => ['type' => 'string', 'default' => ''],
        'alt' => ['type' => 'string', 'default' => ''],
        'caption' => ['type' => 'string', 'default' => ''],
        'align' => ['type' => 'string', 'default' => 'none'],
        'width' => ['type' => 'integer', 'default' => 0],
        'height' => ['type' => 'integer', 'default' => 0],
        'linkUrl' => ['type' => 'string', 'default' => ''],
    ];
    
    protected static array $supports = ['align', 'className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'image');
        
        $url = $attrs['url'] ?? '';
        if (!$url && !empty($attrs['mediaId'])) {
            $media = Media::find((int)$attrs['mediaId']);
            $url = $media ? Media::url($media) : '';
        }
        
        if (!$url) {
            return '';
        }
        
        $img = sprintf(
            '<img src="%s" alt="%s"%s%s>',
            esc($url),
            esc($attrs['alt'] ?? ''),
            !empty($attrs['width']) ? ' width="' . (int)$attrs['width'] . '"' : '',
            !empty($attrs['height']) ? ' height="' . (int)$attrs['height'] . '"' : ''
        );
        
        if (!empty($attrs['linkUrl'])) {
            $img = sprintf('<a href="%s">%s</a>', esc($attrs['linkUrl']), $img);
        }
        
        $caption = !empty($attrs['caption']) 
            ? '<figcaption>' . esc($attrs['caption']) . '</figcaption>' 
            : '';
            
        return sprintf(
            '<figure class="%s">%s%s</figure>',
            esc(self::classString($classes)),
            $img,
            $caption
        );
    }
}
