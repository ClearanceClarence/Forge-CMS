<?php
/**
 * VoidForge REST API
 * 
 * Complete REST API implementation with authentication, rate limiting,
 * and full CRUD operations for all content types.
 * 
 * @package VoidForge
 * @since 0.2.1
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class RestAPI
{
    private static bool $initialized = false;
    private static ?array $currentApiKey = null;
    private static array $rateLimits = [
        'default' => ['requests' => 1000, 'window' => 3600],  // 1000/hour
        'write' => ['requests' => 100, 'window' => 3600],     // 100/hour for POST/PUT/DELETE
        'auth' => ['requests' => 10, 'window' => 300],        // 10/5min for auth attempts
    ];

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // Ensure API keys table exists
        self::ensureTable();

        Plugin::addFilter('rest_authentication_errors', [self::class, 'authenticateRequest'], 10, 2);
        
        Plugin::addFilter('rest_pre_dispatch', [self::class, 'checkRateLimit'], 10, 3);

        Plugin::addAction('rest_api_init', [self::class, 'registerRoutes']);

        self::$initialized = true;
    }

    public static function registerRoutes(): void
    {
        $namespace = 'v1';
        
        Plugin::registerRestRoute($namespace, 'posts', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getPosts'],
            'permission_callback' => null, // Public read
        ]);

        Plugin::registerRestRoute($namespace, 'posts/{id}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getPost'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'posts', [
            'methods' => ['POST'],
            'callback' => [self::class, 'createPost'],
            'permission_callback' => [self::class, 'canCreatePosts'],
        ]);

        Plugin::registerRestRoute($namespace, 'posts/{id}', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [self::class, 'updatePost'],
            'permission_callback' => [self::class, 'canEditPost'],
        ]);

        Plugin::registerRestRoute($namespace, 'posts/{id}', [
            'methods' => ['DELETE'],
            'callback' => [self::class, 'deletePost'],
            'permission_callback' => [self::class, 'canDeletePost'],
        ]);

        Plugin::registerRestRoute($namespace, 'pages', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getPages'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'pages/{id}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getPage'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'pages', [
            'methods' => ['POST'],
            'callback' => [self::class, 'createPage'],
            'permission_callback' => [self::class, 'canCreatePages'],
        ]);

        Plugin::registerRestRoute($namespace, 'pages/{id}', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [self::class, 'updatePage'],
            'permission_callback' => [self::class, 'canEditPage'],
        ]);

        Plugin::registerRestRoute($namespace, 'pages/{id}', [
            'methods' => ['DELETE'],
            'callback' => [self::class, 'deletePage'],
            'permission_callback' => [self::class, 'canDeletePage'],
        ]);

        Plugin::registerRestRoute($namespace, 'types', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getPostTypes'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'types/{type}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getPostType'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'types/{type}/posts', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getPostsByType'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'types/{type}/posts', [
            'methods' => ['POST'],
            'callback' => [self::class, 'createPostByType'],
            'permission_callback' => [self::class, 'canCreatePosts'],
        ]);

        Plugin::registerRestRoute($namespace, 'media', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getMedia'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'media/{id}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getMediaItem'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'media', [
            'methods' => ['POST'],
            'callback' => [self::class, 'uploadMedia'],
            'permission_callback' => [self::class, 'canUploadMedia'],
        ]);

        Plugin::registerRestRoute($namespace, 'media/{id}', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [self::class, 'updateMedia'],
            'permission_callback' => [self::class, 'canEditMedia'],
        ]);

        Plugin::registerRestRoute($namespace, 'media/{id}', [
            'methods' => ['DELETE'],
            'callback' => [self::class, 'deleteMedia'],
            'permission_callback' => [self::class, 'canDeleteMedia'],
        ]);

        Plugin::registerRestRoute($namespace, 'users', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getUsers'],
            'permission_callback' => [self::class, 'canListUsers'],
        ]);

        Plugin::registerRestRoute($namespace, 'users/{id}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getUser'],
            'permission_callback' => [self::class, 'canViewUser'],
        ]);

        Plugin::registerRestRoute($namespace, 'users/me', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getCurrentUser'],
            'permission_callback' => [self::class, 'isAuthenticated'],
        ]);

        Plugin::registerRestRoute($namespace, 'users', [
            'methods' => ['POST'],
            'callback' => [self::class, 'createUser'],
            'permission_callback' => [self::class, 'canCreateUsers'],
        ]);

        Plugin::registerRestRoute($namespace, 'users/{id}', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [self::class, 'updateUser'],
            'permission_callback' => [self::class, 'canEditUser'],
        ]);

        Plugin::registerRestRoute($namespace, 'users/{id}', [
            'methods' => ['DELETE'],
            'callback' => [self::class, 'deleteUser'],
            'permission_callback' => [self::class, 'canDeleteUser'],
        ]);

        Plugin::registerRestRoute($namespace, 'comments', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getComments'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'comments/{id}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getComment'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'comments', [
            'methods' => ['POST'],
            'callback' => [self::class, 'createComment'],
            'permission_callback' => null, // Public can comment
        ]);

        Plugin::registerRestRoute($namespace, 'comments/{id}', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [self::class, 'updateComment'],
            'permission_callback' => [self::class, 'canModerateComments'],
        ]);

        Plugin::registerRestRoute($namespace, 'comments/{id}', [
            'methods' => ['DELETE'],
            'callback' => [self::class, 'deleteComment'],
            'permission_callback' => [self::class, 'canModerateComments'],
        ]);

        Plugin::registerRestRoute($namespace, 'taxonomies', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getTaxonomies'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'taxonomies/{taxonomy}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getTaxonomy'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'taxonomies/{taxonomy}/terms', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getTerms'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'taxonomies/{taxonomy}/terms/{id}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getTerm'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'taxonomies/{taxonomy}/terms', [
            'methods' => ['POST'],
            'callback' => [self::class, 'createTerm'],
            'permission_callback' => [self::class, 'canManageTaxonomies'],
        ]);

        Plugin::registerRestRoute($namespace, 'taxonomies/{taxonomy}/terms/{id}', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [self::class, 'updateTerm'],
            'permission_callback' => [self::class, 'canManageTaxonomies'],
        ]);

        Plugin::registerRestRoute($namespace, 'taxonomies/{taxonomy}/terms/{id}', [
            'methods' => ['DELETE'],
            'callback' => [self::class, 'deleteTerm'],
            'permission_callback' => [self::class, 'canManageTaxonomies'],
        ]);

        Plugin::registerRestRoute($namespace, 'menus', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getMenus'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'menus/{id}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getMenu'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'menus/location/{location}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getMenuByLocation'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'menus', [
            'methods' => ['POST'],
            'callback' => [self::class, 'createMenu'],
            'permission_callback' => [self::class, 'canManageMenus'],
        ]);

        Plugin::registerRestRoute($namespace, 'menus/{id}', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [self::class, 'updateMenu'],
            'permission_callback' => [self::class, 'canManageMenus'],
        ]);

        Plugin::registerRestRoute($namespace, 'menus/{id}', [
            'methods' => ['DELETE'],
            'callback' => [self::class, 'deleteMenu'],
            'permission_callback' => [self::class, 'canManageMenus'],
        ]);

        Plugin::registerRestRoute($namespace, 'settings', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getSettings'],
            'permission_callback' => [self::class, 'isAdmin'],
        ]);

        Plugin::registerRestRoute($namespace, 'settings/{key}', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getSetting'],
            'permission_callback' => [self::class, 'isAdmin'],
        ]);

        Plugin::registerRestRoute($namespace, 'settings/{key}', [
            'methods' => ['PUT', 'PATCH'],
            'callback' => [self::class, 'updateSetting'],
            'permission_callback' => [self::class, 'isAdmin'],
        ]);

        Plugin::registerRestRoute($namespace, 'search', [
            'methods' => ['GET'],
            'callback' => [self::class, 'search'],
            'permission_callback' => null,
        ]);

        Plugin::registerRestRoute($namespace, 'info', [
            'methods' => ['GET'],
            'callback' => [self::class, 'getInfo'],
            'permission_callback' => null,
        ]);

        // Allow plugins to register additional routes
        Plugin::doAction('rest_api_register_routes', $namespace);
    }

    public static function authenticateRequest(?string $error, string $path): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            // No auth header - check if route requires auth
            return null; // Let permission_callback handle it
        }

        // Parse Bearer token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return 'Invalid authorization header format';
        }

        $token = $matches[1];
        $apiKey = self::validateApiKey($token);

        if (!$apiKey) {
            return 'Invalid API key';
        }

        if (!$apiKey['is_active']) {
            return 'API key is disabled';
        }

        if ($apiKey['expires_at'] && strtotime($apiKey['expires_at']) < time()) {
            return 'API key has expired';
        }

        // Store current API key for permission checks
        self::$currentApiKey = $apiKey;

        // Update last used timestamp
        self::updateApiKeyLastUsed($apiKey['id']);

        return null; // Authentication successful
    }

    public static function getCurrentApiKey(): ?array
    {
        return self::$currentApiKey;
    }

    public static function getCurrentUserId(): ?int
    {
        if (self::$currentApiKey) {
            return (int)self::$currentApiKey['user_id'];
        }
        
        $user = User::current();
        return $user ? (int)$user['id'] : null;
    }

    public static function checkRateLimit(?array $error, string $path, array $config): ?array
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $limitType = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) ? 'write' : 'default';
        
        $identifier = self::getRateLimitIdentifier();
        $limits = self::$rateLimits[$limitType];

        $cacheKey = "rate_limit:{$limitType}:{$identifier}";
        $current = self::getRateLimitCount($cacheKey);

        if ($current >= $limits['requests']) {
            header('X-RateLimit-Limit: ' . $limits['requests']);
            header('X-RateLimit-Remaining: 0');
            header('Retry-After: ' . $limits['window']);
            return ['message' => 'Rate limit exceeded', 'code' => 429];
        }

        self::incrementRateLimit($cacheKey, $limits['window']);

        header('X-RateLimit-Limit: ' . $limits['requests']);
        header('X-RateLimit-Remaining: ' . max(0, $limits['requests'] - $current - 1));

        return null;
    }

    private static function getRateLimitIdentifier(): string
    {
        if (self::$currentApiKey) {
            return 'key:' . self::$currentApiKey['id'];
        }
        return 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private static function getRateLimitCount(string $key): int
    {
        $table = Database::table('api_rate_limits');
        $result = Database::queryOne(
            "SELECT request_count FROM {$table} WHERE cache_key = ? AND expires_at > NOW()",
            [$key]
        );
        return $result ? (int)$result['request_count'] : 0;
    }

    private static function incrementRateLimit(string $key, int $window): void
    {
        $table = Database::table('api_rate_limits');
        $expires = date('Y-m-d H:i:s', time() + $window);
        
        Database::execute(
            "INSERT INTO {$table} (cache_key, request_count, expires_at) VALUES (?, 1, ?)
             ON DUPLICATE KEY UPDATE request_count = request_count + 1",
            [$key, $expires]
        );
    }

    public static function isAuthenticated(): bool
    {
        return self::getCurrentUserId() !== null;
    }

    public static function isAdmin(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        return $user && $user['role'] === 'admin';
    }

    public static function canCreatePosts(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        return $user && in_array($user['role'], ['admin', 'editor', 'author']);
    }

    public static function canEditPost(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        if (!$user) return false;
        
        // Admins and editors can edit any post
        if (in_array($user['role'], ['admin', 'editor'])) {
            return true;
        }
        
        // Authors can only edit their own posts
        if ($user['role'] === 'author') {
            $postId = self::getRouteParam('id');
            $post = Post::find($postId);
            return $post && (int)$post['author_id'] === $userId;
        }
        
        return false;
    }

    public static function canDeletePost(): bool
    {
        return self::canEditPost();
    }

    public static function canCreatePages(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        return $user && in_array($user['role'], ['admin', 'editor']);
    }

    public static function canEditPage(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        return $user && in_array($user['role'], ['admin', 'editor']);
    }

    public static function canDeletePage(): bool
    {
        return self::canEditPage();
    }

    public static function canUploadMedia(): bool
    {
        return self::canCreatePosts();
    }

    public static function canEditMedia(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        if (!$user) return false;
        
        if (in_array($user['role'], ['admin', 'editor'])) {
            return true;
        }
        
        // Authors can edit their own uploads
        if ($user['role'] === 'author') {
            $mediaId = self::getRouteParam('id');
            $media = Media::get($mediaId);
            return $media && (int)$media['uploaded_by'] === $userId;
        }
        
        return false;
    }

    public static function canDeleteMedia(): bool
    {
        return self::canEditMedia();
    }

    public static function canListUsers(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        return $user && in_array($user['role'], ['admin', 'editor']);
    }

    public static function canViewUser(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        if (!$user) return false;
        
        // Can view self
        $targetId = self::getRouteParam('id');
        if ((int)$targetId === $userId) {
            return true;
        }
        
        return in_array($user['role'], ['admin', 'editor']);
    }

    public static function canCreateUsers(): bool
    {
        return self::isAdmin();
    }

    public static function canEditUser(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        if (!$user) return false;
        
        // Can edit self
        $targetId = self::getRouteParam('id');
        if ((int)$targetId === $userId) {
            return true;
        }
        
        return $user['role'] === 'admin';
    }

    public static function canDeleteUser(): bool
    {
        return self::isAdmin();
    }

    public static function canModerateComments(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        return $user && in_array($user['role'], ['admin', 'editor']);
    }

    public static function canManageTaxonomies(): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) return false;
        
        $user = User::find($userId);
        return $user && in_array($user['role'], ['admin', 'editor']);
    }

    public static function canManageMenus(): bool
    {
        return self::isAdmin();
    }

    private static function getRouteParam(string $name): ?string
    {
        // Extract from current path
        $path = trim($_SERVER['REQUEST_URI'] ?? '', '/');
        $path = preg_replace('#^api/#', '', $path);
        
        // Very basic extraction - the actual param is passed to callbacks
        $parts = explode('/', $path);
        
        // For routes like /posts/{id}, id is usually the last part
        if ($name === 'id' && count($parts) >= 2) {
            return end($parts);
        }
        
        return null;
    }

    public static function getPosts(array $params): array
    {
        $args = self::parseQueryArgs([
            'status' => 'published',
            'post_type' => 'post',
        ]);

        $posts = Post::query($args);
        $total = Post::count($args);

        return [
            'posts' => array_map([self::class, 'preparePost'], $posts),
            'total' => $total,
            'page' => $args['page'] ?? 1,
            'per_page' => $args['limit'] ?? 10,
            'total_pages' => ceil($total / ($args['limit'] ?? 10)),
        ];
    }

    public static function getPost(array $params): array
    {
        $post = Post::find($params['id']);
        
        if (!$post) {
            throw new Exception('Post not found', 404);
        }

        // Only show published posts to non-authenticated users
        if ($post['status'] !== 'published' && !self::isAuthenticated()) {
            throw new Exception('Post not found', 404);
        }

        return self::preparePost($post, true);
    }

    public static function createPost(array $params): array
    {
        $data = self::getRequestBody();
        $data['post_type'] = 'post';
        $data['author_id'] = self::getCurrentUserId();

        $errors = Post::validate($data);
        if ($errors) {
            throw new Exception(implode(', ', $errors), 400);
        }

        $id = Post::create($data);
        $post = Post::find($id);

        return self::preparePost($post, true);
    }

    public static function updatePost(array $params): array
    {
        $post = Post::find($params['id']);
        
        if (!$post) {
            throw new Exception('Post not found', 404);
        }

        $data = self::getRequestBody();
        unset($data['id'], $data['author_id']); // Can't change these

        $errors = Post::validate(array_merge($post, $data), $params['id']);
        if ($errors) {
            throw new Exception(implode(', ', $errors), 400);
        }

        Post::update($params['id'], $data);
        $post = Post::find($params['id']);

        return self::preparePost($post, true);
    }

    public static function deletePost(array $params): array
    {
        $post = Post::find($params['id']);
        
        if (!$post) {
            throw new Exception('Post not found', 404);
        }

        $permanent = ($_GET['force'] ?? '') === 'true';
        Post::delete($params['id'], $permanent);

        return ['deleted' => true, 'id' => (int)$params['id']];
    }

    public static function getPages(array $params): array
    {
        $args = self::parseQueryArgs([
            'status' => 'published',
            'post_type' => 'page',
        ]);

        $pages = Post::query($args);
        $total = Post::count($args);

        return [
            'pages' => array_map([self::class, 'preparePost'], $pages),
            'total' => $total,
            'page' => $args['page'] ?? 1,
            'per_page' => $args['limit'] ?? 10,
            'total_pages' => ceil($total / ($args['limit'] ?? 10)),
        ];
    }

    public static function getPage(array $params): array
    {
        $page = Post::find($params['id']);
        
        if (!$page || $page['post_type'] !== 'page') {
            throw new Exception('Page not found', 404);
        }

        if ($page['status'] !== 'published' && !self::isAuthenticated()) {
            throw new Exception('Page not found', 404);
        }

        return self::preparePost($page, true);
    }

    public static function createPage(array $params): array
    {
        $data = self::getRequestBody();
        $data['post_type'] = 'page';
        $data['author_id'] = self::getCurrentUserId();

        $errors = Post::validate($data);
        if ($errors) {
            throw new Exception(implode(', ', $errors), 400);
        }

        $id = Post::create($data);
        $page = Post::find($id);

        return self::preparePost($page, true);
    }

    public static function updatePage(array $params): array
    {
        $page = Post::find($params['id']);
        
        if (!$page || $page['post_type'] !== 'page') {
            throw new Exception('Page not found', 404);
        }

        $data = self::getRequestBody();
        unset($data['id'], $data['author_id']);

        Post::update($params['id'], $data);
        $page = Post::find($params['id']);

        return self::preparePost($page, true);
    }

    public static function deletePage(array $params): array
    {
        $page = Post::find($params['id']);
        
        if (!$page || $page['post_type'] !== 'page') {
            throw new Exception('Page not found', 404);
        }

        $permanent = ($_GET['force'] ?? '') === 'true';
        Post::delete($params['id'], $permanent);

        return ['deleted' => true, 'id' => (int)$params['id']];
    }

    public static function getPostTypes(array $params): array
    {
        $types = Post::getTypes();
        $prepared = [];
        foreach ($types as $slug => $type) {
            $prepared[] = self::preparePostType($type, $slug);
        }
        return ['types' => $prepared];
    }

    public static function getPostType(array $params): array
    {
        $type = Post::getType($params['type']);
        
        if (!$type) {
            throw new Exception('Post type not found', 404);
        }

        return self::preparePostType($type, $params['type']);
    }

    public static function getPostsByType(array $params): array
    {
        $type = Post::getType($params['type']);
        
        if (!$type) {
            throw new Exception('Post type not found', 404);
        }

        $args = self::parseQueryArgs([
            'status' => 'published',
            'post_type' => $params['type'],
        ]);

        $posts = Post::query($args);
        $total = Post::count($args);

        return [
            'posts' => array_map([self::class, 'preparePost'], $posts),
            'total' => $total,
            'page' => $args['page'] ?? 1,
            'per_page' => $args['limit'] ?? 10,
            'total_pages' => ceil($total / ($args['limit'] ?? 10)),
        ];
    }

    public static function createPostByType(array $params): array
    {
        $type = Post::getType($params['type']);
        
        if (!$type) {
            throw new Exception('Post type not found', 404);
        }

        $data = self::getRequestBody();
        $data['post_type'] = $params['type'];
        $data['author_id'] = self::getCurrentUserId();

        $errors = Post::validate($data);
        if ($errors) {
            throw new Exception(implode(', ', $errors), 400);
        }

        $id = Post::create($data);
        $post = Post::find($id);

        return self::preparePost($post, true);
    }

    public static function getMedia(array $params): array
    {
        $args = self::parseQueryArgs([]);
        $media = Media::query($args);
        $total = Media::count($args);

        return [
            'media' => array_map([self::class, 'prepareMedia'], $media),
            'total' => $total,
            'page' => $args['page'] ?? 1,
            'per_page' => $args['limit'] ?? 10,
            'total_pages' => ceil($total / ($args['limit'] ?? 10)),
        ];
    }

    public static function getMediaItem(array $params): array
    {
        $media = Media::get($params['id']);
        
        if (!$media) {
            throw new Exception('Media not found', 404);
        }

        return self::prepareMedia($media, true);
    }

    public static function uploadMedia(array $params): array
    {
        if (empty($_FILES['file'])) {
            throw new Exception('No file uploaded', 400);
        }

        $folderId = $_POST['folder_id'] ?? null;
        $userId = self::getCurrentUserId();

        $result = Media::upload($_FILES['file'], $userId, $folderId);
        
        if (isset($result['error'])) {
            throw new Exception($result['error'], 400);
        }

        $media = Media::get($result['id']);
        return self::prepareMedia($media, true);
    }

    public static function updateMedia(array $params): array
    {
        $media = Media::get($params['id']);
        
        if (!$media) {
            throw new Exception('Media not found', 404);
        }

        $data = self::getRequestBody();
        $allowedFields = ['title', 'alt_text', 'caption', 'folder_id'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        Media::update($params['id'], $updateData);
        $media = Media::get($params['id']);

        return self::prepareMedia($media, true);
    }

    public static function deleteMedia(array $params): array
    {
        $media = Media::get($params['id']);
        
        if (!$media) {
            throw new Exception('Media not found', 404);
        }

        Media::delete($params['id']);

        return ['deleted' => true, 'id' => (int)$params['id']];
    }

    public static function getUsers(array $params): array
    {
        $args = self::parseQueryArgs([]);
        
        $table = Database::table('users');
        $limit = $args['limit'] ?? 10;
        $offset = (($args['page'] ?? 1) - 1) * $limit;

        $users = Database::query(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );

        $total = (int)Database::queryValue("SELECT COUNT(*) FROM {$table}");

        return [
            'users' => array_map([self::class, 'prepareUser'], $users),
            'total' => $total,
            'page' => $args['page'] ?? 1,
            'per_page' => $limit,
            'total_pages' => ceil($total / $limit),
        ];
    }

    public static function getUser(array $params): array
    {
        $user = User::find($params['id']);
        
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        return self::prepareUser($user, true);
    }

    public static function getCurrentUser(array $params): array
    {
        $userId = self::getCurrentUserId();
        $user = User::find($userId);
        
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        return self::prepareUser($user, true);
    }

    public static function createUser(array $params): array
    {
        $data = self::getRequestBody();

        $errors = User::validate($data);
        if ($errors) {
            throw new Exception(implode(', ', $errors), 400);
        }

        $id = User::create($data);
        $user = User::find($id);

        return self::prepareUser($user, true);
    }

    public static function updateUser(array $params): array
    {
        $user = User::find($params['id']);
        
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        $data = self::getRequestBody();
        unset($data['id']); // Can't change ID

        // Non-admins can't change roles
        if (!self::isAdmin() && isset($data['role'])) {
            unset($data['role']);
        }

        $errors = User::validate(array_merge($user, $data), $params['id']);
        if ($errors) {
            throw new Exception(implode(', ', $errors), 400);
        }

        User::update($params['id'], $data);
        $user = User::find($params['id']);

        return self::prepareUser($user, true);
    }

    public static function deleteUser(array $params): array
    {
        $user = User::find($params['id']);
        
        if (!$user) {
            throw new Exception('User not found', 404);
        }

        // Can't delete yourself
        if ((int)$params['id'] === self::getCurrentUserId()) {
            throw new Exception('Cannot delete your own account', 400);
        }

        $result = User::delete($params['id']);
        
        if (!$result) {
            throw new Exception('Cannot delete the last admin user', 400);
        }

        return ['deleted' => true, 'id' => (int)$params['id']];
    }

    public static function getComments(array $params): array
    {
        if (!Comment::tableExists()) {
            return ['comments' => [], 'total' => 0];
        }

        $args = self::parseQueryArgs([
            'status' => 'approved',
        ]);

        // Non-authenticated users can only see approved comments
        if (!self::isAuthenticated()) {
            $args['status'] = 'approved';
        }

        $comments = Comment::query($args);
        $total = Comment::count($args);

        return [
            'comments' => array_map([self::class, 'prepareComment'], $comments),
            'total' => $total,
            'page' => $args['page'] ?? 1,
            'per_page' => $args['limit'] ?? 10,
            'total_pages' => ceil($total / ($args['limit'] ?? 10)),
        ];
    }

    public static function getComment(array $params): array
    {
        if (!Comment::tableExists()) {
            throw new Exception('Comment not found', 404);
        }

        $comment = Comment::find($params['id']);
        
        if (!$comment) {
            throw new Exception('Comment not found', 404);
        }

        // Non-authenticated users can only see approved comments
        if ($comment['status'] !== 'approved' && !self::isAuthenticated()) {
            throw new Exception('Comment not found', 404);
        }

        return self::prepareComment($comment, true);
    }

    public static function createComment(array $params): array
    {
        if (!Comment::tableExists()) {
            Comment::ensureTable();
        }

        $data = self::getRequestBody();
        
        // If authenticated, use current user
        $userId = self::getCurrentUserId();
        if ($userId) {
            $data['user_id'] = $userId;
        }

        $data['author_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';

        $errors = Comment::validate($data);
        if ($errors) {
            throw new Exception(implode(', ', $errors), 400);
        }

        $post = Post::find($data['post_id'] ?? 0);
        if (!$post) {
            throw new Exception('Post not found', 404);
        }

        if (!Comment::areOpen($post)) {
            throw new Exception('Comments are closed on this post', 403);
        }

        $data['status'] = getOption('comment_moderation', true) ? 'pending' : 'approved';

        $id = Comment::create($data);
        $comment = Comment::find($id);

        return self::prepareComment($comment, true);
    }

    public static function updateComment(array $params): array
    {
        if (!Comment::tableExists()) {
            throw new Exception('Comment not found', 404);
        }

        $comment = Comment::find($params['id']);
        
        if (!$comment) {
            throw new Exception('Comment not found', 404);
        }

        $data = self::getRequestBody();
        $allowedFields = ['content', 'status', 'author_name', 'author_email', 'author_url'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        Comment::update($params['id'], $updateData);
        $comment = Comment::find($params['id']);

        return self::prepareComment($comment, true);
    }

    public static function deleteComment(array $params): array
    {
        if (!Comment::tableExists()) {
            throw new Exception('Comment not found', 404);
        }

        $comment = Comment::find($params['id']);
        
        if (!$comment) {
            throw new Exception('Comment not found', 404);
        }

        Comment::delete($params['id']);

        return ['deleted' => true, 'id' => (int)$params['id']];
    }

    public static function getTaxonomies(array $params): array
    {
        $taxonomies = Taxonomy::getAll();
        $prepared = [];
        foreach ($taxonomies as $slug => $taxonomy) {
            $prepared[] = self::prepareTaxonomy($taxonomy, $slug);
        }
        return ['taxonomies' => $prepared];
    }

    public static function getTaxonomy(array $params): array
    {
        $taxonomy = Taxonomy::get($params['taxonomy']);
        
        if (!$taxonomy) {
            throw new Exception('Taxonomy not found', 404);
        }

        return self::prepareTaxonomy($taxonomy, $params['taxonomy']);
    }

    public static function getTerms(array $params): array
    {
        $taxonomy = Taxonomy::get($params['taxonomy']);
        
        if (!$taxonomy) {
            throw new Exception('Taxonomy not found', 404);
        }

        $args = self::parseQueryArgs([]);
        $terms = Taxonomy::getTerms($params['taxonomy'], $args);

        return [
            'terms' => array_map([self::class, 'prepareTerm'], $terms),
            'taxonomy' => $params['taxonomy'],
        ];
    }

    public static function getTerm(array $params): array
    {
        $term = Taxonomy::findTerm($params['id']);
        
        if (!$term || $term['taxonomy'] !== $params['taxonomy']) {
            throw new Exception('Term not found', 404);
        }

        return self::prepareTerm($term, true);
    }

    public static function createTerm(array $params): array
    {
        $taxonomy = Taxonomy::get($params['taxonomy']);
        
        if (!$taxonomy) {
            throw new Exception('Taxonomy not found', 404);
        }

        $data = self::getRequestBody();
        $id = Taxonomy::createTerm($params['taxonomy'], $data);
        
        if (!$id) {
            throw new Exception('Failed to create term', 500);
        }

        $term = Taxonomy::findTerm($id);
        return self::prepareTerm($term, true);
    }

    public static function updateTerm(array $params): array
    {
        $term = Taxonomy::findTerm($params['id']);
        
        if (!$term || $term['taxonomy'] !== $params['taxonomy']) {
            throw new Exception('Term not found', 404);
        }

        $data = self::getRequestBody();
        Taxonomy::updateTerm($params['id'], $data);
        
        $term = Taxonomy::findTerm($params['id']);
        return self::prepareTerm($term, true);
    }

    public static function deleteTerm(array $params): array
    {
        $term = Taxonomy::findTerm($params['id']);
        
        if (!$term || $term['taxonomy'] !== $params['taxonomy']) {
            throw new Exception('Term not found', 404);
        }

        Taxonomy::deleteTerm($params['id']);

        return ['deleted' => true, 'id' => (int)$params['id']];
    }

    public static function getMenus(array $params): array
    {
        $menus = Menu::getAll();
        return ['menus' => array_map([self::class, 'prepareMenu'], $menus)];
    }

    public static function getMenu(array $params): array
    {
        $menu = Menu::find($params['id']);
        
        if (!$menu) {
            throw new Exception('Menu not found', 404);
        }

        return self::prepareMenu($menu, true);
    }

    public static function getMenuByLocation(array $params): array
    {
        $menu = Menu::getMenuByLocation($params['location']);
        
        if (!$menu) {
            throw new Exception('No menu assigned to this location', 404);
        }

        return self::prepareMenu($menu, true);
    }

    public static function createMenu(array $params): array
    {
        $data = self::getRequestBody();
        
        if (empty($data['name'])) {
            throw new Exception('Menu name is required', 400);
        }

        $id = Menu::create($data);
        $menu = Menu::find($id);

        return self::prepareMenu($menu, true);
    }

    public static function updateMenu(array $params): array
    {
        $menu = Menu::find($params['id']);
        
        if (!$menu) {
            throw new Exception('Menu not found', 404);
        }

        $data = self::getRequestBody();
        Menu::update($params['id'], $data);
        
        $menu = Menu::find($params['id']);
        return self::prepareMenu($menu, true);
    }

    public static function deleteMenu(array $params): array
    {
        $menu = Menu::find($params['id']);
        
        if (!$menu) {
            throw new Exception('Menu not found', 404);
        }

        Menu::delete($params['id']);

        return ['deleted' => true, 'id' => (int)$params['id']];
    }

    public static function getSettings(array $params): array
    {
        // Only return safe, public settings
        $publicSettings = [
            'site_name', 'site_description', 'timezone', 'date_format',
            'posts_per_page', 'comments_enabled', 'comment_moderation',
        ];

        $settings = [];
        foreach ($publicSettings as $key) {
            $settings[$key] = getOption($key);
        }

        return ['settings' => $settings];
    }

    public static function getSetting(array $params): array
    {
        $value = getOption($params['key']);
        
        return [
            'key' => $params['key'],
            'value' => $value,
        ];
    }

    public static function updateSetting(array $params): array
    {
        $data = self::getRequestBody();
        
        if (!isset($data['value'])) {
            throw new Exception('Value is required', 400);
        }

        setOption($params['key'], $data['value']);

        return [
            'key' => $params['key'],
            'value' => $data['value'],
            'updated' => true,
        ];
    }

    public static function search(array $params): array
    {
        $query = $_GET['q'] ?? $_GET['query'] ?? '';
        
        if (empty($query)) {
            throw new Exception('Search query is required', 400);
        }

        $args = self::parseQueryArgs([
            'status' => 'published',
            'search' => $query,
        ]);

        $posts = Post::query($args);
        $total = Post::count($args);

        return [
            'query' => $query,
            'results' => array_map([self::class, 'preparePost'], $posts),
            'total' => $total,
            'page' => $args['page'] ?? 1,
            'per_page' => $args['limit'] ?? 10,
            'total_pages' => ceil($total / ($args['limit'] ?? 10)),
        ];
    }

    public static function getInfo(array $params): array
    {
        return [
            'name' => CMS_NAME,
            'version' => CMS_VERSION,
            'api_version' => 'v1',
            'site_url' => SITE_URL,
            'timezone' => getOption('timezone', 'UTC'),
            'authentication' => [
                'type' => 'bearer',
                'header' => 'Authorization: Bearer {api_key}',
            ],
            'endpoints' => [
                'posts' => '/api/v1/posts',
                'pages' => '/api/v1/pages',
                'media' => '/api/v1/media',
                'users' => '/api/v1/users',
                'comments' => '/api/v1/comments',
                'taxonomies' => '/api/v1/taxonomies',
                'menus' => '/api/v1/menus',
                'search' => '/api/v1/search',
            ],
        ];
    }

    private static function preparePost(array $post, bool $full = false): array
    {
        $prepared = [
            'id' => (int)$post['id'],
            'type' => $post['post_type'],
            'title' => $post['title'],
            'slug' => $post['slug'],
            'status' => $post['status'],
            'excerpt' => $post['excerpt'] ?? '',
            'author' => Post::getAuthor($post),
            'created_at' => $post['created_at'],
            'updated_at' => $post['updated_at'],
            'published_at' => $post['published_at'],
            'url' => Post::permalink($post),
        ];

        if ($full) {
            $prepared['content'] = $post['content'];
            $prepared['parent_id'] = $post['parent_id'] ? (int)$post['parent_id'] : null;
            $prepared['menu_order'] = (int)$post['menu_order'];
            $prepared['comment_count'] = (int)$post['comment_count'];
            $prepared['featured_image'] = Post::getFeaturedImage($post);
            $prepared['meta'] = self::getPostMeta($post['id']);
            
                $taxonomies = Taxonomy::getForPostType($post['post_type']);
            $prepared['taxonomies'] = [];
            foreach ($taxonomies as $tax) {
                $terms = Taxonomy::getPostTerms($post['id'], $tax['slug']);
                if ($terms) {
                    $prepared['taxonomies'][$tax['slug']] = array_map([self::class, 'prepareTerm'], $terms);
                }
            }
        }

        return $prepared;
    }

    private static function preparePostType(array $type, string $slug): array
    {
        return [
            'slug' => $slug,
            'name' => $type['label'] ?? ucfirst($slug) . 's',
            'singular' => $type['singular'] ?? ucfirst($slug),
            'icon' => $type['icon'] ?? 'file',
            'supports' => $type['supports'] ?? [],
            'hierarchical' => $type['hierarchical'] ?? false,
            'public' => $type['public'] ?? true,
        ];
    }

    private static function prepareMedia(array $media, bool $full = false): array
    {
        $prepared = [
            'id' => (int)$media['id'],
            'filename' => $media['filename'],
            'mime_type' => $media['mime_type'],
            'url' => Media::getUrl($media),
            'alt_text' => $media['alt_text'] ?? '',
            'title' => $media['title'] ?? '',
            'created_at' => $media['created_at'],
        ];

        if ($full) {
            $prepared['caption'] = $media['caption'] ?? '';
            $prepared['filesize'] = (int)$media['filesize'];
            $prepared['width'] = $media['width'] ? (int)$media['width'] : null;
            $prepared['height'] = $media['height'] ? (int)$media['height'] : null;
            $prepared['folder_id'] = $media['folder_id'] ? (int)$media['folder_id'] : null;
            $prepared['uploaded_by'] = (int)$media['uploaded_by'];
            
            // Include thumbnails for images
            if (Media::isImage($media)) {
                $prepared['thumbnails'] = self::getMediaThumbnails($media);
            }
        }

        return $prepared;
    }

    private static function prepareUser(array $user, bool $full = false): array
    {
        $prepared = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'role' => $user['role'],
        ];

        if ($full) {
            $prepared['email'] = $user['email'];
            $prepared['created_at'] = $user['created_at'];
            $prepared['last_login'] = $user['last_login'];
        }

        return $prepared;
    }

    private static function prepareComment(array $comment, bool $full = false): array
    {
        $prepared = [
            'id' => (int)$comment['id'],
            'post_id' => (int)$comment['post_id'],
            'author_name' => Comment::getAuthorName($comment),
            'content' => $comment['content'],
            'status' => $comment['status'],
            'created_at' => $comment['created_at'],
            'gravatar' => Comment::getGravatar($comment),
        ];

        if ($full) {
            $prepared['parent_id'] = $comment['parent_id'] ? (int)$comment['parent_id'] : null;
            $prepared['author_email'] = $comment['author_email'] ?? '';
            $prepared['author_url'] = $comment['author_url'] ?? '';
            $prepared['user_id'] = $comment['user_id'] ? (int)$comment['user_id'] : null;
        }

        return $prepared;
    }

    private static function prepareTaxonomy(array $taxonomy, string $slug): array
    {
        return [
            'slug' => $slug,
            'name' => $taxonomy['label'] ?? ucfirst($slug),
            'singular' => $taxonomy['singular'] ?? ucfirst($slug),
            'hierarchical' => (bool)($taxonomy['hierarchical'] ?? false),
            'post_types' => $taxonomy['post_types'] ?? [],
        ];
    }

    private static function prepareTerm(array $term, bool $full = false): array
    {
        $prepared = [
            'id' => (int)$term['id'],
            'name' => $term['name'],
            'slug' => $term['slug'],
            'taxonomy' => $term['taxonomy'],
            'count' => (int)($term['count'] ?? 0),
        ];

        if ($full) {
            $prepared['description'] = $term['description'] ?? '';
            $prepared['parent_id'] = $term['parent_id'] ? (int)$term['parent_id'] : null;
        }

        return $prepared;
    }

    private static function prepareMenu(array $menu, bool $full = false): array
    {
        $prepared = [
            'id' => (int)$menu['id'],
            'name' => $menu['name'],
            'slug' => $menu['slug'],
            'location' => $menu['location'],
        ];

        if ($full) {
            $prepared['items'] = Menu::getItems($menu['id']);
            $prepared['created_at'] = $menu['created_at'];
        }

        return $prepared;
    }

    private static function getRequestBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (stripos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in request body', 400);
            }
            
            return $data ?? [];
        }

        return $_POST;
    }

    private static function parseQueryArgs(array $defaults = []): array
    {
        $args = $defaults;

        // Pagination
        if (isset($_GET['page'])) {
            $args['page'] = max(1, (int)$_GET['page']);
        }
        if (isset($_GET['per_page'])) {
            $args['limit'] = min(100, max(1, (int)$_GET['per_page']));
        }
        
        // Calculate offset
        if (isset($args['page']) && isset($args['limit'])) {
            $args['offset'] = ($args['page'] - 1) * $args['limit'];
        }

        // Filtering
        if (isset($_GET['status']) && self::isAuthenticated()) {
            $args['status'] = $_GET['status'];
        }
        if (isset($_GET['author'])) {
            $args['author_id'] = (int)$_GET['author'];
        }
        if (isset($_GET['search'])) {
            $args['search'] = $_GET['search'];
        }
        if (isset($_GET['post_type'])) {
            $args['post_type'] = $_GET['post_type'];
        }

        // Sorting
        if (isset($_GET['orderby'])) {
            $args['orderby'] = $_GET['orderby'];
        }
        if (isset($_GET['order'])) {
            $args['order'] = strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
        }

        // Taxonomy filtering
        if (isset($_GET['category'])) {
            $args['category'] = $_GET['category'];
        }
        if (isset($_GET['tag'])) {
            $args['tag'] = $_GET['tag'];
        }

        return $args;
    }

    private static function getPostMeta(int $postId): array
    {
        $table = Database::table('postmeta');
        $rows = Database::query(
            "SELECT meta_key, meta_value FROM {$table} WHERE post_id = ?",
            [$postId]
        );

        $meta = [];
        foreach ($rows as $row) {
            // Skip internal meta keys
            if (strpos($row['meta_key'], '_') === 0) {
                continue;
            }
            
            $value = $row['meta_value'];
            // Try to decode JSON values
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
            
            $meta[$row['meta_key']] = $value;
        }

        return $meta;
    }

    private static function getMediaThumbnails(array $media): array
    {
        $thumbnails = [];
        $sizes = Media::getThumbnailSizes(false);
        
        foreach ($sizes as $name => $size) {
            $thumbPath = str_replace(
                '/' . $media['filename'],
                '/thumbnails/' . pathinfo($media['filename'], PATHINFO_FILENAME) . '-' . $name . '.' . pathinfo($media['filename'], PATHINFO_EXTENSION),
                $media['filepath']
            );
            
            $fullPath = UPLOADS_PATH . '/' . $thumbPath;
            if (file_exists($fullPath)) {
                $thumbnails[$name] = [
                    'url' => UPLOADS_URL . '/' . $thumbPath,
                    'width' => $size['width'],
                    'height' => $size['height'],
                ];
            }
        }

        return $thumbnails;
    }

    public static function ensureTable(): void
    {
        $table = Database::table('api_keys');
        $rateTable = Database::table('api_rate_limits');

        // API Keys table
        Database::execute("
            CREATE TABLE IF NOT EXISTS {$table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                api_key VARCHAR(64) NOT NULL UNIQUE,
                api_secret VARCHAR(64) NOT NULL,
                permissions TEXT,
                is_active TINYINT(1) DEFAULT 1,
                last_used_at DATETIME NULL,
                expires_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_api_key (api_key),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Rate limiting table
        Database::execute("
            CREATE TABLE IF NOT EXISTS {$rateTable} (
                cache_key VARCHAR(255) PRIMARY KEY,
                request_count INT DEFAULT 1,
                expires_at DATETIME NOT NULL,
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public static function generateApiKey(int $userId, string $name, ?string $expiresAt = null, ?array $permissions = null): array
    {
        self::ensureTable();

        $apiKey = bin2hex(random_bytes(32));
        $apiSecret = bin2hex(random_bytes(32));

        $table = Database::table('api_keys');
        Database::insert($table, [
            'user_id' => $userId,
            'name' => $name,
            'api_key' => hash('sha256', $apiKey),
            'api_secret' => hash('sha256', $apiSecret),
            'permissions' => $permissions ? json_encode($permissions) : null,
            'expires_at' => $expiresAt,
        ]);

        return [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'name' => $name,
            'expires_at' => $expiresAt,
            'message' => 'Store these credentials securely. The secret will not be shown again.',
        ];
    }

    public static function validateApiKey(string $key): ?array
    {
        $table = Database::table('api_keys');
        $hashedKey = hash('sha256', $key);

        return Database::queryOne(
            "SELECT * FROM {$table} WHERE api_key = ?",
            [$hashedKey]
        );
    }

    private static function updateApiKeyLastUsed(int $keyId): void
    {
        $table = Database::table('api_keys');
        Database::update($table, ['last_used_at' => date('Y-m-d H:i:s')], 'id = ?', [$keyId]);
    }

    public static function getUserApiKeys(int $userId): array
    {
        self::ensureTable();
        
        $table = Database::table('api_keys');
        return Database::query(
            "SELECT id, name, is_active, last_used_at, expires_at, created_at 
             FROM {$table} WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    public static function revokeApiKey(int $keyId, int $userId): bool
    {
        $table = Database::table('api_keys');
        return Database::delete($table, 'id = ? AND user_id = ?', [$keyId, $userId]);
    }

    public static function toggleApiKey(int $keyId, int $userId): bool
    {
        $table = Database::table('api_keys');
        return Database::execute(
            "UPDATE {$table} SET is_active = NOT is_active WHERE id = ? AND user_id = ?",
            [$keyId, $userId]
        );
    }

    public static function cleanupRateLimits(): void
    {
        $table = Database::table('api_rate_limits');
        Database::delete($table, 'expires_at < NOW()');
    }
}
