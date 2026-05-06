<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class ButtonBlock extends AnvilBlock
{
    protected static string $name = 'button';
    protected static string $label = 'Button';
    protected static string $description = 'Add a button';
    protected static string $category = 'layout';
    protected static string $icon = 'square';
    
    protected static array $attributes = [
        'text' => ['type' => 'string', 'default' => 'Click me'],
        'url' => ['type' => 'string', 'default' => '#'],
        'target' => ['type' => 'string', 'default' => '_self'],
        'style' => ['type' => 'string', 'default' => 'primary'],
        'align' => ['type' => 'string', 'default' => 'left'],
    ];
    
    protected static array $supports = ['align', 'className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'button');
        $text = $attrs['text'] ?? 'Button';
        $url = $attrs['url'] ?? '#';
        $target = $attrs['target'] ?? '_self';
        $btnStyle = $attrs['style'] ?? 'primary';
        
        return sprintf(
            '<div class="%s"><a href="%s" target="%s" class="anvil-button anvil-button-%s">%s</a></div>',
            esc(self::classString($classes)),
            esc($url),
            esc($target),
            esc($btnStyle),
            esc($text)
        );
    }
}
