<?php
/**
 * Anvil Block Editor - VoidForge CMS
 * A modular block-based content editor
 * 
 * @version 2.0.0
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Anvil
{
    /** @var array Registered block types */
    private static array $blocks = [];
    
    /** @var array Block categories */
    private static array $categories = [];
    
    /** @var bool Whether Anvil has been initialized */
    private static bool $initialized = false;
    
    /** @var array Registered block class names */
    private static array $blockClasses = [];
    
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        require_once ANVIL_PATH . '/includes/AnvilBlock.php';
        
        self::registerDefaultCategories();
        
        self::loadDefaultBlocks();
        
        if (class_exists('Plugin')) {
            safe_do_action('anvil_register_blocks');
        }
        
        self::$initialized = true;
    }
    
    private static function registerDefaultCategories(): void
    {
        self::registerCategory('text', [
            'label' => 'Text',
            'icon' => 'type',
            'order' => 10,
        ]);
        
        self::registerCategory('media', [
            'label' => 'Media',
            'icon' => 'image',
            'order' => 20,
        ]);
        
        self::registerCategory('layout', [
            'label' => 'Layout',
            'icon' => 'layout',
            'order' => 30,
        ]);
        
        self::registerCategory('embed', [
            'label' => 'Embeds',
            'icon' => 'code',
            'order' => 40,
        ]);
    }
    
    private static function loadDefaultBlocks(): void
    {
        $blocksDir = ANVIL_PATH . '/includes/blocks';
        
        if (!is_dir($blocksDir)) {
            return;
        }
        
        $files = glob($blocksDir . '/*Block.php');
        
        foreach ($files as $file) {
            require_once $file;
            
                $className = basename($file, '.php');
            
            if (class_exists($className) && is_subclass_of($className, 'AnvilBlock')) {
                $className::register();
                self::$blockClasses[$className::getName()] = $className;
            }
        }
    }
    
    /**
     * Register a block from a class
     * 
     * Usage in plugins/themes:
     * Anvil::registerBlockClass(MyCustomBlock::class);
     */
    /**
     * Register a custom block from a class that extends AnvilBlock.
     * Use this in plugins or themes to add new block types.
     *
     * Example:
     *   add_action('anvil_register_blocks', function() {
     *       Anvil::registerBlockClass(MyBlock::class);
     *   });
     *
     * @param string $className Fully-qualified class name extending AnvilBlock
     * @return bool False if the class doesn't exist or doesn't extend AnvilBlock
     */
    public static function registerBlockClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }
        
        if (!is_subclass_of($className, 'AnvilBlock')) {
            return false;
        }
        
        $className::register();
        self::$blockClasses[$className::getName()] = $className;
        
        return true;
    }
    
    /**
     * Register a block type (array-based, for backwards compatibility)
     */
    public static function registerBlock(string $name, array $args): void
    {
        $defaults = [
            'label' => ucfirst($name),
            'description' => '',
            'category' => 'text',
            'icon' => 'square',
            'attributes' => [],
            'supports' => [],
            'render_callback' => null,
            'class' => null,
        ];
        
        self::$blocks[$name] = array_merge($defaults, $args);
    }
    
    public static function unregisterBlock(string $name): bool
    {
        if (isset(self::$blocks[$name])) {
            unset(self::$blocks[$name]);
            unset(self::$blockClasses[$name]);
            return true;
        }
        return false;
    }
    
    public static function getBlocks(): array
    {
        return self::$blocks;
    }
    
    public static function getBlock(string $name): ?array
    {
        return self::$blocks[$name] ?? null;
    }
    
    public static function getBlockClass(string $name): ?string
    {
        return self::$blockClasses[$name] ?? null;
    }
    
    public static function registerCategory(string $slug, array $args): void
    {
        $defaults = [
            'label' => ucfirst($slug),
            'icon' => 'folder',
            'order' => 100,
        ];
        
        self::$categories[$slug] = array_merge($defaults, $args);
    }
    
    public static function getCategories(): array
    {
        uasort(self::$categories, fn($a, $b) => $a['order'] <=> $b['order']);
        return self::$categories;
    }
    
    public static function getBlocksByCategory(): array
    {
        $grouped = [];
        foreach (self::$categories as $slug => $category) {
            $grouped[$slug] = [
                'category' => $category,
                'blocks' => [],
            ];
        }
        
        foreach (self::$blocks as $name => $block) {
            $cat = $block['category'];
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [
                    'category' => ['label' => ucfirst($cat), 'icon' => 'folder', 'order' => 999],
                    'blocks' => [],
                ];
            }
            $grouped[$cat]['blocks'][$name] = $block;
        }
        
        return array_filter($grouped, fn($g) => !empty($g['blocks']));
    }
    
    /**
     * Decode a JSON block string (as stored in the posts table) into a blocks array.
     * Returns an empty array for invalid JSON or non-block content.
     *
     * @return array<int, array{id:string,type:string,attributes:array}>
     */
    public static function parseBlocks(string $json): array
    {
        if (empty($json)) {
            return [];
        }
        
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        
        return $data;
    }
    
    public static function serializeBlocks(array $blocks): string
    {
        return json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Render an array of block data structures to an HTML string.
     * This is what the 'the_content' filter calls on Anvil JSON content.
     */
    public static function renderBlocks(array $blocks): string
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $html = '';
        
        foreach ($blocks as $block) {
            $html .= self::renderBlock($block);
        }
        
        return $html;
    }
    
    /**
     * Render a single block to HTML, applying all style attributes,
     * CSS classes, custom ID, and animation data attributes to the wrapper.
     *
     * @param array{type:string,attributes:array} $block
     */
    public static function renderBlock(array $block): string
    {
        $type = $block['type'] ?? '';
        $attrs = $block['attributes'] ?? [];
        
        $blockDef = self::getBlock($type);
        if (!$blockDef) {
            return '<!-- Unknown block type: ' . esc($type) . ' -->';
        }
        
        $content = '';
        if (is_callable($blockDef['render_callback'])) {
            $content = call_user_func($blockDef['render_callback'], $attrs, $block);
        } else {
            $content = self::defaultRender($type, $attrs, $block);
        }
        
        $styles  = self::getBlockStyles($attrs);
        $classes = self::getBlockClasses($attrs);
        $cssId   = self::getBlockId($attrs);

        // Collect animation timing as data-attributes for the frontend JS
        $dataAttrs = '';
        if (!empty($attrs['animation']) && is_array($attrs['animation'])) {
            $anim = $attrs['animation'];
            if (!empty($anim['duration']))           $dataAttrs .= ' data-anvil-duration="'   . (int)$anim['duration']           . '"';
            if (!empty($anim['delay']))              $dataAttrs .= ' data-anvil-delay="'      . (int)$anim['delay']              . '"';
            if (!empty($anim['transitionDuration'])) $dataAttrs .= ' data-anvil-transition="' . (int)$anim['transitionDuration'] . '"';
        }

        if ($styles || $classes || $cssId || $dataAttrs) {
            $idAttr    = $cssId    ? ' id="'    . esc($cssId)    . '"' : '';
            $classAttr = $classes  ? ' class="anvil-block-wrapper ' . esc($classes) . '"' : ' class="anvil-block-wrapper"';
            // ENT_COMPAT: escapes &, <, >, " — but NOT ' so CSS url('...') values survive intact
            $styleAttr = $styles   ? ' style="' . htmlspecialchars($styles, ENT_COMPAT, 'UTF-8') . '"' : '';

            $content = sprintf('<div%s%s%s%s>%s</div>', $idAttr, $classAttr, $styleAttr, $dataAttrs, $content);
        }
        
        return $content;
    }
    
    private static function getSpacingStyle(array $attrs): string
    {
        return self::getBlockStyles($attrs);
    }
    
    private static function getBlockStyles(array $attrs): string
    {
        $styles = [];
        
        // Generate margin styles
        if (!empty($attrs['margin']) && is_array($attrs['margin'])) {
            $m = $attrs['margin'];
            $unit = $m['unit'] ?? 'px';
            if (!empty($m['top'])) $styles[] = "margin-top:{$m['top']}{$unit}";
            if (!empty($m['right'])) $styles[] = "margin-right:{$m['right']}{$unit}";
            if (!empty($m['bottom'])) $styles[] = "margin-bottom:{$m['bottom']}{$unit}";
            if (!empty($m['left'])) $styles[] = "margin-left:{$m['left']}{$unit}";
        }
        
        // Generate padding styles
        if (!empty($attrs['padding']) && is_array($attrs['padding'])) {
            $p = $attrs['padding'];
            $unit = $p['unit'] ?? 'px';
            if (!empty($p['top'])) $styles[] = "padding-top:{$p['top']}{$unit}";
            if (!empty($p['right'])) $styles[] = "padding-right:{$p['right']}{$unit}";
            if (!empty($p['bottom'])) $styles[] = "padding-bottom:{$p['bottom']}{$unit}";
            if (!empty($p['left'])) $styles[] = "padding-left:{$p['left']}{$unit}";
        }
        
        // Typography styles
        if (!empty($attrs['typography']) && is_array($attrs['typography'])) {
            $t = $attrs['typography'];
            if (!empty($t['fontSize'])) $styles[] = "font-size:{$t['fontSize']}" . ($t['fontSizeUnit'] ?? 'px');
            if (!empty($t['fontWeight'])) $styles[] = "font-weight:{$t['fontWeight']}";
            if (!empty($t['lineHeight'])) $styles[] = "line-height:{$t['lineHeight']}";
            if (!empty($t['letterSpacing'])) $styles[] = "letter-spacing:{$t['letterSpacing']}px";
            if (!empty($t['textTransform'])) $styles[] = "text-transform:{$t['textTransform']}";
            if (!empty($t['fontStyle'])) $styles[] = "font-style:{$t['fontStyle']}";
        }
        
        // Color styles
        if (!empty($attrs['colors']) && is_array($attrs['colors'])) {
            $c = $attrs['colors'];
            if (!empty($c['textColor']))       $styles[] = "color:{$c['textColor']}";
            if (!empty($c['backgroundColor'])) $styles[] = "background-color:{$c['backgroundColor']}";
            // Link colour exposed as a CSS custom property; consumed by .anvil-block-wrapper a
            if (!empty($c['linkColor']))        $styles[] = "--anvil-link-color:{$c['linkColor']}";
        }
        
        // Border styles
        if (!empty($attrs['border']) && is_array($attrs['border'])) {
            $b = $attrs['border'];
            if (!empty($b['style']) && $b['style'] !== 'none') {
                $styles[] = "border-style:{$b['style']}";
                if (!empty($b['width'])) $styles[] = "border-width:{$b['width']}px";
                if (!empty($b['color'])) $styles[] = "border-color:{$b['color']}";
            }
            if (!empty($b['radius'])) $styles[] = "border-radius:{$b['radius']}px";
        }
        
        // Box shadow styles
        if (!empty($attrs['boxShadow']) && is_array($attrs['boxShadow'])) {
            $s = $attrs['boxShadow'];
            $preset = $s['preset'] ?? '';
            
            if ($preset && $preset !== 'custom') {
                $shadowPresets = [
                    'sm' => '0 1px 2px 0 rgba(0,0,0,0.05)',
                    'md' => '0 4px 6px -1px rgba(0,0,0,0.1)',
                    'lg' => '0 10px 15px -3px rgba(0,0,0,0.1)',
                    'xl' => '0 20px 25px -5px rgba(0,0,0,0.1)'
                ];
                if (isset($shadowPresets[$preset])) {
                    $styles[] = "box-shadow:{$shadowPresets[$preset]}";
                }
            } elseif ($preset === 'custom') {
                $x = $s['x'] ?? 0;
                $y = $s['y'] ?? 4;
                $blur = $s['blur'] ?? 6;
                $spread = $s['spread'] ?? 0;
                $color = $s['color'] ?? 'rgba(0,0,0,0.1)';
                $styles[] = "box-shadow:{$x}px {$y}px {$blur}px {$spread}px {$color}";
            }
        }
        
        // Z-index
        if (!empty($attrs['customAttributes']['zIndex'])) {
            $styles[] = "z-index:{$attrs['customAttributes']['zIndex']}";
        }
        
        // Background styles
        if (!empty($attrs['background']) && is_array($attrs['background'])) {
            $bg = $attrs['background'];
            $bgType = $bg['type'] ?? '';
            
            if ($bgType === 'color' && !empty($bg['color'])) {
                $styles[] = "background-color:{$bg['color']}";
            } elseif ($bgType === 'gradient') {
                $c1 = $bg['gradientColor1'] ?? '#6366f1';
                $c2 = $bg['gradientColor2'] ?? '#a855f7';
                $angle = $bg['gradientAngle'] ?? 135;
                $gtype = $bg['gradientType'] ?? 'linear';
                if ($gtype === 'linear') {
                    $styles[] = "background:linear-gradient({$angle}deg, {$c1}, {$c2})";
                } else {
                    $styles[] = "background:radial-gradient(circle, {$c1}, {$c2})";
                }
            } elseif ($bgType === 'image' && !empty($bg['imageUrl'])) {
                $pos    = $bg['imagePosition'] ?? 'center center';
                $size   = $bg['imageSize']     ?? 'cover';
                $repeat = $bg['imageRepeat']   ?? 'no-repeat';

                // Overlay: stack a semi-transparent gradient on top of the image.
                // Using the stacked-background shorthand avoids needing position:relative + child div.
                if (!empty($bg['overlayColor'])) {
                    $overlayRgba = self::colorToRgba(
                        $bg['overlayColor'],
                        (float) ($bg['overlayOpacity'] ?? 0.5)
                    );
                    $styles[] = "background:linear-gradient({$overlayRgba},{$overlayRgba}),url('{$bg['imageUrl']}')";
                } else {
                    $styles[] = "background-image:url('{$bg['imageUrl']}')";
                }
                $styles[] = "background-position:{$pos}";
                $styles[] = "background-size:{$size}";
                $styles[] = "background-repeat:{$repeat}";
            }
        }
        
        // Sizing styles
        if (!empty($attrs['sizing']) && is_array($attrs['sizing'])) {
            $sz = $attrs['sizing'];
            if (!empty($sz['width'])) $styles[] = "width:{$sz['width']}";
            if (!empty($sz['height'])) $styles[] = "height:{$sz['height']}";
            if (!empty($sz['maxWidth'])) $styles[] = "max-width:{$sz['maxWidth']}";
            if (!empty($sz['maxHeight'])) $styles[] = "max-height:{$sz['maxHeight']}";
            if (!empty($sz['minWidth'])) $styles[] = "min-width:{$sz['minWidth']}";
            if (!empty($sz['minHeight'])) $styles[] = "min-height:{$sz['minHeight']}";
            if (!empty($sz['overflow'])) $styles[] = "overflow:{$sz['overflow']}";
        }
        
        // Transform styles
        if (!empty($attrs['transform']) && is_array($attrs['transform'])) {
            $tr = $attrs['transform'];
            $transforms = [];
            if (!empty($tr['rotate'])) $transforms[] = "rotate({$tr['rotate']}deg)";
            if (!empty($tr['scale']) && $tr['scale'] != 1) $transforms[] = "scale({$tr['scale']})";
            if (!empty($tr['translateX'])) $transforms[] = "translateX({$tr['translateX']}px)";
            if (!empty($tr['translateY'])) $transforms[] = "translateY({$tr['translateY']}px)";
            if (!empty($tr['skewX'])) $transforms[] = "skewX({$tr['skewX']}deg)";
            if (!empty($tr['skewY'])) $transforms[] = "skewY({$tr['skewY']}deg)";
            if (!empty($transforms)) {
                $styles[] = "transform:" . implode(' ', $transforms);
            }
        }
        
        // Animation/transition styles
        if (!empty($attrs['animation']) && is_array($attrs['animation'])) {
            $anim = $attrs['animation'];
            if (!empty($anim['transitionDuration'])) {
                $styles[] = "transition:all {$anim['transitionDuration']}ms ease";
            }
        }
        
        return implode(';', $styles);
    }

    /**
     * Convert a hex or rgb/rgba colour string to rgba() with a given opacity.
     * Used to build the background-image overlay without needing a child element.
     */
    private static function colorToRgba(string $color, float $opacity): string
    {
        $opacity = max(0.0, min(1.0, $opacity));
        $color   = trim($color);

        // Already rgba — swap the alpha component
        if (preg_match('/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i', $color, $m)) {
            return "rgba({$m[1]},{$m[2]},{$m[3]},{$opacity})";
        }

        // Hex — #rgb or #rrggbb
        $hex = ltrim($color, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return "rgba({$r},{$g},{$b},{$opacity})";
        }

        // Fallback: black at given opacity
        return "rgba(0,0,0,{$opacity})";
    }

    /**
     */
    private static function getBlockClasses(array $attrs): string
    {
        $classes = [];
        
        // Custom CSS classes
        if (!empty($attrs['customAttributes']['cssClasses'])) {
            $classes[] = $attrs['customAttributes']['cssClasses'];
        }
        
        // Responsive visibility classes
        if (!empty($attrs['responsive'])) {
            if (!empty($attrs['responsive']['hideDesktop'])) $classes[] = 'anvil-hide-desktop';
            if (!empty($attrs['responsive']['hideTablet'])) $classes[] = 'anvil-hide-tablet';
            if (!empty($attrs['responsive']['hideMobile'])) $classes[] = 'anvil-hide-mobile';
        }
        
        // Animation classes
        if (!empty($attrs['animation'])) {
            if (!empty($attrs['animation']['entrance'])) $classes[] = "anvil-anim-{$attrs['animation']['entrance']}";
            if (!empty($attrs['animation']['hover'])) $classes[] = "anvil-hover-{$attrs['animation']['hover']}";
        }
        
        return implode(' ', $classes);
    }
    
    private static function getBlockId(array $attrs): string
    {
        return $attrs['customAttributes']['cssId'] ?? '';
    }
    
    /**
     * Default block rendering (fallback for blocks without render callback)
     */
    private static function defaultRender(string $type, array $attrs, array $block): string
    {
        $className = $attrs['className'] ?? '';
        
        $classes = ['anvil-block', 'anvil-block-' . $type];
        if ($className) {
            $classes[] = $className;
        }
        
        $classStr = implode(' ', $classes);
        
        $content = $attrs['content'] ?? '';
        
        return sprintf(
            '<div class="%s">%s</div>',
            esc($classStr),
            esc($content)
        );
    }
    
    /**
     * Convert legacy HTML content into an Anvil blocks array.
     * Simple paragraph text is split into paragraph blocks;
     * complex HTML is wrapped in a single HTML block.
     *
     * @return array<int, array{id:string,type:string,attributes:array}>
     */
    public static function htmlToBlocks(string $html): array
    {
        if (empty(trim($html))) {
            return [];
        }
        
        $blocks = [];
        $html = trim($html);
        
        if (!preg_match('/<(h[1-6]|ul|ol|blockquote|pre|table|figure|div|img|video)/i', $html)) {
            // Simple text, split by double newlines or <p> tags
            $paragraphs = preg_split('/\s*<\/p>\s*<p[^>]*>\s*|\s*<br\s*\/?>\s*<br\s*\/?>\s*|\n\n+/', $html);
            foreach ($paragraphs as $p) {
                $p = strip_tags(trim($p), '<strong><em><a><code><br>');
                $p = preg_replace('/^<p[^>]*>|<\/p>$/', '', $p);
                if (!empty($p)) {
                    $blocks[] = [
                        'id' => self::generateBlockId(),
                        'type' => 'paragraph',
                        'attributes' => ['content' => $p],
                    ];
                }
            }
        } else {
            // Complex HTML, use single HTML block
            $blocks[] = [
                'id' => self::generateBlockId(),
                'type' => 'html',
                'attributes' => ['content' => $html],
            ];
        }
        
        return $blocks;
    }
    
    public static function blocksToHtml(array $blocks): string
    {
        return self::renderBlocks($blocks);
    }
    
    /**
     * Generate a unique block ID for a new block instance.
     * Format: block-{16 hex chars}
     */
    public static function generateBlockId(): string
    {
        return 'block-' . bin2hex(random_bytes(8));
    }
    
    public static function validateBlock(array $block): bool
    {
        if (empty($block['type'])) {
            return false;
        }
        
        $blockDef = self::getBlock($block['type']);
        if (!$blockDef) {
            return false;
        }
        
        return true;
    }
    
    public static function getEditorData(): array
    {
        return [
            'blocks' => self::$blocks,
            'categories' => self::getCategories(),
            'blocksByCategory' => self::getBlocksByCategory(),
        ];
    }
    
    public static function getBlockNames(): array
    {
        return array_keys(self::$blocks);
    }
    
    public static function hasBlock(string $name): bool
    {
        return isset(self::$blocks[$name]);
    }
}
