<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class CardBlock extends AnvilBlock
{
    protected static string $name = 'card';
    protected static string $label = 'Card';
    protected static string $description = 'Card with image, title, text and button';
    protected static string $category = 'layout';
    protected static string $icon = 'credit-card';
    
    protected static array $attributes = [
        'imageUrl' => ['type' => 'string', 'default' => ''],
        'imageAlt' => ['type' => 'string', 'default' => ''],
        'title' => ['type' => 'string', 'default' => 'Card Title'],
        'content' => ['type' => 'string', 'default' => 'Card description text goes here.'],
        'buttonText' => ['type' => 'string', 'default' => ''],
        'buttonUrl' => ['type' => 'string', 'default' => '#'],
        'style' => ['type' => 'string', 'default' => 'default'],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'card');
        $imageUrl = $attrs['imageUrl'] ?? '';
        $imageAlt = $attrs['imageAlt'] ?? '';
        $title = $attrs['title'] ?? 'Card Title';
        $content = self::processInlineContent($attrs['content'] ?? '');
        $buttonText = $attrs['buttonText'] ?? '';
        $buttonUrl = $attrs['buttonUrl'] ?? '#';
        $style = $attrs['style'] ?? 'default';
        
        $imageHtml = !empty($imageUrl)
            ? sprintf(
                '<div class="anvil-card-image"><img src="%s" alt="%s" loading="lazy"></div>',
                esc($imageUrl),
                esc($imageAlt)
            )
            : '';
        
        $buttonHtml = !empty($buttonText)
            ? sprintf(
                '<a href="%s" class="anvil-card-button">%s</a>',
                esc($buttonUrl),
                esc($buttonText)
            )
            : '';
        
        return sprintf(
            '<div class="%s anvil-card--%s">
                %s
                <div class="anvil-card-body">
                    <h3 class="anvil-card-title">%s</h3>
                    <div class="anvil-card-content">%s</div>
                    %s
                </div>
            </div>',
            esc(self::classString($classes)),
            esc($style),
            $imageHtml,
            esc($title),
            $content,
            $buttonHtml
        );
    }
}
