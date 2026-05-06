<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class VideoBlock extends AnvilBlock
{
    protected static string $name = 'video';
    protected static string $label = 'Video';
    protected static string $description = 'Add a video';
    protected static string $category = 'media';
    protected static string $icon = 'video';
    
    protected static array $attributes = [
        'mediaId' => ['type' => 'integer', 'default' => 0],
        'url' => ['type' => 'string', 'default' => ''],
        'caption' => ['type' => 'string', 'default' => ''],
        'autoplay' => ['type' => 'boolean', 'default' => false],
        'loop' => ['type' => 'boolean', 'default' => false],
        'muted' => ['type' => 'boolean', 'default' => false],
        'controls' => ['type' => 'boolean', 'default' => true],
    ];
    
    protected static array $supports = ['align', 'className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'video');
        
        $url = $attrs['url'] ?? '';
        if (!$url && !empty($attrs['mediaId'])) {
            $media = Media::find((int)$attrs['mediaId']);
            $url = $media ? Media::url($media) : '';
        }
        
        if (!$url) {
            return '';
        }
        
        $videoAttrs = [];
        if (!empty($attrs['controls'])) $videoAttrs[] = 'controls';
        if (!empty($attrs['autoplay'])) $videoAttrs[] = 'autoplay';
        if (!empty($attrs['loop'])) $videoAttrs[] = 'loop';
        if (!empty($attrs['muted'])) $videoAttrs[] = 'muted';
        
        $video = sprintf(
            '<video src="%s" %s></video>',
            esc($url),
            implode(' ', $videoAttrs)
        );
        
        $caption = !empty($attrs['caption']) 
            ? '<figcaption>' . esc($attrs['caption']) . '</figcaption>' 
            : '';
            
        return sprintf(
            '<figure class="%s">%s%s</figure>',
            esc(self::classString($classes)),
            $video,
            $caption
        );
    }
}
