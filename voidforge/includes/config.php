<?php
/**
 * VoidForge CMS Configuration
 * 
 * This file will be automatically configured during installation.
 * Do not modify unless you know what you're doing.
 */

// Prevent direct access
defined('CMS_ROOT') or die('Direct access not allowed');

// Branding
define('CMS_NAME', 'VoidForge');
define('CMS_VERSION', '0.3.2');

// Database settings - configured during installation
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', '');

// Site settings - configured during installation
define('SITE_URL', '');
define('ADMIN_URL', SITE_URL . '/admin');

// Paths
define('INCLUDES_PATH', CMS_ROOT . '/includes');
define('ADMIN_PATH', CMS_ROOT . '/admin');
define('THEMES_PATH', CMS_ROOT . '/themes');
define('UPLOADS_PATH', CMS_ROOT . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');
define('PLUGINS_PATH', CMS_ROOT . '/plugins');
define('PLUGINS_URL', SITE_URL . '/plugins');
define('THEMES_URL', SITE_URL . '/themes');

// Session settings
define('SESSION_NAME', 'voidforge_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Security
define('HASH_COST', 12);
define('AUTH_KEY', '');
define('SECURE_AUTH_KEY', '');
define('NONCE_SALT', '');

// Debug mode — false in production, true for local development
// Set via environment variable: VOIDFORGE_DEBUG=1
// Or override here directly (not recommended for production installs)
define('CMS_DEBUG', (bool) ($_ENV['VOIDFORGE_DEBUG'] ?? getenv('VOIDFORGE_DEBUG') ?? false));

// Timezone
date_default_timezone_set('UTC');

// Error reporting — controlled by CMS_DEBUG
if (CMS_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
