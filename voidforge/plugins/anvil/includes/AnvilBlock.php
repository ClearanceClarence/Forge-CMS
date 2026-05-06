<?php
/**
 * AnvilBlock - Abstract base class for all Anvil blocks
 * 
 * Each block extends this class and defines its own settings,
 * attributes, and rendering logic.
 * 
 * @package VoidForge
 * @subpackage Anvil
 * @version 1.0.0
 */

defined('CMS_ROOT') or die('Direct access not allowed');

abstract class AnvilBlock
{
    /** @var string Block unique identifier */
    protected static string $name = '';
    
    /** @var string Display label */
    protected static string $label = '';
    
    /** @var string Block description */
    protected static string $description = '';
    
    /** @var string Category slug */
    protected static string $category = 'text';
    
    /** @var string Icon name (Lucide icons) */
    protected static string $icon = 'square';
    
    /** @var array Attribute definitions */
    protected static array $attributes = [];
    
    /** @var array Supported features */
    protected static array $supports = [];
    
    public static function getName(): string
    {
        return static::$name;
    }
    
    public static function getLabel(): string
    {
        return static::$label;
    }
    
    public static function getDescription(): string
    {
        return static::$description;
    }
    
    public static function getCategory(): string
    {
        return static::$category;
    }
    
    public static function getIcon(): string
    {
        return static::$icon;
    }
    
    public static function getAttributes(): array
    {
        return static::$attributes;
    }
    
    public static function getSupports(): array
    {
        return static::$supports;
    }
    
    /**
     * Return the full block definition array used when registering with Anvil.
     * Includes label, category, icon, attributes schema, and render callback.
     */
    public static function getDefinition(): array
    {
        return [
            'label' => static::$label,
            'description' => static::$description,
            'category' => static::$category,
            'icon' => static::$icon,
            'attributes' => static::$attributes,
            'supports' => static::$supports,
            'render_callback' => [static::class, 'render'],
            'class' => static::class,
        ];
    }
    
    /**
     * Register this block class with the Anvil block registry.
     * Called automatically by Anvil::loadDefaultBlocks() for bundled blocks.
     * Call manually for custom blocks: MyBlock::register()
     */
    public static function register(): void
    {
        if (empty(static::$name)) {
            return;
        }
        
        Anvil::registerBlock(static::$name, static::getDefinition());
    }
    
    protected static function buildClasses(array $attrs, string $type): array
    {
        $classes = ['anvil-block', 'anvil-block-' . $type];
        
        if (!empty($attrs['className'])) {
            $classes[] = $attrs['className'];
        }
        
        if (!empty($attrs['align']) && $attrs['align'] !== 'none') {
            $classes[] = 'align' . $attrs['align'];
        }
        
        return $classes;
    }
    
    protected static function classString(array $classes): string
    {
        return implode(' ', array_filter($classes));
    }
    
    protected static function processInlineContent(string $content): string
    {
        return $content;
    }
    
    /**
     * Render the block to HTML
     * Each block must implement this method
     */
    /**
     * Render the block to HTML. Must be implemented by every concrete block class.
     *
     * @param array $attrs  Block attributes (merged with defaults from $attributes schema)
     * @param array $block  Full block data including type and raw attributes
     * @return string       Final HTML to output; must be safe for direct echo
     */
    abstract public static function render(array $attrs, array $block): string;
}
