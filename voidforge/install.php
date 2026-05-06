<?php
/**
 * VoidForge CMS Installer
 * Modern, beautiful installation wizard
 */

// Define CMS_ROOT first (needed for includes)
if (!defined('CMS_ROOT')) define('CMS_ROOT', __DIR__);

// Check if already installed
$isInstalled = false;
if (file_exists(CMS_ROOT . '/includes/config.php')) {
    require_once CMS_ROOT . '/includes/config.php';
    if (defined('DB_NAME') && DB_NAME !== '') {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'vf_';
            $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}users'");
            if ($stmt->rowCount() > 0) {
                $isInstalled = true;
            }
        } catch (Exception $e) {
            // Connection failed - allow install
        }
    }
}

// Define fallbacks only if config.php didn't define them
if (!defined('CMS_VERSION')) define('CMS_VERSION', '0.3.2');
if (!defined('CMS_NAME')) define('CMS_NAME', 'VoidForge CMS');

if ($isInstalled) {
    header('Location: admin/');
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Check system requirements
function checkRequirements() {
    return [
        'php' => [
            'name' => 'PHP 7.4 or higher',
            'current' => PHP_VERSION,
            'pass' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'icon' => 'code'
        ],
        'pdo' => [
            'name' => 'PDO MySQL Extension',
            'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Not Found',
            'pass' => extension_loaded('pdo_mysql'),
            'icon' => 'database'
        ],
        'json' => [
            'name' => 'JSON Extension',
            'current' => extension_loaded('json') ? 'Enabled' : 'Not Found',
            'pass' => extension_loaded('json'),
            'icon' => 'braces'
        ],
        'gd' => [
            'name' => 'GD Library (optional)',
            'current' => extension_loaded('gd') ? 'Enabled' : 'Not Found',
            'pass' => extension_loaded('gd'),
            'optional' => true,
            'icon' => 'image'
        ],
        'uploads' => [
            'name' => 'Uploads Directory',
            'current' => is_writable(CMS_ROOT . '/uploads') ? 'Writable' : 'Not Writable',
            'pass' => is_writable(CMS_ROOT . '/uploads'),
            'icon' => 'folder'
        ],
        'config' => [
            'name' => 'Config Directory',
            'current' => is_writable(CMS_ROOT . '/includes') ? 'Writable' : 'Not Writable',
            'pass' => is_writable(CMS_ROOT . '/includes'),
            'icon' => 'settings'
        ]
    ];
}

// Process installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $dbPrefix = trim($_POST['db_prefix'] ?? 'vf_');
    $siteUrl = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $siteTitle = trim($_POST['site_title'] ?? '');
    $adminUser = trim($_POST['admin_user'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = $_POST['admin_pass'] ?? '';
    $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';

    // Validation
    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $error = 'Please fill in all database fields.';
    } elseif (empty($siteUrl) || empty($siteTitle)) {
        $error = 'Please fill in site URL and title.';
    } elseif (empty($adminUser) || empty($adminEmail) || empty($adminPass)) {
        $error = 'Please fill in all admin account fields.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($adminPass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($adminPass !== $adminPassConfirm) {
        $error = 'Passwords do not match.';
    } else {
        // Test database connection
        try {
            $dsn = "mysql:host={$dbHost};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Create tables
            $tables = "
                CREATE TABLE IF NOT EXISTS `{$dbPrefix}users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `username` VARCHAR(50) NOT NULL UNIQUE,
                    `email` VARCHAR(100) NOT NULL UNIQUE,
                    `password` VARCHAR(255) NOT NULL,
                    `display_name` VARCHAR(100),
                    `role` ENUM('admin','editor','author','subscriber') DEFAULT 'subscriber',
                    `avatar_url` VARCHAR(255),
                    `bio` TEXT,
                    `last_login` DATETIME DEFAULT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}posts` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(255),
                    `slug` VARCHAR(255),
                    `content` LONGTEXT,
                    `excerpt` TEXT,
                    `status` VARCHAR(20) DEFAULT 'draft',
                    `post_type` VARCHAR(50) DEFAULT 'post',
                    `author_id` INT,
                    `parent_id` INT DEFAULT 0,
                    `menu_order` INT DEFAULT 0,
                    `featured_image` INT,
                    `template` VARCHAR(100),
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `published_at` DATETIME,
                    `trashed_at` DATETIME DEFAULT NULL,
                    INDEX `idx_slug` (`slug`),
                    INDEX `idx_status` (`status`),
                    INDEX `idx_post_type` (`post_type`),
                    INDEX `idx_author` (`author_id`),
                    INDEX `idx_trashed_at` (`trashed_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}postmeta` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `post_id` INT NOT NULL,
                    `meta_key` VARCHAR(255) NOT NULL,
                    `meta_value` LONGTEXT,
                    INDEX `idx_post_id` (`post_id`),
                    INDEX `idx_meta_key` (`meta_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}media` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `filename` VARCHAR(255) NOT NULL,
                    `filepath` VARCHAR(255) NOT NULL,
                    `url` VARCHAR(255) NOT NULL,
                    `mime_type` VARCHAR(100),
                    `size` INT,
                    `width` INT,
                    `height` INT,
                    `alt_text` VARCHAR(255),
                    `caption` TEXT,
                    `folder` VARCHAR(100) DEFAULT 'uncategorized',
                    `uploaded_by` INT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}options` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `option_name` VARCHAR(191) NOT NULL UNIQUE,
                    `option_value` LONGTEXT,
                    `autoload` TINYINT(1) DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}taxonomies` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `type` VARCHAR(50) DEFAULT 'category',
                    `description` TEXT,
                    `parent_id` INT DEFAULT 0,
                    `count` INT DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}term_relationships` (
                    `post_id` INT NOT NULL,
                    `term_id` INT NOT NULL,
                    PRIMARY KEY (`post_id`, `term_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}menus` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `location` VARCHAR(50),
                    `items` LONGTEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}comments` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `post_id` INT NOT NULL,
                    `parent_id` INT DEFAULT 0,
                    `author_name` VARCHAR(100),
                    `author_email` VARCHAR(100),
                    `author_url` VARCHAR(255),
                    `author_ip` VARCHAR(45),
                    `user_id` INT,
                    `content` TEXT NOT NULL,
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_post_id` (`post_id`),
                    INDEX `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}revisions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `post_id` INT NOT NULL,
                    `title` VARCHAR(255),
                    `content` LONGTEXT,
                    `author_id` INT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_post_id` (`post_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `{$dbPrefix}api_keys` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `name` VARCHAR(100) NOT NULL,
                    `api_key` VARCHAR(64) NOT NULL UNIQUE,
                    `api_secret_hash` VARCHAR(255) NOT NULL,
                    `permissions` JSON,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `last_used_at` DATETIME,
                    `expires_at` DATETIME,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_user_id` (`user_id`),
                    INDEX `idx_api_key` (`api_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";

            // Execute table creation
            $pdo->exec($tables);

            // Create admin user
            $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO `{$dbPrefix}users` (username, email, password, display_name, role) VALUES (?, ?, ?, ?, 'admin')");
            $stmt->execute([$adminUser, $adminEmail, $hashedPass, $adminUser]);

            // Insert default options
            $options = [
                ['site_title', $siteTitle],
                ['site_description', 'A fresh VoidForge CMS installation'],
                ['site_url', $siteUrl],
                ['admin_email', $adminEmail],
                ['posts_per_page', '10'],
                ['date_format', 'M j, Y'],
                ['time_format', 'g:i a'],
                ['active_theme', 'flavor'],
                ['homepage_display', 'posts'],
                ['comments_enabled', '1'],
                ['comment_moderation', 'manual'],
                ['db_version', '1']
            ];

            $stmt = $pdo->prepare("INSERT INTO `{$dbPrefix}options` (option_name, option_value) VALUES (?, ?)");
            foreach ($options as $opt) {
                $stmt->execute($opt);
            }

            // Create welcome post
            $welcomeContent = '<p>Welcome to VoidForge CMS! This is your first post. Edit or delete it, then start creating!</p>';
            $stmt = $pdo->prepare("INSERT INTO `{$dbPrefix}posts` (title, slug, content, status, post_type, author_id, published_at) VALUES (?, ?, ?, 'published', 'post', 1, NOW())");
            $stmt->execute(['Welcome to VoidForge CMS', 'welcome-to-voidforge-cms', $welcomeContent]);

            // Write config file
            $configContent = "<?php
/**
 * VoidForge CMS Configuration
 * Generated: " . date('Y-m-d H:i:s') . "
 */

// Database
define('DB_HOST', " . var_export($dbHost, true) . ");
define('DB_NAME', " . var_export($dbName, true) . ");
define('DB_USER', " . var_export($dbUser, true) . ");
define('DB_PASS', " . var_export($dbPass, true) . ");
define('DB_PREFIX', " . var_export($dbPrefix, true) . ");

// Site
define('SITE_URL', " . var_export($siteUrl, true) . ");
define('ADMIN_URL', SITE_URL . '/admin');

// CMS
define('CMS_VERSION', '" . CMS_VERSION . "');
define('CMS_NAME', 'VoidForge');

// Paths  
define('ADMIN_PATH', CMS_ROOT . '/admin');
define('INCLUDES_PATH', CMS_ROOT . '/includes');
define('THEMES_PATH', CMS_ROOT . '/themes');
define('PLUGINS_PATH', CMS_ROOT . '/plugins');
define('UPLOADS_PATH', CMS_ROOT . '/uploads');
define('UPLOADS_URL', SITE_URL . '/uploads');
define('THEMES_URL', SITE_URL . '/themes');
define('PLUGINS_URL', SITE_URL . '/plugins');

// Security
define('HASH_COST', 12);
define('AUTH_KEY', '" . bin2hex(random_bytes(32)) . "');
define('SECURE_AUTH_KEY', '" . bin2hex(random_bytes(32)) . "');
define('NONCE_SALT', '" . bin2hex(random_bytes(32)) . "');
define('SESSION_NAME', 'voidforge_session');
define('SESSION_LIFETIME', 86400);

// Debug mode — set VOIDFORGE_DEBUG=1 in your environment to enable
define('CMS_DEBUG', (bool) (\$_ENV['VOIDFORGE_DEBUG'] ?? getenv('VOIDFORGE_DEBUG') ?? false));

// Error reporting
if (CMS_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
";

            file_put_contents(CMS_ROOT . '/includes/config.php', $configContent);

            // Redirect to success
            header('Location: install.php?step=3');
            exit;

        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Installation error: ' . $e->getMessage();
        }
    }
    $step = 2;
}

