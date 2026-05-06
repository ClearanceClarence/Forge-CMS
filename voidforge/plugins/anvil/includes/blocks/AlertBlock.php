<?php
defined('CMS_ROOT') or die('Direct access not allowed');

class AlertBlock extends AnvilBlock
{
    protected static string $name = 'alert';
    protected static string $label = 'Alert';
    protected static string $description = 'Info, warning, success, or error message';
    protected static string $category = 'text';
    protected static string $icon = 'alert-circle';
    
    protected static array $attributes = [
        'content' => ['type' => 'string', 'default' => 'This is an alert message.'],
        'type' => ['type' => 'string', 'default' => 'info'],
        'title' => ['type' => 'string', 'default' => ''],
        'dismissible' => ['type' => 'boolean', 'default' => false],
    ];
    
    protected static array $supports = ['className'];
    
    private static array $icons = [
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'success' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'warning' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'error' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
    ];
    
    public static function render(array $attrs, array $block): string
    {
        $classes = self::buildClasses($attrs, 'alert');
        $type = $attrs['type'] ?? 'info';
        $title = $attrs['title'] ?? '';
        $content = self::processInlineContent($attrs['content'] ?? '');
        $dismissible = !empty($attrs['dismissible']);
        
        $icon = self::$icons[$type] ?? self::$icons['info'];
        
        $titleHtml = !empty($title) 
            ? '<div class="anvil-alert-title">' . esc($title) . '</div>' 
            : '';
        
        $dismissBtn = $dismissible 
            ? '<button type="button" class="anvil-alert-dismiss" onclick="this.parentElement.remove()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
               </button>' 
            : '';
        
        return sprintf(
            '<div class="%s anvil-alert--%s" role="alert">
                <svg class="anvil-alert-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">%s</svg>
                <div class="anvil-alert-body">%s<div class="anvil-alert-content">%s</div></div>
                %s
            </div>',
            esc(self::classString($classes)),
            esc($type),
            $icon,
            $titleHtml,
            $content,
            $dismissBtn
        );
    }
}
