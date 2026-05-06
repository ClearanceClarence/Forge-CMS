<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class TableBlock extends AnvilBlock
{
    protected static string $name = 'table';
    protected static string $label = 'Table';
    protected static string $description = 'Add a table';
    protected static string $category = 'text';
    protected static string $icon = 'table';
    
    protected static array $attributes = [
        'rows' => ['type' => 'array', 'default' => [['']]],
        'hasHeader' => ['type' => 'boolean', 'default' => false],
        'hasFooter' => ['type' => 'boolean', 'default' => false],
    ];
    
    protected static array $supports = ['className'];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'table');
        $rows = $attrs['rows'] ?? [['']];
        $hasHeader = !empty($attrs['hasHeader']);
        $hasFooter = !empty($attrs['hasFooter']);
        
        $html = '<table class="' . esc(self::classString($classes)) . '">';
        
        foreach ($rows as $i => $row) {
            $isHeader = $hasHeader && $i === 0;
            $isFooter = $hasFooter && $i === count($rows) - 1;
            $cellTag = $isHeader ? 'th' : 'td';
            $wrapper = $isHeader ? 'thead' : ($isFooter ? 'tfoot' : 'tbody');
            
            if ($i === 0 || ($hasHeader && $i === 1) || ($hasFooter && $i === count($rows) - 1)) {
                $html .= '<' . $wrapper . '>';
            }
            
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<' . $cellTag . '>' . esc($cell) . '</' . $cellTag . '>';
            }
            $html .= '</tr>';
            
            $lastInSection = ($hasHeader && $i === 0) || 
                             ($hasFooter && $i === count($rows) - 1) ||
                             (!$hasFooter && $i === count($rows) - 1) ||
                             ($hasFooter && $i === count($rows) - 2);
            if ($lastInSection) {
                $html .= '</' . $wrapper . '>';
            }
        }
        
        $html .= '</table>';
        return $html;
    }
}
