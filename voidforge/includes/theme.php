<?php
/**
 * Theme Management - VoidForge CMS
 * WordPress-like theme system
 */

class Theme
{
    private static ?string $activeTheme = null;
    private static array $themes = [];
    private static array $themeData = [];
    
    public static function init(): void
    {
        self::$activeTheme = getOption('active_theme', 'flavor');
        self::scanThemes();
    }
    
    public static function scanThemes(): array
    {
        self::$themes = [];
        $themesDir = CMS_ROOT . '/themes';
        
        if (!is_dir($themesDir)) {
            mkdir($themesDir, 0755, true);
            return self::$themes;
        }
        
        $dirs = array_filter(glob($themesDir . '/*'), 'is_dir');
        
        foreach ($dirs as $dir) {
            $slug = basename($dir);
            $header = self::getThemeHeader($slug);
            
            if ($header) {
                self::$themes[$slug] = $header;
            }
        }
        
        return self::$themes;
    }
    
    public static function getThemeHeader(string $slug): ?array
    {
        $themePath = CMS_ROOT . '/themes/' . $slug;
        
        $jsonPath = $themePath . '/theme.json';
        if (file_exists($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            if ($json) {
                return array_merge([
                    'slug' => $slug,
                    'name' => $slug,
                    'description' => '',
                    'version' => '1.0.0',
                    'author' => '',
                    'author_uri' => '',
                    'theme_uri' => '',
                    'requires_php' => '',
                    'requires_cms' => '',
                    'license' => '',
                    'tags' => [],
                ], $json);
            }
        }
        
        $stylePath = $themePath . '/style.css';
        if (file_exists($stylePath)) {
            $content = file_get_contents($stylePath, false, null, 0, 8192);
            return self::parseThemeHeader($content, $slug);
        }
        
        if (file_exists($themePath . '/index.php')) {
            return [
                'slug' => $slug,
                'name' => ucfirst($slug),
                'description' => '',
                'version' => '1.0.0',
                'author' => '',
                'author_uri' => '',
                'theme_uri' => '',
                'requires_php' => '',
                'requires_cms' => '',
                'license' => '',
                'tags' => [],
            ];
        }
        
        return null;
    }
    
    private static function parseThemeHeader(string $content, string $slug): array
    {
        $headers = [
            'name' => 'Theme Name',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author',
            'author_uri' => 'Author URI',
            'theme_uri' => 'Theme URI',
            'requires_php' => 'Requires PHP',
            'requires_cms' => 'Requires CMS',
            'license' => 'License',
            'tags' => 'Tags',
        ];
        
        $data = ['slug' => $slug];
        
        foreach ($headers as $key => $label) {
            if (preg_match('/^[\s\*]*' . preg_quote($label, '/') . ':\s*(.+)$/mi', $content, $match)) {
                $value = trim($match[1]);
                if ($key === 'tags') {
                    $data[$key] = array_map('trim', explode(',', $value));
                } else {
                    $data[$key] = $value;
                }
            } else {
                $data[$key] = $key === 'tags' ? [] : ($key === 'name' ? ucfirst($slug) : '');
            }
        }
        
        return $data;
    }
    
    public static function getThemes(): array
    {
        if (empty(self::$themes)) {
            self::scanThemes();
        }
        return self::$themes;
    }
    
    public static function getActive(): string
    {
        if (self::$activeTheme === null) {
            self::$activeTheme = getOption('active_theme', 'flavor');
        }
        return self::$activeTheme;
    }
    
    public static function getActiveData(): ?array
    {
        $active = self::getActive();
        return self::$themes[$active] ?? null;
    }
    
    public static function activate(string $slug): array
    {
        $themes = self::getThemes();
        
        if (!isset($themes[$slug])) {
            return ['success' => false, 'error' => 'Theme not found.'];
        }
        
        $theme = $themes[$slug];
        
        if (!empty($theme['requires_php'])) {
            if (version_compare(PHP_VERSION, $theme['requires_php'], '<')) {
                return ['success' => false, 'error' => "Theme requires PHP {$theme['requires_php']} or higher."];
            }
        }
        
        if (!empty($theme['requires_cms'])) {
            if (version_compare(CMS_VERSION, $theme['requires_cms'], '<')) {
                return ['success' => false, 'error' => "Theme requires VoidForge CMS {$theme['requires_cms']} or higher."];
            }
        }
        
        // Deactivate current theme
        $oldTheme = self::$activeTheme;
        if ($oldTheme && $oldTheme !== $slug) {
            safe_do_action('switch_theme', $oldTheme, $slug);
            safe_do_action('deactivate_theme_' . $oldTheme);
        }
        
        // Activate new theme
        self::$activeTheme = $slug;
        setOption('active_theme', $slug);
        
        self::loadFunctions($slug);
        
        safe_do_action('activate_theme_' . $slug);
        safe_do_action('after_switch_theme', $slug, $oldTheme);
        
        return ['success' => true];
    }
    
    public static function getPath(string $slug = null): string
    {
        $slug = $slug ?? self::getActive();
        return CMS_ROOT . '/themes/' . $slug;
    }
    
    public static function getUrl(string $slug = null): string
    {
        $slug = $slug ?? self::getActive();
        return SITE_URL . '/themes/' . $slug;
    }
    
    public static function loadFunctions(string $slug = null): void
    {
        $slug = $slug ?? self::getActive();
        $functionsPath = self::getPath($slug) . '/functions.php';
        
        if (file_exists($functionsPath)) {
            require_once $functionsPath;
        }
    }
    
    public static function getTemplate(string $template, string $slug = null): ?string
    {
        $slug = $slug ?? self::getActive();
        $path = self::getPath($slug) . '/' . $template . '.php';
        
        if (file_exists($path)) {
            return $path;
        }
        
        // Fallback to index.php
        $indexPath = self::getPath($slug) . '/index.php';
        if (file_exists($indexPath)) {
            return $indexPath;
        }
        
        return null;
    }
    
    public static function includeTemplate(string $template, array $data = []): void
    {
        $path = self::getTemplate($template);
        
        if ($path) {
            extract($data);
            include $path;
        }
    }
    
    public static function getHeader(string $name = null, array $data = []): void
    {
        $template = $name ? "header-{$name}" : 'header';
        $path = self::getTemplate($template);
        
        if (!$path && $name) {
            $path = self::getTemplate('header');
        }
        
        if ($path) {
            extract($data);
            safe_do_action('get_header', $name);
            include $path;
        }
    }
    
    public static function getFooter(string $name = null, array $data = []): void
    {
        $template = $name ? "footer-{$name}" : 'footer';
        $path = self::getTemplate($template);
        
        if (!$path && $name) {
            $path = self::getTemplate('footer');
        }
        
        if ($path) {
            extract($data);
            safe_do_action('get_footer', $name);
            include $path;
        }
    }
    
    public static function getSidebar(string $name = null, array $data = []): void
    {
        $template = $name ? "sidebar-{$name}" : 'sidebar';
        $path = self::getTemplate($template);
        
        if (!$path && $name) {
            $path = self::getTemplate('sidebar');
        }
        
        if ($path) {
            extract($data);
            safe_do_action('get_sidebar', $name);
            include $path;
        }
    }
    
    public static function getTemplatePart(string $slug, string $name = null, array $data = []): void
    {
        $templates = [];
        
        if ($name) {
            $templates[] = "{$slug}-{$name}";
        }
        $templates[] = $slug;
        
        foreach ($templates as $template) {
            $path = self::getTemplate($template);
            if ($path) {
                extract($data);
                include $path;
                return;
            }
        }
    }
    
    public static function hasTemplate(string $template, string $slug = null): bool
    {
        $slug = $slug ?? self::getActive();
        return file_exists(self::getPath($slug) . '/' . $template . '.php');
    }
    
    public static function getScreenshot(string $slug): ?string
    {
        $path = self::getPath($slug);
        
        foreach (['screenshot.png', 'screenshot.jpg', 'screenshot.gif'] as $file) {
            if (file_exists($path . '/' . $file)) {
                return self::getUrl($slug) . '/' . $file;
            }
        }
        
        return null;
    }
    
    public static function delete(string $slug): array
    {
        if ($slug === self::getActive()) {
            return ['success' => false, 'error' => 'Cannot delete active theme.'];
        }
        
        if ($slug === 'flavor') {
            return ['success' => false, 'error' => 'Cannot delete default theme.'];
        }
        
        $path = self::getPath($slug);
        
        if (!is_dir($path)) {
            return ['success' => false, 'error' => 'Theme not found.'];
        }
        
        // Recursively delete directory
        self::deleteDirectory($path);
        
        unset(self::$themes[$slug]);
        
        return ['success' => true];
    }
    
    private static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}

function get_theme_path(string $slug = null): string
{
    return Theme::getPath($slug);
}

function get_theme_url(string $slug = null): string
{
    return Theme::getUrl($slug);
}

function get_active_theme(): string
{
    return Theme::getActive();
}

function get_header(string $name = null, array $data = []): void
{
    Theme::getHeader($name, $data);
}

function get_footer(string $name = null, array $data = []): void
{
    Theme::getFooter($name, $data);
}

function get_sidebar(string $name = null, array $data = []): void
{
    Theme::getSidebar($name, $data);
}

function get_template_part(string $slug, string $name = null, array $data = []): void
{
    Theme::getTemplatePart($slug, $name, $data);
}

function get_template(string $template, array $data = []): void
{
    Theme::includeTemplate($template, $data);
}

function has_template(string $template): bool
{
    return Theme::hasTemplate($template);
}

function enqueue_theme_style(string $handle = 'theme-style', string $file = 'style.css', array $deps = [], string $version = ''): void
{
    $url = get_theme_url() . '/' . $file;
    enqueue_style($handle, $url, $deps, $version);
}

function enqueue_theme_script(string $handle, string $file, array $deps = [], string $version = '', bool $inFooter = true): void
{
    $url = get_theme_url() . '/' . $file;
    enqueue_script($handle, $url, $deps, $version, $inFooter);
}
