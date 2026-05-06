<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class ColumnsBlock extends AnvilBlock
{
    protected static string $name = 'columns';
    protected static string $label = 'Columns';
    protected static string $description = 'Add columns layout';
    protected static string $category = 'layout';
    protected static string $icon = 'columns';
    
    protected static array $attributes = [
        'columnCount' => ['type' => 'integer', 'default' => 2],
        'columns' => ['type' => 'array', 'default' => []],
        'verticalAlign' => ['type' => 'string', 'default' => 'top'],
    ];
    
    protected static array $supports = ['className', 'innerBlocks'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'columns');
        $columnCount = max(2, min(6, (int)($attrs['columnCount'] ?? 2)));
        $columns = $attrs['columns'] ?? [];
        $vAlign = $attrs['verticalAlign'] ?? 'top';
        
        $alignValue = match($vAlign) {
            'center' => 'center',
            'bottom' => 'end',
            default => 'start'
        };
        
        $style = sprintf(
            'display:grid;grid-template-columns:repeat(%d,1fr);gap:24px;align-items:%s;',
            $columnCount,
            $alignValue
        );
        
        $colsHtml = '';
        for ($i = 0; $i < $columnCount; $i++) {
            $colBlocks = $columns[$i] ?? [];
            $colContent = '';
            
            if (is_array($colBlocks) && !empty($colBlocks)) {
                $colContent = Anvil::renderBlocks($colBlocks);
            }
            
            $colsHtml .= '<div class="anvil-column" data-column-index="' . $i . '">' . $colContent . '</div>';
        }
        
        return sprintf(
            '<div class="%s" style="%s">%s</div>',
            esc(self::classString($classes)),
            $style,
            $colsHtml
        );
    }
}
