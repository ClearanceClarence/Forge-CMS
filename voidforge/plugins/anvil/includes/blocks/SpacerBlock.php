<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class SpacerBlock extends AnvilBlock
{
    protected static string $name = 'spacer';
    protected static string $label = 'Spacer';
    protected static string $description = 'Add vertical space';
    protected static string $category = 'layout';
    protected static string $icon = 'arrow-down-up';
    
    protected static array $attributes = [
        'height' => ['type' => 'integer', 'default' => 50],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'spacer');
        $height = max(0, (int)($attrs['height'] ?? 50));
        
        return sprintf(
            '<div class="%s" style="height:%dpx;" aria-hidden="true"></div>',
            esc(self::classString($classes)),
            $height
        );
    }
}