// Auto-detect site URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = dirname($_SERVER['SCRIPT_NAME']);
$detectedUrl = $protocol . '://' . $host . $path;

$requirements = checkRequirements();
$canProceed = true;
foreach ($requirements as $req) {
    if (!$req['pass'] && empty($req['optional'])) {
        $canProceed = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install <?= CMS_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --radius: 12px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.5;
        }

        .installer {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .installer-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .installer-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        }
        .installer-header h1 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
        }
        .installer-header p {
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
            position: relative;
        }
        .logo-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            backdrop-filter: blur(10px);
        }
        .logo-icon svg {
            width: 36px;
            height: 36px;
            color: white;
        }

        /* Steps */
        /* Steps Progress */
        .steps {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background: var(--card);
            border-bottom: 1px solid var(--border);
            position: relative;
        }
        
        .steps-container {
            display: flex;
            align-items: center;
            gap: 0;
            position: relative;
        }
        
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }
        
        .step-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 600;
            background: var(--bg);
            border: 2px solid var(--border);
            color: var(--text-muted);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .step-circle svg {
            width: 20px;
            height: 20px;
        }
        
        .step-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: color 0.3s ease;
            white-space: nowrap;
        }
        
        .step-connector {
            width: 80px;
            height: 2px;
            background: var(--border);
            position: relative;
            transition: background 0.3s ease;
        }
        
        .step-connector::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: width 0.5s ease;
        }
        
        /* Active Step */
        .step-item.active .step-circle {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-color: transparent;
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            transform: scale(1.05);
        }
        
        .step-item.active .step-label {
            color: var(--primary);
        }
        
        /* Completed Step */
        .step-item.done .step-circle {
            background: var(--success);
            border-color: transparent;
            color: white;
        }
        
        .step-item.done .step-label {
            color: var(--success);
        }
        
        .step-item.done + .step-connector::after {
            width: 100%;
        }
        
        /* Pulse animation for active step */
        .step-item.active .step-circle::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            opacity: 0.5;
            animation: stepPulse 2s ease-in-out infinite;
        }
        
        @keyframes stepPulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0; }
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .steps {
                padding: 1.5rem 1rem;
            }
            .step-item {
                padding: 0 1rem;
            }
            .step-circle {
                width: 40px;
                height: 40px;
                font-size: 0.875rem;
            }
            .step-label {
                font-size: 0.6875rem;
            }
            .step-connector {
                width: 40px;
            }
        }

        /* Content */
        .installer-content {
            flex: 1;
            padding: 3rem 2rem;
            display: flex;
            justify-content: center;
        }
        .installer-card {
            width: 100%;
            max-width: 600px;
        }

        /* Card sections */
        .card {
            background: var(--card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
        }
        .card-header h2 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }
        .card-header h2 svg {
            width: 20px;
            height: 20px;
            color: var(--primary);
        }
        .card-body {
            padding: 1.5rem;
        }

        /* Requirements */
        .req-list { list-style: none; }
        .req-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 0;
            border-bottom: 1px solid var(--border);
        }
        .req-item:last-child { border-bottom: none; }
        .req-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .req-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
        }
        .req-icon svg { width: 18px; height: 18px; color: var(--text-secondary); }
        .req-name {
            font-weight: 500;
            font-size: 0.9375rem;
        }
        .req-value {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .req-status {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .req-status.pass { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .req-status.fail { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .req-status.warn { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .req-status svg { width: 14px; height: 14px; }

        /* Forms */
        .form-section {
            margin-bottom: 2rem;
        }
        .form-section:last-child { margin-bottom: 0; }
        .form-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text);
        }
        .form-group label .req { color: var(--danger); }
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9375rem;
            font-family: inherit;
            transition: all 0.15s;
            background: var(--card);
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .form-group input::placeholder { color: var(--text-muted); }
        .form-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.375rem;
        }

        /* Alerts */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        .alert svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 0.125rem; }
        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .alert.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* Buttons */
        .btn-row {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s;
            border: none;
            text-decoration: none;
        }
        .btn svg { width: 18px; height: 18px; }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn-secondary {
            background: var(--bg);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { border-color: var(--primary); color: var(--primary); }

        /* Success Page */
        .success-page {
            text-align: center;
            padding: 3rem 2rem;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--success), #34d399);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .success-icon svg { width: 40px; height: 40px; color: white; }
        .success-page h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .success-page p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        /* Footer */
        .installer-footer {
            padding: 1.5rem 2rem;
            text-align: center;
            border-top: 1px solid var(--border);
            background: var(--card);
        }
        .installer-footer p {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .success-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="installer">
        <!-- Header -->
        <div class="installer-header">
            <div class="logo-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                    <polyline points="2 17 12 22 22 17"/>
                    <polyline points="2 12 12 17 22 12"/>
                </svg>
            </div>
            <h1><?= CMS_NAME ?></h1>
            <p>Installation Wizard</p>
        </div>

        <!-- Steps -->
        <div class="steps">
            <div class="steps-container">
                <div class="step-item <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">
                    <div class="step-circle">
                        <?php if ($step > 1): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php else: ?>
                        1
                        <?php endif; ?>
                    </div>
                    <span class="step-label">Requirements</span>
                </div>
                
                <div class="step-connector <?= $step > 1 ? 'done' : '' ?>"></div>
                
                <div class="step-item <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">
                    <div class="step-circle">
                        <?php if ($step > 2): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?php else: ?>
                        2
                        <?php endif; ?>
                    </div>
                    <span class="step-label">Configuration</span>
                </div>
                
                <div class="step-connector <?= $step > 2 ? 'done' : '' ?>"></div>
                
                <div class="step-item <?= $step >= 3 ? 'active' : '' ?>">
                    <div class="step-circle">3</div>
                    <span class="step-label">Complete</span>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="installer-content">
            <div class="installer-card">

                <?php if ($step === 1): ?>
                <!-- Step 1: Requirements -->
                <div class="card">
                    <div class="card-header">
                        <h2>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            System Requirements
                        </h2>
                    </div>
                    <div class="card-body">
                        <ul class="req-list">
                            <?php foreach ($requirements as $key => $req): ?>
                            <li class="req-item">
                                <div class="req-info">
                                    <div class="req-icon">
                                        <?php if ($req['icon'] === 'code'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                        <?php elseif ($req['icon'] === 'database'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                                        <?php elseif ($req['icon'] === 'braces'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H7a2 2 0 0 0-2 2v5a2 2 0 0 1-2 2 2 2 0 0 1 2 2v5c0 1.1.9 2 2 2h1"/><path d="M16 21h1a2 2 0 0 0 2-2v-5c0-1.1.9-2 2-2a2 2 0 0 1-2-2V5a2 2 0 0 0-2-2h-1"/></svg>
                                        <?php elseif ($req['icon'] === 'image'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                        <?php elseif ($req['icon'] === 'folder'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="req-name"><?= $req['name'] ?></div>
                                        <div class="req-value"><?= $req['current'] ?></div>
                                    </div>
                                </div>
                                <div class="req-status <?= $req['pass'] ? 'pass' : (!empty($req['optional']) ? 'warn' : 'fail') ?>">
                                    <?php if ($req['pass']): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Pass
                                    <?php elseif (!empty($req['optional'])): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        Optional
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Failed
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="btn-row">
                    <a href="?step=2" class="btn btn-primary" <?= !$canProceed ? 'style="pointer-events:none;opacity:0.5;"' : '' ?>>
                        Continue
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                <?php elseif ($step === 2): ?>
                <!-- Step 2: Configuration -->
                
                <?php if ($error): ?>
                <div class="alert error">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                                Database Settings
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Host <span class="req">*</span></label>
                                    <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" placeholder="localhost" required>
                                </div>
                                <div class="form-group">
                                    <label>Database Name <span class="req">*</span></label>
                                    <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="voidforge_cms" required>
                                </div>
                                <div class="form-group">
                                    <label>Username <span class="req">*</span></label>
                                    <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" placeholder="root" required>
                                </div>
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="db_pass" value="" placeholder="••••••••">
                                </div>
                                <div class="form-group full">
                                    <label>Table Prefix</label>
                                    <input type="text" name="db_prefix" value="<?= htmlspecialchars($_POST['db_prefix'] ?? 'vf_') ?>" placeholder="vf_">
                                    <p class="form-hint">Useful if installing multiple copies in one database</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                Site Settings
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group full">
                                    <label>Site URL <span class="req">*</span></label>
                                    <input type="url" name="site_url" value="<?= htmlspecialchars($_POST['site_url'] ?? $detectedUrl) ?>" placeholder="https://example.com" required>
                                </div>
                                <div class="form-group full">
                                    <label>Site Title <span class="req">*</span></label>
                                    <input type="text" name="site_title" value="<?= htmlspecialchars($_POST['site_title'] ?? 'My Website') ?>" placeholder="My Awesome Website" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Admin Account
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Username <span class="req">*</span></label>
                                    <input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>" placeholder="admin" required>
                                </div>
                                <div class="form-group">
                                    <label>Email <span class="req">*</span></label>
                                    <input type="email" name="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" placeholder="admin@example.com" required>
                                </div>
                                <div class="form-group">
                                    <label>Password <span class="req">*</span></label>
                                    <input type="password" name="admin_pass" placeholder="••••••••" required>
                                    <p class="form-hint">Minimum 8 characters</p>
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password <span class="req">*</span></label>
                                    <input type="password" name="admin_pass_confirm" placeholder="••••••••" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <a href="?step=1" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                            Back
                        </a>
                        <button type="submit" name="install" class="btn btn-primary">
                            Install Now
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    </div>
                </form>

                <?php elseif ($step === 3): ?>
                <!-- Step 3: Success -->
                <div class="card">
                    <div class="success-page">
                        <div class="success-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <h2>Installation Complete!</h2>
                        <p>VoidForge CMS has been successfully installed. You can now log in to your admin panel and start creating content.</p>
                        <div class="success-actions">
                            <a href="admin/" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                                Go to Admin
                            </a>
                            <a href="./" class="btn btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                View Site
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Footer -->
        <div class="installer-footer">
            <p><?= CMS_NAME ?> v<?= CMS_VERSION ?> — Built with ❤️</p>
        </div>
    </div>
</body>
</html>
