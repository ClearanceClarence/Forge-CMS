<?php

defined('CMS_ROOT') or die('Direct access not allowed');

class Database
{
    /** @var PDO|null */
    private static $instance = null;

    public static function isConfigured(): bool
    {
        return defined('DB_NAME') && !empty(DB_NAME) && defined('DB_HOST') && !empty(DB_HOST);
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
                if (!self::isConfigured()) {
                self::showSetupPage();
            }
            
            try {
                $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . $charset;
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                self::showErrorPage('Database Connection Failed', $e->getMessage());
            }
        }
        
        return self::$instance;
    }

    private static function showSetupPage(): void
    {
        // Calculate installer URL
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $installUrl = dirname($scriptPath);
        $installUrl = ($installUrl === '/' || $installUrl === '\\') ? '/install.php' : $installUrl . '/install.php';
        // If we're in admin or a subdirectory, go up
        if (strpos($scriptPath, '/admin/') !== false) {
            $installUrl = dirname(dirname($scriptPath)) . '/install.php';
        }
        $installUrl = str_replace('//', '/', $installUrl);
        
        http_response_code(503);
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to VoidForge CMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            padding: 20px;
        }
        .container {
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
        }
        .logo svg {
            width: 48px;
            height: 48px;
            color: white;
        }
        .card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        .subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 32px;
        }
        .status {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 12px;
            margin-bottom: 32px;
            text-align: left;
        }
        .status-icon {
            width: 40px;
            height: 40px;
            background: rgba(251, 191, 36, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .status-icon svg {
            width: 20px;
            height: 20px;
            color: #fbbf24;
        }
        .status-text {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.5;
        }
        .status-text strong {
            color: #fbbf24;
            font-weight: 600;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 32px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
        }
        .btn svg {
            width: 20px;
            height: 20px;
        }
        .footer {
            margin-top: 32px;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.4);
        }
        .footer a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
        }
        .footer a:hover {
            color: #8b5cf6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                <polyline points="2 17 12 22 22 17"/>
                <polyline points="2 12 12 17 22 12"/>
            </svg>
        </div>
        <div class="card">
            <h1>Welcome to VoidForge</h1>
            <p class="subtitle">Your CMS is almost ready to go</p>
            
            <div class="status">
                <div class="status-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div class="status-text">
                    <strong>Setup Required</strong><br>
                    Database connection has not been configured yet.
                </div>
            </div>
            
            <a href="' . htmlspecialchars($installUrl) . '" class="btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                Run Installer
            </a>
        </div>
        <p class="footer">
            VoidForge CMS &bull; <a href="https://github.com/voidforge/cms" target="_blank">Documentation</a>
        </p>
    </div>
</body>
</html>';
        exit;
    }

    private static function showErrorPage(string $title, string $message): void
    {
        // Calculate installer URL
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $installUrl = dirname($scriptPath);
        $installUrl = ($installUrl === '/' || $installUrl === '\\') ? '/install.php' : $installUrl . '/install.php';
        if (strpos($scriptPath, '/admin/') !== false) {
            $installUrl = dirname(dirname($scriptPath)) . '/install.php';
        }
        $installUrl = str_replace('//', '/', $installUrl);
        
        http_response_code(500);
        $safeTitle = htmlspecialchars($title);
        $safeMessage = htmlspecialchars($message);
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $safeTitle . ' - VoidForge CMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            padding: 20px;
        }
        .container {
            max-width: 520px;
            width: 100%;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 40px rgba(239, 68, 68, 0.3);
        }
        .icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }
        .card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
        }
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 32px;
            text-align: left;
        }
        .error-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ef4444;
            margin-bottom: 8px;
        }
        .error-message {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            word-break: break-word;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, monospace;
        }
        .help-text {
            font-size: 0.9375rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }
        .btn svg {
            width: 18px;
            height: 18px;
        }
        .footer {
            margin-top: 32px;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        <div class="card">
            <h1>' . $safeTitle . '</h1>
            
            <div class="error-box">
                <div class="error-label">Error Details</div>
                <div class="error-message">' . $safeMessage . '</div>
            </div>
            
            <p class="help-text">
                Please check your database credentials in the configuration file or contact your system administrator.
            </p>
            
            <a href="' . htmlspecialchars($installUrl) . '" class="btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                Run Installer
            </a>
        </div>
        <p class="footer">VoidForge CMS</p>
    </div>
</body>
</html>';
        exit;
    }

    /**
     * Get prefixed table name
     * For backward compatibility, defaults to empty prefix if DB_PREFIX not defined
     */
    public static function table(string $name): string
    {
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : '';
        return $prefix . $name;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return single row
     * @return array|null
     */
    public static function queryOne(string $sql, array $params = [])
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a query and return single value
     * @return mixed
     */
    public static function queryValue(string $sql, array $params = [])
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int) self::getInstance()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $whereParams));
        
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
}
