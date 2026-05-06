<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class AccordionBlock extends AnvilBlock
{
    protected static string $name = 'accordion';
    protected static string $label = 'Accordion';
    protected static string $description = 'Collapsible content sections';
    protected static string $category = 'text';
    protected static string $icon = 'chevrons-down';
    
    protected static array $attributes = [
        'items' => ['type' => 'array', 'default' => []],
        'allowMultiple' => ['type' => 'boolean', 'default' => false],
        'style' => ['type' => 'string', 'default' => 'default'],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'accordion');
        $items = $attrs['items'] ?? [];
        $style = $attrs['style'] ?? 'default';
        $allowMultiple = !empty($attrs['allowMultiple']);
        
        if (empty($items)) {
                $items = [
                ['title' => 'Accordion Item 1', 'content' => 'Content for the first accordion item.'],
                ['title' => 'Accordion Item 2', 'content' => 'Content for the second accordion item.'],
            ];
        }
        
        $itemsHtml = '';
        foreach ($items as $index => $item) {
            $title = esc($item['title'] ?? 'Item ' . ($index + 1));
            $content = self::processInlineContent($item['content'] ?? '');
            $isOpen = $index === 0 ? ' open' : '';
            
            $itemsHtml .= sprintf(
                '<details class="anvil-accordion-item"%s>
                    <summary class="anvil-accordion-title">
                        <span>%s</span>
                        <svg class="anvil-accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </summary>
                    <div class="anvil-accordion-content">%s</div>
                </details>',
                $isOpen,
                $title,
                $content
            );
        }
        
        $dataAttr = $allowMultiple ? '' : ' data-single="true"';
        
        return sprintf(
            '<div class="%s anvil-accordion--%s"%s>%s</div>',
            esc(self::classString($classes)),
            esc($style),
            $dataAttr,
            $itemsHtml
        );
    }
}
