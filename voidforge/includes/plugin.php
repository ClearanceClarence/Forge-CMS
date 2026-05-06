<?php
/**
 * Plugin System - VoidForge CMS v0.1.2
 * 
 * Provides a comprehensive plugin API including:
 * - Hooks & Filters
 * - Shortcodes
 * - Plugin Settings API
 * - Admin Notices
 * - Asset Enqueueing
 * - AJAX Handlers
 * - Scheduled Tasks
 * - Widgets
 * - REST API Extensions
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class Plugin
{
    /** @var array Registered actions */
    private static array $actions = [];
    
    /** @var array Registered filters */
    private static array $filters = [];
    
    /** @var array Registered shortcodes */
    private static array $shortcodes = [];
    
    /** @var array Registered content tags */
    private static array $tags = [];
    
    /** @var array Loaded plugins */
    private static array $plugins = [];
    
    /** @var array Plugin metadata cache */
    private static array $pluginData = [];
    
    /** @var array Registered admin pages */
    private static array $adminPages = [];
    
    /** @var array Registered admin notices */
    private static array $adminNotices = [];
    
    /** @var array Registered scripts */
    private static array $scripts = [];
    
    /** @var array Registered styles */
    private static array $styles = [];
    
    /** @var array Registered AJAX handlers */
    private static array $ajaxHandlers = [];
    
    /** @var array Registered widgets */
    private static array $widgets = [];
    
    /** @var array Registered REST routes */
    private static array $restRoutes = [];
    
    /** @var array Registered cron jobs */
    private static array $cronJobs = [];
    
    /** @var array Plugin settings schemas */
    private static array $settingsSchemas = [];

    public static function init(): void
    {
        $pluginsDir = CMS_ROOT . '/plugins';
        
        if (!is_dir($pluginsDir)) {
            mkdir($pluginsDir, 0755, true);
        }
        
        $activePlugins = self::getActivePlugins();
        
        foreach ($activePlugins as $pluginSlug) {
            self::load($pluginSlug);
        }
        
        self::doAction('plugins_loaded');
        
        self::processCronJobs();
    }

    public static function load(string $slug): bool
    {
        $pluginFile = CMS_ROOT . '/plugins/' . $slug . '/' . $slug . '.php';
        
        if (!file_exists($pluginFile)) {
            return false;
        }
        
        $header = self::getPluginHeader($pluginFile);
        if (!self::checkRequirements($header)) {
            return false;
        }
        
        require_once $pluginFile;
        
        // Cache plugin data
        self::$plugins[$slug] = true;
        self::$pluginData[$slug] = $header;
        
        self::doAction('plugin_loaded_' . $slug);
        
        return true;
    }

    /**
     * Register a callback to fire when $hook is triggered via doAction().
     *
     * @param string   $hook         Hook name
     * @param callable $callback     Function or method to call
     * @param int      $priority     Lower numbers run first (default 10)
     * @param int      $acceptedArgs Number of arguments passed to the callback
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        if (!isset(self::$actions[$hook])) {
            self::$actions[$hook] = [];
        }
        
        self::$actions[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $acceptedArgs,
        ];
        
        usort(self::$actions[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Remove a previously registered action callback.
     * Priority must match the value used when the action was added.
     *
     * @return bool True if the callback was found and removed
     */
    public static function removeAction(string $hook, callable $callback, int $priority = 10): bool
    {
        if (!isset(self::$actions[$hook])) {
            return false;
        }
        
        foreach (self::$actions[$hook] as $key => $action) {
            if ($action['callback'] === $callback && $action['priority'] === $priority) {
                unset(self::$actions[$hook][$key]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Trigger all callbacks registered for $hook, passing $args to each one.
     */
    public static function doAction(string $hook, ...$args): void
    {
        if (!isset(self::$actions[$hook])) {
            return;
        }
        
        foreach (self::$actions[$hook] as $action) {
            $callArgs = array_slice($args, 0, $action['accepted_args']);
            call_user_func_array($action['callback'], $callArgs);
        }
    }

    /**
     * Register a callback that can modify a value when applyFilters() is called.
     * The callback receives $value as its first argument and must return it (modified or not).
     *
     * @param string   $hook         Filter name
     * @param callable $callback     Function that receives and returns the filtered value
     * @param int      $priority     Lower numbers run first (default 10)
     * @param int      $acceptedArgs Total arguments passed to the callback (including $value)
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        if (!isset(self::$filters[$hook])) {
            self::$filters[$hook] = [];
        }
        
        self::$filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $acceptedArgs,
        ];
        
        usort(self::$filters[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Pass $value through all filters registered for $hook and return the final result.
     *
     * @param mixed $value The value to filter
     * @param mixed ...$args Additional context arguments passed to each callback
     * @return mixed The filtered value
     */
    public static function applyFilters(string $hook, mixed $value, ...$args): mixed
    {
        if (!isset(self::$filters[$hook])) {
            return $value;
        }
        
        foreach (self::$filters[$hook] as $filter) {
            $callArgs = array_merge([$value], array_slice($args, 0, $filter['accepted_args'] - 1));
            $value = call_user_func_array($filter['callback'], $callArgs);
        }
        
        return $value;
    }

    public static function hasAction(string $hook): bool
    {
        return !empty(self::$actions[$hook]);
    }

    public static function hasFilter(string $hook): bool
    {
        return !empty(self::$filters[$hook]);
    }

    /**
     * Return the number of times $hook has been fired since the page load started.
     */
    public static function didAction(string $hook): int
    {
        static $counts = [];
        return $counts[$hook] ?? 0;
    }

    /**
     * Register a shortcode
     * 
     * @param string $tag Shortcode tag (e.g., 'button')
     * @param callable $callback Function that returns output
     */
    /**
     * Register a shortcode handler.
     * The callback receives ($atts, $content) and must return a string.
     *
     * @param string   $tag      Shortcode name, e.g. 'gallery'
     * @param callable $callback function(array $atts, ?string $content): string
     */
    public static function addShortcode(string $tag, callable $callback): void
    {
        self::$shortcodes[strtolower($tag)] = $callback;
    }

    public static function removeShortcode(string $tag): void
    {
        unset(self::$shortcodes[strtolower($tag)]);
    }

    public static function shortcodeExists(string $tag): bool
    {
        return isset(self::$shortcodes[strtolower($tag)]);
    }

    /**
     * Process shortcodes in content
     * 
     * Supports: [tag], [tag attr="value"], [tag]content[/tag]
     */
    /**
     * Parse and execute all shortcodes found in $content, returning the result.
     */
    public static function doShortcode(string $content): string
    {
        if (empty(self::$shortcodes) || strpos($content, '[') === false) {
            return $content;
        }

        $tagNames = array_keys(self::$shortcodes);
        $tagRegex = implode('|', array_map('preg_quote', $tagNames));
        
        // Match shortcodes with content: [tag]...[/tag]
        $pattern = '/\[(' . $tagRegex . ')(\s+[^\]]*?)?\](.*?)\[\/\1\]/s';
        $content = preg_replace_callback($pattern, function($matches) {
            $tag = strtolower($matches[1]);
            $attrs = self::parseShortcodeAttrs($matches[2] ?? '');
            $innerContent = $matches[3] ?? '';
            return self::executeShortcode($tag, $attrs, $innerContent);
        }, $content);
        
        // Match self-closing shortcodes: [tag] or [tag attr="value"]
        $pattern = '/\[(' . $tagRegex . ')(\s+[^\]]*?)?\]/';
        $content = preg_replace_callback($pattern, function($matches) {
            $tag = strtolower($matches[1]);
            $attrs = self::parseShortcodeAttrs($matches[2] ?? '');
            return self::executeShortcode($tag, $attrs, '');
        }, $content);
        
        return $content;
    }

    private static function parseShortcodeAttrs(string $attrString): array
    {
        $attrs = [];
        $attrString = trim($attrString);
        
        if (empty($attrString)) {
            return $attrs;
        }

        // Match: attr="value", attr='value', attr=value
        $pattern = '/([a-zA-Z_][a-zA-Z0-9_-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+))/';
        
        if (preg_match_all($pattern, $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[1];
                $value = $match[2] ?? $match[3] ?? $match[4] ?? '';
                $attrs[$key] = $value;
            }
        }

        return $attrs;
    }

    private static function executeShortcode(string $tag, array $attrs, string $content): string
    {
        if (!isset(self::$shortcodes[$tag])) {
            return '';
        }

        try {
            $result = call_user_func(self::$shortcodes[$tag], $attrs, $content, $tag);
            return is_string($result) ? $result : '';
        } catch (\Throwable $e) {
            if (defined('CMS_DEBUG') && CMS_DEBUG) {
                return '<!-- Shortcode error [' . $tag . ']: ' . esc($e->getMessage()) . ' -->';
            }
            return '';
        }
    }

    public static function getShortcodes(): array
    {
        return array_keys(self::$shortcodes);
    }

    public static function registerTag(string $name, callable $callback, array $options = []): void
    {
        $name = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
        
        self::$tags[$name] = [
            'callback' => $callback,
            'has_content' => $options['has_content'] ?? false,
            'description' => $options['description'] ?? '',
        ];
    }

    public static function processContent(string $content): string
    {
        $content = self::doShortcode($content);
        
        // Then process legacy tags
        if (empty(self::$tags) || strpos($content, '{') === false) {
            return $content;
        }

        foreach (self::$tags as $name => $tag) {
            if ($tag['has_content']) {
                $pattern = '/\{' . preg_quote($name, '/') . '(\s+[^}]*)?\}(.*?)\{\/' . preg_quote($name, '/') . '\}/s';
                $content = preg_replace_callback($pattern, function($matches) use ($name, $tag) {
                    $attrs = self::parseShortcodeAttrs($matches[1] ?? '');
                    $innerContent = $matches[2] ?? '';
                    return self::executeTag($name, $attrs, $innerContent);
                }, $content);
            }
        }

        $pattern = '/\{([a-zA-Z0-9_-]+)(\s+[^}]*)?\}/';
        $content = preg_replace_callback($pattern, function($matches) {
            $name = strtolower($matches[1]);
            if (!isset(self::$tags[$name]) || self::$tags[$name]['has_content']) {
                return $matches[0];
            }
            $attrs = self::parseShortcodeAttrs($matches[2] ?? '');
            return self::executeTag($name, $attrs, '');
        }, $content);

        return $content;
    }

    private static function executeTag(string $name, array $attrs, string $content): string
    {
        if (!isset(self::$tags[$name])) {
            return '';
        }

        try {
            $result = call_user_func(self::$tags[$name]['callback'], $attrs, $content, $name);
            return is_string($result) ? $result : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function getTags(): array
    {
        return self::$tags;
    }

    /**
     * Register plugin settings
     * 
     * @param string $pluginSlug Plugin identifier
     * @param array $schema Settings schema definition
     */
    /**
     * Declare the settings schema for a plugin.
     * The schema defines field types and defaults used by the settings UI.
     *
     * @param array<string,array{type:string,default:mixed,label?:string}> $schema
     */
    public static function registerSettings(string $pluginSlug, array $schema): void
    {
        self::$settingsSchemas[$pluginSlug] = $schema;
    }

    public static function getSettingsSchema(string $pluginSlug): array
    {
        return self::$settingsSchemas[$pluginSlug] ?? [];
    }

    /**
     * Get a single setting value for a plugin.
     *
     * @param mixed $default Returned when the key does not exist
     * @return mixed
     */
    public static function getSetting(string $pluginSlug, string $key, mixed $default = null): mixed
    {
        $settings = getOption('plugin_settings_' . $pluginSlug, []);
        return $settings[$key] ?? $default;
    }

    public static function setSetting(string $pluginSlug, string $key, mixed $value): void
    {
        $settings = getOption('plugin_settings_' . $pluginSlug, []);
        $settings[$key] = $value;
        setOption('plugin_settings_' . $pluginSlug, $settings);
    }

    public static function getSettings(string $pluginSlug): array
    {
        return getOption('plugin_settings_' . $pluginSlug, []);
    }

    public static function saveSettings(string $pluginSlug, array $settings): void
    {
        setOption('plugin_settings_' . $pluginSlug, $settings);
    }

    public static function deleteSettings(string $pluginSlug): void
    {
        deleteOption('plugin_settings_' . $pluginSlug);
    }

    /**
     * Register a plugin admin page that appears in the sidebar under Plugins.
     *
     * @param array{
     *   title:string,
     *   icon?:string,
     *   capability?:string,
     *   callback:callable,
     *   position?:int,
     *   parent?:string
     * } $config
     */
    public static function registerAdminPage(string $slug, array $config): void
    {
        self::$adminPages[$slug] = array_merge([
            'title' => $slug,
            'menu_title' => $config['title'] ?? $slug,
            'icon' => 'puzzle',
            'parent' => null,
            'capability' => 'admin',
            'callback' => null,
            'position' => 99,
            'plugin' => null,
        ], $config);
    }

    public static function getAdminPages(): array
    {
        return self::$adminPages;
    }

    public static function getAdminPage(string $slug): ?array
    {
        return self::$adminPages[$slug] ?? null;
    }

    public static function renderAdminPage(string $slug): bool
    {
        $page = self::$adminPages[$slug] ?? null;
        
        if (!$page || !is_callable($page['callback'])) {
            return false;
        }
        
        call_user_func($page['callback']);
        return true;
    }

    /**
     * Add an admin notice
     * 
     * @param string $message Notice message
     * @param string $type Notice type: success, error, warning, info
     * @param bool $dismissible Can be dismissed
     */
    public static function addNotice(string $message, string $type = 'info', bool $dismissible = true): void
    {
        self::$adminNotices[] = [
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible,
        ];
    }

    public static function getNotices(): array
    {
        return self::$adminNotices;
    }

    public static function renderNotices(): string
    {
        $html = '';
        foreach (self::$adminNotices as $notice) {
            $class = 'notice notice-' . esc($notice['type']);
            if ($notice['dismissible']) {
                $class .= ' is-dismissible';
            }
            $html .= '<div class="' . $class . '"><p>' . esc($notice['message']) . '</p></div>';
        }
        return $html;
    }

    /**
     * Register a script to be output on the current page.
     *
     * @param string   $handle   Unique identifier
     * @param string   $src      URL to the JS file
     * @param string[] $deps     Handles this script depends on
     * @param bool     $inFooter Output before </body> (true) or in <head> (false)
     */
    public static function enqueueScript(string $handle, string $src, array $deps = [], string $version = '', bool $inFooter = true): void
    {
        self::$scripts[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'version' => $version ?: CMS_VERSION,
            'in_footer' => $inFooter,
        ];
    }

    /**
     * Register a stylesheet to be output in <head> on the current page.
     *
     * @param string   $handle Unique identifier
     * @param string   $src    URL to the CSS file
     * @param string[] $deps   Handles this stylesheet depends on
     */
    public static function enqueueStyle(string $handle, string $src, array $deps = [], string $version = ''): void
    {
        self::$styles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'version' => $version ?: CMS_VERSION,
        ];
    }

    public static function getScripts(): array
    {
        return self::$scripts;
    }

    public static function getStyles(): array
    {
        return self::$styles;
    }

    public static function renderStyles(): string
    {
        $html = '';
        foreach (self::$styles as $handle => $style) {
            $src = $style['src'];
            if ($style['version']) {
                $src .= (strpos($src, '?') !== false ? '&' : '?') . 'ver=' . $style['version'];
            }
            $html .= '<link rel="stylesheet" id="' . esc($handle) . '-css" href="' . esc($src) . '">' . "\n";
        }
        return $html;
    }

    public static function renderScripts(bool $footer = false): string
    {
        $html = '';
        foreach (self::$scripts as $handle => $script) {
            if ($script['in_footer'] !== $footer) {
                continue;
            }
            $src = $script['src'];
            if ($script['version']) {
                $src .= (strpos($src, '?') !== false ? '&' : '?') . 'ver=' . $script['version'];
            }
            $html .= '<script id="' . esc($handle) . '-js" src="' . esc($src) . '"></script>' . "\n";
        }
        return $html;
    }

    /**
     * Register an AJAX handler
     * 
     * @param string $action Action name
     * @param callable $callback Handler function
     * @param bool $nopriv Allow non-logged-in users
     */
    /**
     * Register a handler for an admin-ajax request.
     *
     * @param string   $action   The value of $_POST['action'] or $_GET['action']
     * @param callable $callback Handler that outputs the response and exits
     * @param bool     $nopriv   True to allow unauthenticated requests
     */
    public static function registerAjax(string $action, callable $callback, bool $nopriv = false): void
    {
        self::$ajaxHandlers[$action] = [
            'callback' => $callback,
            'nopriv' => $nopriv,
        ];
    }

    public static function handleAjax(string $action): void
    {
        if (!isset(self::$ajaxHandlers[$action])) {
            self::sendJsonError(['message' => 'Invalid action'], 400);
        }

        $handler = self::$ajaxHandlers[$action];
        
        if (!$handler['nopriv'] && !User::isLoggedIn()) {
            self::sendJsonError(['message' => 'Authentication required'], 401);
        }

        try {
            call_user_func($handler['callback']);
        } catch (\Throwable $e) {
            self::sendJsonError(['message' => $e->getMessage()], 500);
        }
    }

    public static function getAjaxHandlers(): array
    {
        return self::$ajaxHandlers;
    }

    public static function sendJsonSuccess(mixed $data = null, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    public static function sendJsonError(mixed $data = null, int $code = 400): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'data' => $data]);
        exit;
    }

    public static function registerWidget(string $id, array $config): void
    {
        self::$widgets[$id] = array_merge([
            'title' => $id,
            'description' => '',
            'callback' => null,
            'settings' => [],
        ], $config);
    }

    public static function getWidgets(): array
    {
        return self::$widgets;
    }

    public static function renderWidget(string $id, array $args = []): string
    {
        if (!isset(self::$widgets[$id]) || !is_callable(self::$widgets[$id]['callback'])) {
            return '';
        }

        try {
            ob_start();
            call_user_func(self::$widgets[$id]['callback'], $args);
            return ob_get_clean();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Register a REST API route
     * 
     * Routes are stored per-method to allow different handlers for GET/POST/etc on same path
     */
    /**
     * Register a REST API route under /api/{namespace}/{route}.
     *
     * @param array{
     *   methods:string[],
     *   callback:callable,
     *   permission_callback?:callable|null
     * } $config
     */
    public static function registerRestRoute(string $namespace, string $route, array $config): void
    {
        $routePath = $namespace . '/' . ltrim($route, '/');
        $methods = (array)($config['methods'] ?? ['GET']);
        
        // Initialize route if not exists
        if (!isset(self::$restRoutes[$routePath])) {
            self::$restRoutes[$routePath] = [];
        }
        
        foreach ($methods as $method) {
            $method = strtoupper($method);
            self::$restRoutes[$routePath][$method] = [
                'callback' => $config['callback'] ?? null,
                'permission_callback' => $config['permission_callback'] ?? null,
            ];
        }
    }

    public static function getRestRoutes(): array
    {
        return self::$restRoutes;
    }

    public static function handleRestRequest(string $path): bool
    {
        self::doAction('rest_api_init');
        
        if (empty(self::$restRoutes)) {
            return false;
        }
        
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);
        
        foreach (self::$restRoutes as $route => $methodHandlers) {
            $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $route);
            if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                
                        if (!isset($methodHandlers[$requestMethod])) {
                    $allowedMethods = array_keys($methodHandlers);
                    header('Allow: ' . implode(', ', $allowedMethods));
                    self::sendJsonError(['message' => 'Method not allowed'], 405);
                }
                
                $config = $methodHandlers[$requestMethod];
                
                // Allow filtering before dispatch (for auth, rate limiting)
                $error = self::applyFilters('rest_pre_dispatch', null, $path, $config);
                if ($error) {
                    self::sendJsonError($error, 403);
                }
                
                        $authError = self::applyFilters('rest_authentication_errors', null, $path);
                if ($authError) {
                    self::sendJsonError(['message' => $authError], 401);
                }

                        if ($config['permission_callback']) {
                    $permissionResult = call_user_func($config['permission_callback']);
                    if (!$permissionResult) {
                                        // If not authenticated, return 401; if authenticated but lacking permission, return 403
                        $isAuthenticated = false;
                        
                                        if (class_exists('RestAPI') && method_exists('RestAPI', 'getCurrentUserId')) {
                            $isAuthenticated = RestAPI::getCurrentUserId() !== null;
                        } elseif (class_exists('User') && method_exists('User', 'isLoggedIn')) {
                            $isAuthenticated = User::isLoggedIn();
                        }
                        
                        if (!$isAuthenticated) {
                            self::sendJsonError(['message' => 'Authentication required'], 401);
                        } else {
                            self::sendJsonError(['message' => 'Forbidden'], 403);
                        }
                    }
                }

                // Extract params from URL
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // For POST/PUT/PATCH, merge in the request body
                if (in_array($requestMethod, ['POST', 'PUT', 'PATCH'])) {
                    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                    if (strpos($contentType, 'application/json') !== false) {
                        $rawBody = file_get_contents('php://input');
                        $jsonData = json_decode($rawBody, true) ?? [];
                        $params = array_merge($params, $jsonData);
                    } else {
                        // Form data
                        $params = array_merge($params, $_POST);
                    }
                }
                
                try {
                    $result = call_user_func($config['callback'], $params);
                    
                                $result = self::applyFilters('rest_post_dispatch', $result, $path, $config);
                    
                    self::sendJsonSuccess($result);
                } catch (\Throwable $e) {
                    $code = $e->getCode() ?: 500;
                    if ($code < 100 || $code > 599) $code = 500;
                    self::sendJsonError(['message' => $e->getMessage()], $code);
                }
            }
        }

        return false;
    }

    /**
     * Register a recurring task to run at the given interval.
     * Tasks are checked on every page load (pseudo-cron).
     *
     * @param string   $hook     Unique task name
     * @param string   $interval 'hourly', 'daily', or a number of seconds
     * @param callable $callback Function to run when the interval has elapsed
     */
    public static function scheduleCron(string $hook, string $interval, callable $callback): void
    {
        self::$cronJobs[$hook] = [
            'interval' => $interval,
            'callback' => $callback,
        ];
    }

    public static function unscheduleCron(string $hook): void
    {
        unset(self::$cronJobs[$hook]);
        deleteOption('cron_last_run_' . $hook);
    }

    public static function processCronJobs(): void
    {
        // Default intervals
        $intervals = [
            'hourly' => 3600,
            'twicedaily' => 43200,
            'daily' => 86400,
            'weekly' => 604800,
        ];
        
        // Allow plugins to add custom intervals
        $intervals = self::applyFilters('cron_schedules', $intervals);

        foreach (self::$cronJobs as $hook => $job) {
            $lastRun = (int)getOption('cron_last_run_' . $hook, 0);
            $interval = $intervals[$job['interval']] ?? 86400;
            
            if (time() - $lastRun >= $interval) {
                try {
                    call_user_func($job['callback']);
                    setOption('cron_last_run_' . $hook, time());
                } catch (\Throwable $e) {
                    // Log error silently
                }
            }
        }
    }

    public static function getActivePlugins(): array
    {
        $plugins = getOption('active_plugins', []);
        
        if (is_string($plugins)) {
            $plugins = json_decode($plugins, true);
        }
        
        return is_array($plugins) ? $plugins : [];
    }

    /**
     * Activate a plugin by slug, running its activation hook if defined.
     *
     * @return array{success:bool,message:string}
     */
    public static function activate(string $slug): array
    {
        $pluginFile = CMS_ROOT . '/plugins/' . $slug . '/' . $slug . '.php';
        
        if (!file_exists($pluginFile)) {
            return ['success' => false, 'error' => 'Plugin not found'];
        }
        
        $header = self::getPluginHeader($pluginFile);
        $reqCheck = self::checkRequirements($header, true);
        if ($reqCheck !== true) {
            return ['success' => false, 'error' => $reqCheck];
        }
        
        $activePlugins = self::getActivePlugins();
        
        if (in_array($slug, $activePlugins)) {
            return ['success' => false, 'error' => 'Plugin already active'];
        }
        
        require_once $pluginFile;
        
        // Run activation hook
        self::doAction('plugin_activate_' . $slug);
        self::doAction('plugin_activated', $slug);
        
        $activePlugins[] = $slug;
        setOption('active_plugins', json_encode($activePlugins));
        
        return ['success' => true];
    }

    /**
     * Deactivate a plugin, removing it from the active list.
     *
     * @return array{success:bool,message:string}
     */
    public static function deactivate(string $slug): array
    {
        $activePlugins = self::getActivePlugins();
        
        if (!in_array($slug, $activePlugins)) {
            return ['success' => false, 'error' => 'Plugin not active'];
        }
        
        // Run deactivation hook
        self::doAction('plugin_deactivate_' . $slug);
        self::doAction('plugin_deactivated', $slug);
        
        $activePlugins = array_filter($activePlugins, fn($p) => $p !== $slug);
        setOption('active_plugins', json_encode(array_values($activePlugins)));
        
        return ['success' => true];
    }

    public static function uninstall(string $slug): array
    {
        // Deactivate first
        self::deactivate($slug);
        
        // Run uninstall hook
        self::doAction('plugin_uninstall_' . $slug);
        
        self::deleteSettings($slug);
        
        $pluginDir = CMS_ROOT . '/plugins/' . $slug;
        if (is_dir($pluginDir)) {
            self::deleteDirectory($pluginDir);
        }
        
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

    public static function getAll(): array
    {
        $plugins = [];
        $pluginsDir = CMS_ROOT . '/plugins';
        
        if (!is_dir($pluginsDir)) {
            return $plugins;
        }
        
        $dirs = scandir($pluginsDir);
        $activePlugins = self::getActivePlugins();
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || $dir === '.gitkeep') {
                continue;
            }
            
            $pluginFile = $pluginsDir . '/' . $dir . '/' . $dir . '.php';
            
            if (!file_exists($pluginFile)) {
                continue;
            }
            
            $header = self::getPluginHeader($pluginFile);
            $hasSettings = isset(self::$settingsSchemas[$dir]) || 
                          (isset(self::$adminPages[$dir . '-settings']));
            
            $plugins[] = [
                'slug' => $dir,
                'name' => $header['name'] ?? $dir,
                'description' => $header['description'] ?? '',
                'version' => $header['version'] ?? '1.0.0',
                'author' => $header['author'] ?? '',
                'author_uri' => $header['author_uri'] ?? '',
                'plugin_uri' => $header['plugin_uri'] ?? '',
                'requires_php' => $header['requires_php'] ?? '',
                'requires_cms' => $header['requires_cms'] ?? '',
                'active' => in_array($dir, $activePlugins),
                'has_settings' => $hasSettings,
                'file' => $pluginFile,
            ];
        }
        
        return $plugins;
    }

    public static function getPluginHeader(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file, false, null, 0, 4096);
        
        $headers = [
            'name' => 'Plugin Name',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author',
            'author_uri' => 'Author URI',
            'plugin_uri' => 'Plugin URI',
            'requires_php' => 'Requires PHP',
            'requires_cms' => 'Requires CMS',
            'license' => 'License',
            'text_domain' => 'Text Domain',
        ];
        
        $result = [];
        
        foreach ($headers as $key => $label) {
            if (preg_match('/^[\s\*]*' . preg_quote($label, '/') . ':\s*(.+)$/mi', $content, $matches)) {
                $result[$key] = trim($matches[1]);
            }
        }
        
        return $result;
    }

    public static function checkRequirements(array $header, bool $returnError = false): bool|string
    {
        if (!empty($header['requires_php'])) {
            if (version_compare(PHP_VERSION, $header['requires_php'], '<')) {
                $error = 'Requires PHP ' . $header['requires_php'] . ' or higher';
                return $returnError ? $error : false;
            }
        }
        
        if (!empty($header['requires_cms'])) {
            if (version_compare(CMS_VERSION, $header['requires_cms'], '<')) {
                $error = 'Requires VoidForge CMS ' . $header['requires_cms'] . ' or higher';
                return $returnError ? $error : false;
            }
        }
        
        return true;
    }

    public static function isActive(string $slug): bool
    {
        return in_array($slug, self::getActivePlugins());
    }

    public static function getLoaded(): array
    {
        return array_keys(self::$plugins);
    }

    public static function getPluginData(string $slug): array
    {
        return self::$pluginData[$slug] ?? [];
    }

    public static function createTable(string $tableName, string $sql): bool
    {
        $fullTableName = Database::table($tableName);
        $createSql = "CREATE TABLE IF NOT EXISTS `{$fullTableName}` ({$sql}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            Database::query($createSql);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function dropTable(string $tableName): bool
    {
        $fullTableName = Database::table($tableName);
        
        try {
            Database::query("DROP TABLE IF EXISTS `{$fullTableName}`");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
{
    Plugin::addAction($hook, $callback, $priority, $acceptedArgs);
}

function remove_action(string $hook, callable $callback, int $priority = 10): bool
{
    return Plugin::removeAction($hook, $callback, $priority);
}

function do_action(string $hook, ...$args): void
{
    Plugin::doAction($hook, ...$args);
}

function has_action(string $hook): bool
{
    return Plugin::hasAction($hook);
}

function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
{
    Plugin::addFilter($hook, $callback, $priority, $acceptedArgs);
}

function apply_filters(string $hook, mixed $value, ...$args): mixed
{
    return Plugin::applyFilters($hook, $value, ...$args);
}

function has_filter(string $hook): bool
{
    return Plugin::hasFilter($hook);
}

// Shortcodes
function add_shortcode(string $tag, callable $callback): void
{
    Plugin::addShortcode($tag, $callback);
}

function remove_shortcode(string $tag): void
{
    Plugin::removeShortcode($tag);
}

function shortcode_exists(string $tag): bool
{
    return Plugin::shortcodeExists($tag);
}

function do_shortcode(string $content): string
{
    return Plugin::doShortcode($content);
}

// Legacy tags
function register_tag(string $name, callable $callback, array $options = []): void
{
    Plugin::registerTag($name, $callback, $options);
}

function process_tags(string $content): string
{
    return Plugin::processContent($content);
}

// Admin pages
function add_admin_page(string $slug, array $config): void
{
    // Default parent to 'plugins' if not specified
    if (!array_key_exists('parent', $config)) {
        $config['parent'] = 'plugins';
    }
    Plugin::registerAdminPage($slug, $config);
}

function get_admin_pages(): array
{
    return Plugin::getAdminPages();
}

// Admin notices
function add_admin_notice(string $message, string $type = 'info', bool $dismissible = true): void
{
    Plugin::addNotice($message, $type, $dismissible);
}

// Assets
function enqueue_script(string $handle, string $src, array $deps = [], string $version = '', bool $inFooter = true): void
{
    Plugin::enqueueScript($handle, $src, $deps, $version, $inFooter);
}

function enqueue_style(string $handle, string $src, array $deps = [], string $version = ''): void
{
    Plugin::enqueueStyle($handle, $src, $deps, $version);
}

// Settings
function register_plugin_settings(string $pluginSlug, array $schema): void
{
    Plugin::registerSettings($pluginSlug, $schema);
}

function get_plugin_setting(string $pluginSlug, string $key, mixed $default = null): mixed
{
    return Plugin::getSetting($pluginSlug, $key, $default);
}

function set_plugin_setting(string $pluginSlug, string $key, mixed $value): void
{
    Plugin::setSetting($pluginSlug, $key, $value);
}

// AJAX
function register_ajax_handler(string $action, callable $callback, bool $nopriv = false): void
{
    Plugin::registerAjax($action, $callback, $nopriv);
}

function vf_send_json_success(mixed $data = null, int $code = 200): void
{
    Plugin::sendJsonSuccess($data, $code);
}

function vf_send_json_error(mixed $data = null, int $code = 400): void
{
    Plugin::sendJsonError($data, $code);
}

// Legacy aliases for backward compatibility (deprecated)
function wp_send_json_success(mixed $data = null, int $code = 200): void
{
    Plugin::sendJsonSuccess($data, $code);
}

function wp_send_json_error(mixed $data = null, int $code = 400): void
{
    Plugin::sendJsonError($data, $code);
}

// Widgets
function register_widget(string $id, array $config): void
{
    Plugin::registerWidget($id, $config);
}

// REST API
function register_rest_route(string $namespace, string $route, array $config): void
{
    Plugin::registerRestRoute($namespace, $route, $config);
}

// Cron
function schedule_event(string $hook, string $interval, callable $callback): void
{
    Plugin::scheduleCron($hook, $interval, $callback);
}

function unschedule_event(string $hook): void
{
    Plugin::unscheduleCron($hook);
}

// Database
function create_plugin_table(string $tableName, string $sql): bool
{
    return Plugin::createTable($tableName, $sql);
}

function drop_plugin_table(string $tableName): bool
{
    return Plugin::dropTable($tableName);
}

function the_title(array $post): string
{
    $title = $post['title'] ?? '';
    return Plugin::applyFilters('the_title', $title, $post);
}

function the_excerpt(array $post, int $length = 55): string
{
    $excerpt = $post['excerpt'] ?? '';
    
    // Auto-generate from content if empty
    if (empty($excerpt)) {
        $content = strip_tags($post['content'] ?? '');
        $excerpt = wp_trim_words($content, $length);
    }
    
    return Plugin::applyFilters('the_excerpt', $excerpt, $post);
}

function vf_redirect(string $url, int $status = 302): void
{
    $url = Plugin::applyFilters('vf_redirect', $url);
    header('Location: ' . $url, true, $status);
    exit;
}

function vf_shutdown(): void
{
    Plugin::doAction('shutdown');
}

// Register shutdown function
register_shutdown_function('vf_shutdown');
