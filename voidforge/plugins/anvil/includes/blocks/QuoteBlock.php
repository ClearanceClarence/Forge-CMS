<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class QuoteBlock extends AnvilBlock
{
    protected static string $name = 'quote';
    protected static string $label = 'Quote';
    protected static string $description = 'Add a blockquote';
    protected static string $category = 'text';
    protected static string $icon = 'quote';
    
    protected static array $attributes = [
        'content' => ['type' => 'string', 'default' => ''],
        'citation' => ['type' => 'string', 'default' => ''],
        'align' => ['type' => 'string', 'default' => 'left'],
    ];
    
    protected static array $supports = ['align', 'className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'quote');
        
        $citation = !empty($attrs['citation']) 
            ? '<cite>' . esc($attrs['citation']) . '</cite>' 
            : '';
        
        return sprintf(
            '<blockquote class="%s">%s%s</blockquote>',
            esc(self::classString($classes)),
            self::processInlineContent($attrs['content'] ?? ''),
            $citation
        );
    }
}
