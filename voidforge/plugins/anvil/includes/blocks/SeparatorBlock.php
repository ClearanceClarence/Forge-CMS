<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class SeparatorBlock extends AnvilBlock
{
    protected static string $name = 'separator';
    protected static string $label = 'Separator';
    protected static string $description = 'Add a horizontal line';
    protected static string $category = 'layout';
    protected static string $icon = 'minus';
    
    protected static array $attributes = [
        'style' => ['type' => 'string', 'default' => 'default'],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'separator');
        $style = $attrs['style'] ?? 'default';
        $classes[] = 'separator-' . $style;
        
        return sprintf('<hr class="%s">', esc(self::classString($classes)));
    }
}
