<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class HtmlBlock extends AnvilBlock
{
    protected static string $name = 'html';
    protected static string $label = 'Custom HTML';
    protected static string $description = 'Add custom HTML code';
    protected static string $category = 'embed';
    protected static string $icon = 'code-2';
    
    protected static array $attributes = [
        'content' => ['type' => 'string', 'default' => ''],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'html');
        
        // Allow raw HTML (be careful with this!)
        return sprintf(
            '<div class="%s">%s</div>',
            esc(self::classString($classes)),
            $attrs['content'] ?? ''
        );
    }
}
