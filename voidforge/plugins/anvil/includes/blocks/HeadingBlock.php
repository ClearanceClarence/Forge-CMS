<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class HeadingBlock extends AnvilBlock
{
    protected static string $name = 'heading';
    protected static string $label = 'Heading';
    protected static string $description = 'Add a heading (H1-H6)';
    protected static string $category = 'text';
    protected static string $icon = 'heading';
    
    protected static array $attributes = [
        'content' => ['type' => 'string', 'default' => ''],
        'level' => ['type' => 'integer', 'default' => 2],
        'align' => ['type' => 'string', 'default' => 'left'],
        'anchor' => ['type' => 'string', 'default' => ''],
    ];
    
    protected static array $supports = ['align', 'className', 'anchor'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'heading');
        $level = max(1, min(6, (int)($attrs['level'] ?? 2)));
        $anchor = !empty($attrs['anchor']) ? ' id="' . esc($attrs['anchor']) . '"' : '';
        
        return sprintf(
            '<h%d class="%s"%s>%s</h%d>',
            $level,
            esc(self::classString($classes)),
            $anchor,
            self::processInlineContent($attrs['content'] ?? ''),
            $level
        );
    }
}
