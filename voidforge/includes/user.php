<?php
/**
 * User Authentication and Management
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class User
{
    // Higher number = more permissions
    public const ROLES = [
        'subscriber' => 1,
        'author'     => 2,
        'editor'     => 3,
        'admin'      => 4,
    ];

    public const ROLE_LABELS = [
        'subscriber' => 'Subscriber',
        'author'     => 'Author',
        'editor'     => 'Editor',
        'admin'      => 'Administrator',
    ];

    private static $currentUser = null;

    /**
     * Start the CMS session if one is not already active.
     * Must be called before any session reads or writes.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }

    /**
     * Return the currently logged-in user row, or null if not authenticated.
     * Result is cached in-memory after the first database lookup.
     *
     * @return array{id:int,username:string,email:string,role:string,...}|null
     */
    public static function current(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        self::$currentUser = self::find($_SESSION['user_id']);
        return self::$currentUser;
    }

    public static function isLoggedIn(): bool
    {
        return self::current() !== null;
    }

    /**
     * Redirect to the login page if the visitor is not authenticated.
     * Call at the top of any admin page that requires a logged-in user.
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            redirect(ADMIN_URL . '/login.php');
        }
    }

    /**
     * Abort with 403 unless the current user has $role or higher.
     * Also redirects to login if the visitor is not authenticated at all.
     *
     * @param string $role Minimum role: subscriber | author | editor | admin
     */
    public static function requireRole(string $role): void
    {
        self::requireLogin();

        if (!self::hasRole($role)) {
            http_response_code(403);
            die('Access denied: insufficient permissions');
        }
    }

    /**
     * Return true if the current user's role is equal to or higher than $role.
     *
     * @param string $role Role to check against: subscriber | author | editor | admin
     */
    public static function hasRole(string $role): bool
    {
        $user = self::current();
        if (!$user) return false;

        return (self::ROLES[$user['role']] ?? 0) >= (self::ROLES[$role] ?? 0);
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Attempt to authenticate a user by username (or email) and password.
     * On success, writes user_id to the session and updates last_login.
     * Fires: pre_user_login, user_logged_in, user_login_failed
     * Filter: authenticate — return true/false to override default password check.
     *
     * @param string $username Username or email address
     * @param string $password Plain-text password
     * @return bool True on success, false on bad credentials
     */
    public static function login(string $username, string $password): bool
    {
        safe_do_action('pre_user_login', $username);

        $table = Database::table('users');
        $user  = Database::queryOne(
            "SELECT * FROM {$table} WHERE username = ? OR email = ?",
            [$username, $username]
        );

        // Plugins can short-circuit authentication via the 'authenticate' filter
        $authenticated = safe_apply_filters('authenticate', null, $username, $password, $user);

        if ($authenticated === null) {
            $authenticated = $user && password_verify($password, $user['password']);
        }

        if (!$authenticated || !$user) {
            safe_do_action('user_login_failed', $username);
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        self::$currentUser   = $user;

        Database::update(Database::table('users'), ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        safe_do_action('user_logged_in', $user['id'], $user);

        return true;
    }

    /**
     * Destroy the current session and clear the authentication cookie.
     * Fires: user_logged_out
     */
    public static function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $user   = self::$currentUser;

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
        self::$currentUser = null;

        if ($userId) {
            safe_do_action('user_logged_out', $userId, $user);
        }
    }

    /**
     * Find a user by primary key.
     *
     * @return array{id:int,username:string,email:string,role:string,...}|null
     */
    public static function find(int $id)
    {
        $table = Database::table('users');
        return Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }

    /**
     * Find a user by email address. Returns null if not found.
     *
     * @return array|null
     */
    public static function findByEmail(string $email)
    {
        $table = Database::table('users');
        return Database::queryOne("SELECT * FROM {$table} WHERE email = ?", [$email]);
    }

    /**
     * Find a user by username. Returns null if not found.
     *
     * @return array|null
     */
    public static function findByUsername(string $username)
    {
        $table = Database::table('users');
        return Database::queryOne("SELECT * FROM {$table} WHERE username = ?", [$username]);
    }

    public static function all(): array
    {
        $table = Database::table('users');
        return Database::query("SELECT * FROM {$table} ORDER BY created_at DESC");
    }

    /**
     * Insert a new user and return the new ID.
     * Password is hashed automatically — pass plain-text.
     * Fires: user_inserted. Filter: pre_insert_user.
     *
     * @param array{username:string,email:string,password:string,role?:string,display_name?:string} $data
     * @return int New user ID
     */
    public static function create(array $data): int
    {
        $data = safe_apply_filters('pre_insert_user', $data);

        $id = Database::insert(Database::table('users'), [
            'username'     => $data['username'],
            'email'        => $data['email'],
            'password'     => password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => HASH_COST]),
            'display_name' => $data['display_name'] ?? $data['username'],
            'role'         => $data['role'] ?? 'subscriber',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        safe_do_action('user_inserted', $id, $data);
        return $id;
    }

    /**
     * Update one or more fields on an existing user.
     * Only keys present in $data are updated; omit a key to leave it unchanged.
     * If 'password' is included it is hashed automatically.
     * Fires: user_updated. Fires user_role_changed when the role changes.
     *
     * @param array{username?:string,email?:string,password?:string,role?:string,display_name?:string} $data
     * @return bool False if user not found or no fields to update
     */
    public static function update(int $id, array $data): bool
    {
        $user = self::find($id);
        if (!$user) return false;

        $oldRole = $user['role'];
        $data    = safe_apply_filters('pre_update_user', $data, $id, $user);

        $fields = [];
        foreach (['username', 'email', 'display_name', 'role'] as $key) {
            if (isset($data[$key])) $fields[$key] = $data[$key];
        }
        if (!empty($data['password'])) {
            $fields['password'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => HASH_COST]);
        }

        if (empty($fields)) return false;

        $result = Database::update(Database::table('users'), $fields, 'id = ?', [$id]) > 0;

        if ($result) {
            safe_do_action('user_updated', $id, $fields, $user);
            if (isset($fields['role']) && $oldRole !== $fields['role']) {
                safe_do_action('user_role_changed', $id, $fields['role'], $oldRole, $user);
            }
        }

        return $result;
    }

    /**
     * Permanently delete a user. Returns false if the user is the last admin.
     * Fires: pre_delete_user, user_deleted.
     *
     * @return bool False if user not found or deletion is blocked
     */
    public static function delete(int $id): bool
    {
        $user = self::find($id);
        if (!$user) return false;

        // Never delete the last admin
        if ($user['role'] === 'admin') {
            $table      = Database::table('users');
            $adminCount = Database::queryValue("SELECT COUNT(*) FROM {$table} WHERE role = 'admin'");
            if ($adminCount <= 1) return false;
        }

        safe_do_action('pre_delete_user', $id, $user);
        $result = Database::delete(Database::table('users'), 'id = ?', [$id]) > 0;

        if ($result) safe_do_action('user_deleted', $id, $user);
        return $result;
    }

    /**
     * Validate user data and return any field errors.
     * Pass $excludeId when updating an existing user so uniqueness checks
     * ignore that user's own current username/email.
     *
     * @param array{username?:string,email?:string,password?:string,role?:string} $data
     * @param int|null $excludeId User ID to exclude from uniqueness checks (use for updates)
     * @return array<string,string> Map of field name → error message; empty on success
     */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (($u = self::findByUsername($data['username'])) && $u['id'] !== $excludeId) {
            $errors['username'] = 'Username already exists';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        } elseif (($u = self::findByEmail($data['email'])) && $u['id'] !== $excludeId) {
            $errors['email'] = 'Email already exists';
        }

        if ($excludeId === null && empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        if (!empty($data['role']) && !isset(self::ROLES[$data['role']])) {
            $errors['role'] = 'Invalid role';
        }

        return $errors;
    }

    public static function getRoleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst($role);
    }
}
