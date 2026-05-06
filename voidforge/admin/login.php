<?php
/**
 * Admin Login - VoidForge CMS
 * Uses customizable settings from Login Editor
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';

// Check if CMS is installed
if (!defined('DB_NAME') || DB_NAME === '' || !defined('DB_HOST') || DB_HOST === '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Install Required - VoidForge CMS</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f1f5f9;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .box { background: #fff; border-radius: 12px; padding: 2rem; text-align: center; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            h1 { font-size: 1.25rem; color: #1e293b; margin-bottom: 0.5rem; }
            p { color: #64748b; margin-bottom: 1.5rem; }
            .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #6366f1; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 500; }
            .btn:hover { background: #4f46e5; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>Installation Required</h1>
            <p>VoidForge CMS is not installed yet. Please run the installer first.</p>
            <a href="../install.php" class="btn">Go to Installer</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';

User::startSession();

if (User::isLoggedIn()) {
    redirect(ADMIN_URL . '/');
}

$error = '';

// Rate limiting — track failed attempts in session
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 300; // 5 minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCsrf()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        // Rate limit check
        $attempts    = (int) ($_SESSION['login_attempts'] ?? 0);
        $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

        if ($lockedUntil > time()) {
            $remaining = ceil(($lockedUntil - time()) / 60);
            $error = 'Too many failed attempts. Please wait ' . $remaining . ' minute(s) before trying again.';
        } else {
            // Reset stale lockout
            if ($lockedUntil > 0) {
                $_SESSION['login_attempts']    = 0;
                $_SESSION['login_locked_until'] = 0;
                $attempts = 0;
            }

            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (User::login($username, $password)) {
                // Clear rate limit on success
                unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
                $redirect = $_GET['redirect'] ?? ADMIN_URL . '/';
                redirect($redirect);
            } else {
                $attempts++;
                $_SESSION['login_attempts'] = $attempts;
                if ($attempts >= LOGIN_MAX_ATTEMPTS) {
                    $_SESSION['login_locked_until'] = time() + LOGIN_LOCKOUT_SECONDS;
                    $error = 'Too many failed attempts. Please wait 5 minutes before trying again.';
                } else {
                    $remaining = LOGIN_MAX_ATTEMPTS - $attempts;
                    $error = 'Invalid username or password. ' . $remaining . ' attempt(s) remaining.';
                }
            }
        }
    }
}

$siteName = getOption('site_name', CMS_NAME);

// Get login settings - Light theme defaults
$defaults = [
    'bg_type' => 'gradient',
    'bg_color' => '#f8fafc',
    'bg_gradient_start' => '#f8fafc',
    'bg_gradient_end' => '#e2e8f0',
    'bg_gradient_angle' => 180,
    'bg_image' => '',
    'bg_overlay' => false,
    'bg_overlay_color' => 'rgba(0,0,0,0.5)',
    'bg_pattern' => 'dots',
    'bg_pattern_color' => 'rgba(0,0,0,0.08)',
    'bg_pattern_size' => 20,
    'logo_type' => 'default',
    'logo_image' => '',
    'logo_width' => 180,
    'logo_margin_bottom' => 32,
    'card_bg' => '#ffffff',
    'card_border' => '#e2e8f0',
    'card_border_width' => 1,
    'card_radius' => 16,
    'card_shadow' => true,
    'card_shadow_color' => 'rgba(0,0,0,0.15)',
    'card_backdrop_blur' => 0,
    'card_width' => 400,
    'card_padding' => 40,
    'title_text' => 'Welcome Back',
    'title_color' => '#1e293b',
    'title_size' => 28,
    'title_weight' => 700,
    'subtitle_text' => 'Sign in to continue',
    'subtitle_color' => '#64748b',
    'subtitle_size' => 14,
    'label_color' => '#475569',
    'label_size' => 13,
    'input_bg' => '#f8fafc',
    'input_border' => '#e2e8f0',
    'input_text' => '#1e293b',
    'input_placeholder' => '#94a3b8',
    'input_radius' => 10,
    'input_padding' => 12,
    'input_focus_color' => '#8b5cf6',
    'button_label' => 'Sign In',
    'button_bg_type' => 'gradient',
    'button_solid_color' => '#8b5cf6',
    'button_gradient_start' => '#8b5cf6',
    'button_gradient_end' => '#06b6d4',
    'button_gradient_angle' => 135,
    'button_text' => '#ffffff',
    'button_radius' => 10,
    'button_padding' => 14,
    'button_shadow' => true,
    'button_full_width' => true,
    'show_remember' => true,
    'show_forgot' => false,
    'show_register' => false,
    'remember_label' => 'Remember me',
    'forgot_label' => 'Forgot password?',
    'register_text' => "Don't have an account?",
    'register_label' => 'Sign up',
    'custom_css' => '',
    'footer_text' => '',
    'animation_type' => 'fade',
    'animation_duration' => 500,
];

$loginSettings = getOption('login_settings', []);

$s = array_merge($defaults, $loginSettings);

// Build styles
$bgStyle = '';
switch ($s['bg_type']) {
    case 'color':
        $bgStyle = "background: {$s['bg_color']};";
        break;
    case 'gradient':
        $bgStyle = "background: linear-gradient({$s['bg_gradient_angle']}deg, {$s['bg_gradient_start']}, {$s['bg_gradient_end']});";
        break;
    case 'image':
        $bgStyle = "background: url('{$s['bg_image']}') center/cover no-repeat;";
        break;
    case 'pattern':
        $patternColor = $s['bg_pattern_color'] ?? 'rgba(0,0,0,0.08)';
        $patternSize = (int)($s['bg_pattern_size'] ?? 20);
        switch ($s['bg_pattern']) {
            case 'dots':
                $bgStyle = "background-color: {$s['bg_color']}; background-image: radial-gradient({$patternColor} 1px, transparent 1px); background-size: {$patternSize}px {$patternSize}px;";
                break;
            case 'grid':
                $bgStyle = "background-color: {$s['bg_color']}; background-image: linear-gradient({$patternColor} 1px, transparent 1px), linear-gradient(90deg, {$patternColor} 1px, transparent 1px); background-size: {$patternSize}px {$patternSize}px;";
                break;
            case 'diagonal':
                $half = $patternSize / 2;
                $bgStyle = "background-color: {$s['bg_color']}; background-image: repeating-linear-gradient(45deg, transparent, transparent {$half}px, {$patternColor} {$half}px, {$patternColor} {$patternSize}px);";
                break;
            case 'crosses':
                $bgStyle = "background-color: {$s['bg_color']}; background-image: linear-gradient({$patternColor} 2px, transparent 2px), linear-gradient(90deg, {$patternColor} 2px, transparent 2px); background-size: {$patternSize}px {$patternSize}px; background-position: center center;";
                break;
            case 'waves':
                $half = $patternSize / 2;
                $bgStyle = "background-color: {$s['bg_color']}; background-image: repeating-linear-gradient(0deg, transparent, transparent {$half}px, {$patternColor} {$half}px, {$patternColor} " . ($half + 1) . "px);";
                break;
            default:
                $bgStyle = "background-color: {$s['bg_color']}; background-image: radial-gradient({$patternColor} 1px, transparent 1px); background-size: {$patternSize}px {$patternSize}px;";
        }
        break;
}

$cardBlur = $s['card_backdrop_blur'] > 0 ? "backdrop-filter: blur({$s['card_backdrop_blur']}px); -webkit-backdrop-filter: blur({$s['card_backdrop_blur']}px);" : '';
$cardShadow = $s['card_shadow'] ? "box-shadow: 0 25px 50px -12px {$s['card_shadow_color']};" : '';

$buttonBg = $s['button_bg_type'] === 'gradient' 
    ? "background: linear-gradient({$s['button_gradient_angle']}deg, {$s['button_gradient_start']}, {$s['button_gradient_end']});"
    : "background: {$s['button_solid_color']};";
$buttonShadow = $s['button_shadow'] ? "box-shadow: 0 4px 15px {$s['button_gradient_start']}40;" : '';
$buttonWidth = $s['button_full_width'] ? 'width: 100%;' : '';

$animClass = $s['animation_type'] !== 'none' ? 'anim-' . $s['animation_type'] : '';
$animDuration = (int)$s['animation_duration'] / 1000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= esc($siteName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= ADMIN_URL ?>/assets/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            <?= $bgStyle ?>
            overflow: hidden;
        }
        
        .login-overlay {
            position: fixed;
            inset: 0;
            background: <?= $s['bg_overlay_color'] ?>;
            z-index: 1;
        }
        
        .login-card {
            position: relative;
            z-index: 10;
            width: <?= (int)$s['card_width'] ?>px;
            max-width: 90vw;
            padding: <?= (int)$s['card_padding'] ?>px;
            background: <?= $s['card_bg'] ?>;
            border: <?= (int)$s['card_border_width'] ?>px solid <?= $s['card_border'] ?>;
            border-radius: <?= (int)$s['card_radius'] ?>px;
            <?= $cardBlur ?>
            <?= $cardShadow ?>
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: <?= (int)$s['logo_margin_bottom'] ?>px;
        }
        
        .login-logo-default {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        
        .login-logo-default span {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-logo-custom {
            max-width: <?= (int)$s['logo_width'] ?>px;
            height: auto;
        }
        
        .login-title {
            font-size: <?= (int)$s['title_size'] ?>px;
            font-weight: <?= (int)$s['title_weight'] ?>;
            color: <?= $s['title_color'] ?>;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .login-subtitle {
            font-size: <?= (int)$s['subtitle_size'] ?>px;
            color: <?= $s['subtitle_color'] ?>;
            text-align: center;
            margin-bottom: 32px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: <?= (int)$s['label_size'] ?>px;
            font-weight: 500;
            color: <?= $s['label_color'] ?>;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: <?= (int)$s['input_padding'] ?>px 16px;
            background: <?= $s['input_bg'] ?>;
            border: 1px solid <?= $s['input_border'] ?>;
            border-radius: <?= (int)$s['input_radius'] ?>px;
            color: <?= $s['input_text'] ?>;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        .form-group input::placeholder {
            color: <?= $s['input_placeholder'] ?>;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: <?= $s['input_focus_color'] ?>;
            box-shadow: 0 0 0 3px <?= $s['input_focus_color'] ?>20;
        }
        
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            font-size: 13px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: <?= $s['label_color'] ?>;
        }
        
        .remember-me input {
            width: 16px;
            height: 16px;
            accent-color: <?= $s['input_focus_color'] ?>;
        }
        
        .forgot-link {
            color: <?= $s['input_focus_color'] ?>;
            text-decoration: none;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .login-btn {
            <?= $buttonWidth ?>
            padding: <?= (int)$s['button_padding'] ?>px 24px;
            <?= $buttonBg ?>
            color: <?= $s['button_text'] ?>;
            border: none;
            border-radius: <?= (int)$s['button_radius'] ?>px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            <?= $buttonShadow ?>
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: <?= $s['subtitle_color'] ?>;
        }
        
        .login-footer a {
            color: <?= $s['input_focus_color'] ?>;
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .error-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            margin-bottom: 24px;
            color: #fca5a5;
            font-size: 14px;
        }
        
        .error-alert svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }
        
        .back-link {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 20;
            color: <?= $s['subtitle_color'] ?>;
            text-decoration: none;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: <?= $s['input_focus_color'] ?>;
        }
        
        .back-link svg {
            width: 16px;
            height: 16px;
        }
        
        /* Animations */
        .anim-fade { animation: fadeIn <?= $animDuration ?>s ease; }
        .anim-slide { animation: slideIn <?= $animDuration ?>s ease; }
        .anim-scale { animation: scaleIn <?= $animDuration ?>s ease; }
        .anim-bounce { animation: bounceIn <?= $animDuration ?>s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes bounceIn {
            from { opacity: 0; transform: scale(0.3); }
            to { opacity: 1; transform: scale(1); }
        }
        
        /* Custom CSS from editor */
        <?= $s['custom_css'] ?? '' ?>
    </style>
</head>
<body>
    <?php if ($s['bg_overlay']): ?>
    <div class="login-overlay"></div>
    <?php endif; ?>
    
    <div class="login-card <?= $animClass ?>">
        <?php if ($s['logo_type'] === 'default'): ?>
        <div class="login-logo">
            <div class="login-logo-default">
                <svg width="48" height="48" viewBox="0 0 32 32" fill="none">
                    <defs>
                        <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#8b5cf6"/>
                            <stop offset="100%" style="stop-color:#06b6d4"/>
                        </linearGradient>
                    </defs>
                    <path d="M5 5 L16 27 L27 5" fill="none" stroke="url(#logoGrad)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="16" cy="14" r="3.5" fill="#c4b5fd"/>
                </svg>
                <span>VoidForge</span>
            </div>
        </div>
        <?php elseif ($s['logo_type'] === 'custom' && $s['logo_image']): ?>
        <div class="login-logo">
            <img src="<?= esc($s['logo_image']) ?>" class="login-logo-custom" alt="Logo">
        </div>
        <?php endif; ?>
        
        <h1 class="login-title"><?= esc($s['title_text']) ?></h1>
        <?php if ($s['subtitle_text']): ?>
        <p class="login-subtitle"><?= esc($s['subtitle_text']) ?></p>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error-alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span><?= esc($error) ?></span>
        </div>
        <?php endif; ?>
        
        <form method="post">
            <?= csrfField() ?>
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" placeholder="Enter your username" required autofocus value="<?= esc($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <?php if ($s['show_remember'] || $s['show_forgot']): ?>
            <div class="form-options">
                <?php if ($s['show_remember']): ?>
                <label class="remember-me">
                    <input type="checkbox" name="remember"> <?= esc($s['remember_label']) ?>
                </label>
                <?php else: ?>
                <span></span>
                <?php endif; ?>
                
                <?php if ($s['show_forgot']): ?>
                <a href="#" class="forgot-link"><?= esc($s['forgot_label']) ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="login-btn"><?= esc($s['button_label']) ?></button>
            
            <?php if ($s['show_register']): ?>
            <div class="login-footer">
                <?= esc($s['register_text']) ?> <a href="#"><?= esc($s['register_label']) ?></a>
            </div>
            <?php endif; ?>
            
            <?php if ($s['footer_text']): ?>
            <div class="login-footer"><?= $s['footer_text'] ?></div>
            <?php endif; ?>
        </form>
    </div>
    
    <a href="<?= SITE_URL ?>" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
        </svg>
        Back to site
    </a>
</body>
</html>
