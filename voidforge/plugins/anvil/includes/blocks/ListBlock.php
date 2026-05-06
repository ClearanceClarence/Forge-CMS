<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class ListBlock extends AnvilBlock
{
    protected static string $name = 'list';
    protected static string $label = 'List';
    protected static string $description = 'Add an ordered or unordered list';
    protected static string $category = 'text';
    protected static string $icon = 'list';
    
    protected static array $attributes = [
        'items' => ['type' => 'array', 'default' => []],
        'ordered' => ['type' => 'boolean', 'default' => false],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'list');
        $tag = !empty($attrs['ordered']) ? 'ol' : 'ul';
        $items = $attrs['items'] ?? [];
        
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= '<li>' . self::processInlineContent($item) . '</li>';
        }
        
        return sprintf(
            '<%s class="%s">%s</%s>',
            $tag,
            esc(self::classString($classes)),
            $itemsHtml,
            $tag
        );
    }
}
