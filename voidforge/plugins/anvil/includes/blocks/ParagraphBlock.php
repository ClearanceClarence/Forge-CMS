<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class ParagraphBlock extends AnvilBlock
{
    protected static string $name = 'paragraph';
    protected static string $label = 'Paragraph';
    protected static string $description = 'Add a text paragraph';
    protected static string $category = 'text';
    protected static string $icon = 'align-left';
    
    protected static array $attributes = [
        'content' => ['type' => 'string', 'default' => ''],
        'align' => ['type' => 'string', 'default' => 'left'],
        'dropCap' => ['type' => 'boolean', 'default' => false],
    ];
    
    protected static array $supports = ['align', 'className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'paragraph');
        
        if (!empty($attrs['dropCap'])) {
            $classes[] = 'has-drop-cap';
        }
        
        return sprintf(
            '<p class="%s">%s</p>',
            esc(self::classString($classes)),
            self::processInlineContent($attrs['content'] ?? '')
        );
    }
}
