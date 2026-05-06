<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class CodeBlock extends AnvilBlock
{
    protected static string $name = 'code';
    protected static string $label = 'Code';
    protected static string $description = 'Add a code snippet';
    protected static string $category = 'text';
    protected static string $icon = 'code';
    
    protected static array $attributes = [
        'content' => ['type' => 'string', 'default' => ''],
        'language' => ['type' => 'string', 'default' => ''],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'code');
        $lang = !empty($attrs['language']) ? ' data-language="' . esc($attrs['language']) . '"' : '';
        
        return sprintf(
            '<pre class="%s"%s><code>%s</code></pre>',
            esc(self::classString($classes)),
            $lang,
            esc($attrs['content'] ?? '')
        );
    }
}
