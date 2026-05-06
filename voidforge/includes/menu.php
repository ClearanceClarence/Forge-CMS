<?php
/**
 * Menu Management Class - VoidForge CMS
 * 
 * Handles navigation menu creation, management, and display
 */

class Menu
{
    private static array $locations = [];
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;
        
        self::registerLocation('primary', 'Primary Navigation');
        self::registerLocation('footer', 'Footer Menu');
    }

    /**
     * Register a menu location (called by themes)
     */
    /**
     * Register a named menu location that themes can assign menus to.
     * Call from a theme's functions.php during the 'init' action.
     *
     * @param string $slug Unique location identifier (e.g. 'primary')
     * @param string $name Human-readable label shown in the menu admin
     */
    public static function registerLocation(string $slug, string $name): void
    {
        self::$locations[$slug] = $name;
    }

    public static function getLocations(): array
    {
        return self::$locations;
    }

    public static function getMenuByLocation(string $location): ?array
    {
        $table = Database::table('menus');
        $menu = Database::queryOne("SELECT * FROM {$table} WHERE location = ?", [$location]);
        return $menu ?: null;
    }

    public static function create(array $data): int
    {
        $data = safe_apply_filters('pre_save_menu', $data, null);
        
        $table = Database::table('menus');
        
        $id = Database::insert($table, [
            'name' => $data['name'],
            'slug' => slugify($data['name']),
            'location' => $data['location'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        safe_do_action('menu_saved', $id, $data);
        
        return $id;
    }

    public static function update(int $id, array $data): bool
    {
        $data = safe_apply_filters('pre_save_menu', $data, $id);
        
        $table = Database::table('menus');
        
        $updateData = ['name' => $data['name']];
        
        if (isset($data['location'])) {
            // Clear location from other menus first
            if ($data['location']) {
                Database::query("UPDATE {$table} SET location = NULL WHERE location = ? AND id != ?", 
                    [$data['location'], $id]);
            }
            $updateData['location'] = $data['location'] ?: null;
        }
        
        $result = Database::update($table, $updateData, "id = ?", [$id]) > 0;
        
        if ($result) {
            safe_do_action('menu_saved', $id, $data);
        }
        
        return $result;
    }

    public static function delete(int $id): bool
    {
        $menu = self::find($id);
        if (!$menu) {
            return false;
        }
        
        $menusTable = Database::table('menus');
        $itemsTable = Database::table('menu_items');
        
        // Delete all menu items first
        Database::query("DELETE FROM {$itemsTable} WHERE menu_id = ?", [$id]);
        
        // Delete the menu
        $result = Database::delete($menusTable, "id = ?", [$id]) > 0;
        
        if ($result) {
            safe_do_action('menu_deleted', $id, $menu);
        }
        
        return $result;
    }

    public static function find(int $id): ?array
    {
        $table = Database::table('menus');
        return Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    }

    public static function getAll(): array
    {
        $table = Database::table('menus');
        return Database::query("SELECT * FROM {$table} ORDER BY name ASC");
    }

    /**
     * Add an item to a menu.
     *
     * @param array{
     *   title:string,
     *   type:'page'|'post'|'custom'|'taxonomy',
     *   object_id?:int,
     *   url?:string,
     *   target?:string,
     *   parent_id?:int,
     *   position?:int
     * } $data
     * @return int New menu item ID
     */
    public static function addItem(int $menuId, array $data): int
    {
        $table = Database::table('menu_items');
        
        // Get max position
        $maxPos = Database::queryOne(
            "SELECT MAX(position) as max_pos FROM {$table} WHERE menu_id = ? AND parent_id = ?",
            [$menuId, $data['parent_id'] ?? 0]
        );
        $position = ($maxPos['max_pos'] ?? -1) + 1;
        
        return Database::insert($table, [
            'menu_id' => $menuId,
            'parent_id' => $data['parent_id'] ?? 0,
            'title' => $data['title'],
            'type' => $data['type'], // page, post, custom, category, post_type
            'object_id' => $data['object_id'] ?? null,
            'url' => $data['url'] ?? null,
            'target' => $data['target'] ?? '_self',
            'css_class' => $data['css_class'] ?? null,
            'position' => $position,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function updateItem(int $id, array $data): bool
    {
        $table = Database::table('menu_items');
        
        $updateData = [];
        if (isset($data['title'])) $updateData['title'] = $data['title'];
        if (isset($data['url'])) $updateData['url'] = $data['url'];
        if (isset($data['target'])) $updateData['target'] = $data['target'];
        if (isset($data['css_class'])) $updateData['css_class'] = $data['css_class'];
        if (isset($data['parent_id'])) $updateData['parent_id'] = $data['parent_id'];
        if (isset($data['position'])) $updateData['position'] = $data['position'];
        
        return Database::update($table, $updateData, "id = ?", [$id]) > 0;
    }

    public static function deleteItem(int $id): bool
    {
        $table = Database::table('menu_items');
        
        // Get item to find menu_id
        $item = Database::queryOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
        if (!$item) return false;
        
        // Delete children first (recursive)
        $children = Database::query("SELECT id FROM {$table} WHERE parent_id = ?", [$id]);
        foreach ($children as $child) {
            self::deleteItem($child['id']);
        }
        
        // Delete the item
        return Database::delete($table, "id = ?", [$id]) > 0;
    }

    public static function getItems(int $menuId): array
    {
        $table = Database::table('menu_items');
        $items = Database::query(
            "SELECT * FROM {$table} WHERE menu_id = ? ORDER BY position ASC",
            [$menuId]
        );
        
        $tree = self::buildTree($items);
        
        // Allow filtering of menu items
        return safe_apply_filters('menu_items', $tree, $menuId);
    }

    public static function getItemsFlat(int $menuId): array
    {
        $table = Database::table('menu_items');
        return Database::query(
            "SELECT * FROM {$table} WHERE menu_id = ? ORDER BY position ASC",
            [$menuId]
        );
    }

    private static function buildTree(array $items, int $parentId = 0): array
    {
        $branch = [];
        
        foreach ($items as $item) {
            if ((int)$item['parent_id'] === $parentId) {
                $item['children'] = self::buildTree($items, (int)$item['id']);
                $branch[] = $item;
            }
        }
        
        return $branch;
    }

    /**
     * Persist a new item order for a menu, including nested parent relationships.
     * $items is the nested array produced by the drag-and-drop UI.
     */
    public static function saveOrder(int $menuId, array $items, int $parentId = 0): void
    {
        $table = Database::table('menu_items');
        
        foreach ($items as $position => $item) {
            Database::update($table, [
                'parent_id' => $parentId,
                'position' => $position,
            ], "id = ? AND menu_id = ?", [$item['id'], $menuId]);
            
            if (!empty($item['children'])) {
                self::saveOrder($menuId, $item['children'], $item['id']);
            }
        }
    }

    public static function getItemUrl(array $item): string
    {
        switch ($item['type']) {
            case 'custom':
                return $item['url'] ?: '#';
                
            case 'page':
            case 'post':
                if ($item['object_id']) {
                    $post = Post::find($item['object_id']);
                    if ($post) {
                        return Post::permalink($post);
                    }
                }
                return '#';
                
            case 'category':
                if ($item['object_id']) {
                    return SITE_URL . '/category/' . $item['object_id'];
                }
                return '#';
                
            case 'post_type':
                if ($item['url']) {
                    return SITE_URL . '/' . $item['url'];
                }
                return '#';
                
            default:
                // For custom post types, try to get the post permalink
                if ($item['object_id']) {
                    $post = Post::find($item['object_id']);
                    if ($post) {
                        return Post::permalink($post);
                    }
                }
                return $item['url'] ?: '#';
        }
    }

    /**
     * Render the menu assigned to $location as an HTML navigation string.
     * Returns an empty string if no menu is assigned to the location.
     *
     * @param array{
     *   container?:string,
     *   container_class?:string,
     *   menu_class?:string,
     *   submenu_class?:string,
     *   depth?:int
     * } $options
     */
    public static function display(string $location, array $options = []): string
    {
        $menu = self::getMenuByLocation($location);
        if (!$menu) {
            return '';
        }
        
        return self::render($menu['id'], $options);
    }

    public static function render(int $menuId, array $options = []): string
    {
        $items = self::getItems($menuId);
        if (empty($items)) {
            return '';
        }
        
        $defaults = [
            'container' => 'nav',
            'container_class' => 'navigation',
            'container_id' => '',
            'menu_class' => 'menu',
            'menu_id' => '',
            'item_class' => 'menu-item',
            'link_class' => 'menu-link',
            'submenu_class' => 'sub-menu',
            'depth' => 0, // 0 = unlimited
            'before' => '',
            'after' => '',
            'link_before' => '',
            'link_after' => '',
        ];
        
        $options = array_merge($defaults, $options);
        
        $html = self::renderItems($items, $options, 0);
        
        // Wrap in UL
        $menuAttr = $options['menu_class'] ? ' class="' . esc($options['menu_class']) . '"' : '';
        $menuAttr .= $options['menu_id'] ? ' id="' . esc($options['menu_id']) . '"' : '';
        $html = "<ul{$menuAttr}>{$html}</ul>";
        
        // Wrap in container
        if ($options['container']) {
            $containerAttr = $options['container_class'] ? ' class="' . esc($options['container_class']) . '"' : '';
            $containerAttr .= $options['container_id'] ? ' id="' . esc($options['container_id']) . '"' : '';
            $html = "<{$options['container']}{$containerAttr}>{$html}</{$options['container']}>";
        }
        
        return $html;
    }

    private static function renderItems(array $items, array $options, int $depth): string
    {
        if ($options['depth'] > 0 && $depth >= $options['depth']) {
            return '';
        }
        
        $html = '';
        
        foreach ($items as $item) {
            $url = self::getItemUrl($item);
            $hasChildren = !empty($item['children']);
            
            $classes = [$options['item_class']];
            if ($hasChildren) {
                $classes[] = 'has-children';
            }
            if ($item['css_class']) {
                $classes[] = $item['css_class'];
            }
            
            // Allow filtering of menu item classes
            $classes = safe_apply_filters('menu_item_classes', $classes, $item, $depth);
            
            $itemAttr = ' class="' . esc(implode(' ', $classes)) . '"';
            
            $linkAttr = $options['link_class'] ? ' class="' . esc($options['link_class']) . '"' : '';
            $linkAttr .= ' href="' . esc($url) . '"';
            if ($item['target'] && $item['target'] !== '_self') {
                $linkAttr .= ' target="' . esc($item['target']) . '"';
                if ($item['target'] === '_blank') {
                    $linkAttr .= ' rel="noopener noreferrer"';
                }
            }
            
            $html .= "<li{$itemAttr}>";
            $html .= $options['before'];
            $html .= "<a{$linkAttr}>";
            $html .= $options['link_before'];
            $html .= esc($item['title']);
            $html .= $options['link_after'];
            $html .= '</a>';
            $html .= $options['after'];
            
            if ($hasChildren) {
                $submenuAttr = $options['submenu_class'] ? ' class="' . esc($options['submenu_class']) . '"' : '';
                $html .= "<ul{$submenuAttr}>";
                $html .= self::renderItems($item['children'], $options, $depth + 1);
                $html .= '</ul>';
            }
            
            $html .= '</li>';
        }
        
        return $html;
    }

    public static function getAvailablePages(): array
    {
        $postsTable = Database::table('posts');
        return Database::query(
            "SELECT id, title, slug FROM {$postsTable} WHERE post_type = 'page' AND status = 'published' ORDER BY title ASC"
        );
    }

    public static function getAvailablePosts(): array
    {
        $postsTable = Database::table('posts');
        return Database::query(
            "SELECT id, title, slug FROM {$postsTable} WHERE post_type = 'post' AND status = 'published' ORDER BY title ASC LIMIT 50"
        );
    }

    public static function getAvailableCategories(): array
    {
        // For now, return empty - categories system would need to be implemented
        return [];
    }

    /**
     * Get available custom post types for menu (individual posts)
     */
    public static function getAvailablePostTypes(): array
    {
        $types = [];
        $allTypes = Post::getTypes();
        $postsTable = Database::table('posts');
        
        foreach ($allTypes as $slug => $config) {
            // Skip built-in types
            if (in_array($slug, ['post', 'page'])) continue;
            
            if ($config['public'] ?? true) {
                // Get published posts of this type
                $posts = Database::query(
                    "SELECT id, title, slug FROM {$postsTable} WHERE post_type = ? AND status = 'published' ORDER BY title ASC LIMIT 50",
                    [$slug]
                );
                
                if (!empty($posts)) {
                    $types[] = [
                        'slug' => $slug,
                        'name' => $config['label'] ?? ucfirst($slug),
                        'singular' => $config['singular'] ?? ucfirst($slug),
                        'posts' => $posts,
                    ];
                }
            }
        }
        
        return $types;
    }

    /**
     * Check whether an item of a given type is already in a menu.
     * Prevents adding the same page or post twice.
     */
    public static function itemExists(int $menuId, string $type, $objectId = null, ?string $url = null): bool
    {
        $table = Database::table('menu_items');
        
        if ($type === 'custom') {
            // Custom links can be duplicated, so always allow
            return false;
        }
        
        if ($objectId) {
            $exists = Database::queryOne(
                "SELECT id FROM {$table} WHERE menu_id = ? AND type = ? AND object_id = ?",
                [$menuId, $type, $objectId]
            );
            return $exists !== null;
        }
        
        return false;
    }

    public static function duplicate(int $id): ?int
    {
        $menu = self::find($id);
        if (!$menu) return null;
        
        // Create new menu
        $newId = self::create([
            'name' => $menu['name'] . ' (Copy)',
            'location' => null,
        ]);
        
        // Copy items
        $items = self::getItemsFlat($id);
        $idMap = [];
        
        foreach ($items as $item) {
            $oldId = $item['id'];
            $newItemId = self::addItem($newId, [
                'parent_id' => 0, // Will be fixed in second pass
                'title' => $item['title'],
                'type' => $item['type'],
                'object_id' => $item['object_id'],
                'url' => $item['url'],
                'target' => $item['target'],
                'css_class' => $item['css_class'],
            ]);
            $idMap[$oldId] = $newItemId;
        }
        
        // Fix parent IDs
        foreach ($items as $item) {
            if ($item['parent_id'] > 0 && isset($idMap[$item['parent_id']])) {
                self::updateItem($idMap[$item['id']], [
                    'parent_id' => $idMap[$item['parent_id']],
                ]);
            }
        }
        
        return $newId;
    }
}
