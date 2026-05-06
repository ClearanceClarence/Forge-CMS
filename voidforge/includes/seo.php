<?php
/**
 * SEO System - VoidForge CMS
 * 
 * Comprehensive SEO tools including meta tags, Open Graph,
 * Twitter Cards, JSON-LD schema, XML sitemaps, and robots.txt
 * 
 * @package VoidForge
 * @since 0.3.0
 */

defined('CMS_ROOT') or die('Direct access not allowed');

class SEO
{
    /** @var bool Whether SEO has been initialized */
    private static bool $initialized = false;
    
    /** @var array Current page SEO data cache */
    private static array $currentSeo = [];
    
    /** @var array Default meta robots options */
    public const ROBOTS_OPTIONS = [
        'index' => 'Index',
        'noindex' => 'No Index',
        'follow' => 'Follow',
        'nofollow' => 'No Follow',
    ];
    
    /** @var array Title separator options */
    public const TITLE_SEPARATORS = [
        '|' => '|',
        '-' => '-',
        '–' => '–',
        '—' => '—',
        '•' => '•',
        '»' => '»',
        '›' => '›',
    ];
    
    /** @var array Twitter card types */
    public const TWITTER_CARD_TYPES = [
        'summary' => 'Summary',
        'summary_large_image' => 'Summary with Large Image',
    ];
    
    /** @var array Schema.org types */
    public const SCHEMA_TYPES = [
        'Organization' => 'Organization',
        'Person' => 'Person',
        'LocalBusiness' => 'Local Business',
        'WebSite' => 'Website',
    ];

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        if (class_exists('Plugin')) {
            // Frontend meta tag output
            Plugin::addAction('vf_head', [self::class, 'outputMetaTags'], 1);
            
            // Filter document title
            Plugin::addFilter('document_title', [self::class, 'filterDocumentTitle'], 10, 2);
            
                Plugin::addFilter('the_posts', [self::class, 'addSeoDataToPosts'], 10, 2);
            
            // Debug panel (shows with ?seo_debug=1 for logged-in admins)
            Plugin::addAction('vf_footer', [self::class, 'maybeOutputDebugPanel'], 999);
        }
        
        self::$initialized = true;
    }

    /**
     * Get all SEO meta values stored for a post (title, description, OG fields, etc.).
     * Keys match the _seo_* postmeta keys without the leading underscore and prefix.
     *
     * @return array{title?:string,description?:string,keywords?:string,focus_keyword?:string,canonical?:string,...}
     */
    public static function getPostMeta(int $postId): array
    {
        $defaults = [
            'seo_title' => '',
            'seo_description' => '',
            'seo_keywords' => '',
            'seo_canonical' => '',
            'seo_robots_index' => 'index',
            'seo_robots_follow' => 'follow',
            'seo_og_title' => '',
            'seo_og_description' => '',
            'seo_og_image' => 0,
            'seo_twitter_title' => '',
            'seo_twitter_description' => '',
            'seo_focus_keyword' => '',
            'seo_score' => 0,
        ];
        
        $meta = [];
        foreach ($defaults as $key => $default) {
            $value = get_custom_field('_' . $key, $postId);
            $meta[$key] = $value !== null ? $value : $default;
        }
        
        return $meta;
    }

    /**
     * Persist SEO meta fields for a post from submitted form data.
     * Expects the same keys returned by getPostMeta().
     */
    public static function savePostMeta(int $postId, array $data): void
    {
        $fields = [
            'seo_title', 'seo_description', 'seo_keywords', 'seo_canonical',
            'seo_robots_index', 'seo_robots_follow', 'seo_og_title',
            'seo_og_description', 'seo_og_image', 'seo_twitter_title',
            'seo_twitter_description', 'seo_focus_keyword', 'seo_score',
        ];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $value = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                set_custom_field('_' . $field, $value, $postId);
            }
        }
    }

    public static function addSeoDataToPosts(array $posts, array $args): array
    {
        foreach ($posts as &$post) {
            if (!empty($post['id'])) {
                $post['seo'] = self::getPostMeta($post['id']);
            }
        }
        return $posts;
    }

    /**
     * Build the full <title> string for a page, applying the configured separator and format.
     *
     * @param array|null  $post     Current post row, or null for the homepage
     * @param string      $pageType Context hint: 'single' | 'archive' | 'home' | '404'
     */
    public static function generateTitle(?array $post = null, string $pageType = 'single'): string
    {
        $siteName = get_site_name();
        $separator = getOption('seo_title_separator', '|');
        $format = getOption('seo_title_format', 'post_first'); // post_first or site_first
        
        // Homepage
        if ($pageType === 'home' || $post === null) {
            $homeTitle = getOption('seo_home_title', '');
            if (!empty($homeTitle)) {
                return $homeTitle;
            }
            $tagline = get_site_description();
            return $siteName . ($tagline ? " {$separator} {$tagline}" : '');
        }
        
        $seoMeta = self::getPostMeta($post['id']);
        $title = !empty($seoMeta['seo_title']) ? $seoMeta['seo_title'] : ($post['title'] ?? '');
        
        if (empty($title)) {
            return $siteName;
        }
        
        // Format the title
        if ($format === 'site_first') {
            return "{$siteName} {$separator} {$title}";
        }
        
        return "{$title} {$separator} {$siteName}";
    }

    public static function filterDocumentTitle(string $title, ?array $post = null): string
    {
        return self::generateTitle($post);
    }

    /**
     * Build the meta description string for a page.
     * Prefers the custom _seo_description, then falls back to the excerpt or content snippet.
     */
    public static function generateDescription(?array $post = null, string $pageType = 'single'): string
    {
        // Homepage
        if ($pageType === 'home' || $post === null) {
            $homeDesc = getOption('seo_home_description', '');
            return $homeDesc ?: get_site_description();
        }
        
        $seoMeta = self::getPostMeta($post['id']);
        if (!empty($seoMeta['seo_description'])) {
            return $seoMeta['seo_description'];
        }
        
        // Fall back to excerpt
        if (!empty($post['excerpt'])) {
            return wp_trim_words(strip_tags($post['excerpt']), 30, '...');
        }
        
        // Fall back to content
        if (!empty($post['content'])) {
            $content = $post['content'];
            // If it's JSON (Anvil blocks), try to extract text
            if (isset($content[0]) && $content[0] === '[') {
                $content = self::extractTextFromBlocks($content);
            }
            return wp_trim_words(strip_tags($content), 30, '...');
        }
        
        return get_site_description();
    }

    private static function extractTextFromBlocks(string $json): string
    {
        $blocks = json_decode($json, true);
        if (!is_array($blocks)) {
            return '';
        }
        
        $text = '';
        foreach ($blocks as $block) {
            $attrs = $block['attributes'] ?? [];
            
            // Extract text from common block types
            if (!empty($attrs['content'])) {
                $text .= ' ' . strip_tags($attrs['content']);
            }
            if (!empty($attrs['text'])) {
                $text .= ' ' . strip_tags($attrs['text']);
            }
            if (!empty($attrs['heading'])) {
                $text .= ' ' . strip_tags($attrs['heading']);
            }
            
                if (!empty($block['innerBlocks'])) {
                $text .= ' ' . self::extractTextFromBlocks(json_encode($block['innerBlocks']));
            }
        }
        
        return trim($text);
    }

    public static function generateOpenGraph(?array $post = null, string $pageType = 'single'): array
    {
        $og = [
            'og:site_name' => get_site_name(),
            'og:locale' => getOption('seo_locale', 'en_US'),
        ];
        
        // Homepage
        if ($pageType === 'home' || $post === null) {
            $og['og:type'] = 'website';
            $og['og:title'] = getOption('seo_home_title', get_site_name());
            $og['og:description'] = self::generateDescription(null, 'home');
            $og['og:url'] = SITE_URL;
            
            $defaultImage = getOption('seo_og_default_image', '');
            if ($defaultImage) {
                $og['og:image'] = $defaultImage;
            }
            
            return $og;
        }
        
        // Single post/page
        $seoMeta = self::getPostMeta($post['id']);
        
        $og['og:type'] = ($post['post_type'] ?? 'post') === 'post' ? 'article' : 'website';
        $og['og:title'] = !empty($seoMeta['seo_og_title']) ? $seoMeta['seo_og_title'] : ($seoMeta['seo_title'] ?: $post['title']);
        $og['og:description'] = !empty($seoMeta['seo_og_description']) ? $seoMeta['seo_og_description'] : self::generateDescription($post);
        $og['og:url'] = self::getCanonicalUrl($post);
        
        // Article-specific
        if ($og['og:type'] === 'article') {
            if (!empty($post['published_at'])) {
                $og['article:published_time'] = date('c', strtotime($post['published_at']));
            }
            if (!empty($post['updated_at'])) {
                $og['article:modified_time'] = date('c', strtotime($post['updated_at']));
            }
            // Author
            if (!empty($post['author_id']) && class_exists('User')) {
                $author = User::find($post['author_id']);
                if ($author) {
                    $og['article:author'] = $author['display_name'] ?? $author['username'];
                }
            }
        }
        
        // Image
        $ogImage = self::getOgImage($post, $seoMeta);
        if ($ogImage) {
            $og['og:image'] = $ogImage['url'];
            if (!empty($ogImage['width'])) {
                $og['og:image:width'] = $ogImage['width'];
            }
            if (!empty($ogImage['height'])) {
                $og['og:image:height'] = $ogImage['height'];
            }
        }
        
        return $og;
    }

    private static function getOgImage(array $post, array $seoMeta): ?array
    {
        if (!empty($seoMeta['seo_og_image'])) {
            $media = Media::find((int)$seoMeta['seo_og_image']);
            if ($media) {
                return [
                    'url' => Media::url($media),
                    'width' => $media['width'] ?? null,
                    'height' => $media['height'] ?? null,
                ];
            }
        }
        
        if (!empty($post['featured_image_id'])) {
            $media = Media::find((int)$post['featured_image_id']);
            if ($media) {
                return [
                    'url' => Media::url($media),
                    'width' => $media['width'] ?? null,
                    'height' => $media['height'] ?? null,
                ];
            }
        }
        
        // Fall back to default
        $defaultImage = getOption('seo_og_default_image', '');
        if ($defaultImage) {
            return ['url' => $defaultImage, 'width' => null, 'height' => null];
        }
        
        return null;
    }

    public static function generateTwitterCard(?array $post = null, string $pageType = 'single'): array
    {
        $twitter = [];
        
        $cardType = getOption('seo_twitter_card_type', 'summary_large_image');
        $twitter['twitter:card'] = $cardType;
        
        $twitterSite = getOption('seo_twitter_site', '');
        if ($twitterSite) {
            $twitter['twitter:site'] = $twitterSite;
        }
        
        // Homepage
        if ($pageType === 'home' || $post === null) {
            $twitter['twitter:title'] = getOption('seo_home_title', get_site_name());
            $twitter['twitter:description'] = self::generateDescription(null, 'home');
            
            $defaultImage = getOption('seo_og_default_image', '');
            if ($defaultImage) {
                $twitter['twitter:image'] = $defaultImage;
            }
            
            return $twitter;
        }
        
        // Single post/page
        $seoMeta = self::getPostMeta($post['id']);
        
        $twitter['twitter:title'] = !empty($seoMeta['seo_twitter_title']) 
            ? $seoMeta['seo_twitter_title'] 
            : (!empty($seoMeta['seo_og_title']) ? $seoMeta['seo_og_title'] : $post['title']);
            
        $twitter['twitter:description'] = !empty($seoMeta['seo_twitter_description'])
            ? $seoMeta['seo_twitter_description']
            : self::generateDescription($post);
        
        // Image
        $ogImage = self::getOgImage($post, $seoMeta);
        if ($ogImage) {
            $twitter['twitter:image'] = $ogImage['url'];
        }
        
        return $twitter;
    }

    public static function getCanonicalUrl(?array $post = null): string
    {
        if ($post === null) {
            return SITE_URL;
        }
        
        $seoMeta = self::getPostMeta($post['id']);
        if (!empty($seoMeta['seo_canonical'])) {
            return $seoMeta['seo_canonical'];
        }
        
        if (class_exists('Post')) {
            return Post::permalink($post);
        }
        
        return SITE_URL;
    }

    public static function generateRobotsMeta(?array $post = null, string $pageType = 'single'): string
    {
        if (getOption('seo_noindex_site', false)) {
            return 'noindex, nofollow';
        }
        
        // Homepage
        if ($pageType === 'home' || $post === null) {
            return 'index, follow';
        }
        
        $seoMeta = self::getPostMeta($post['id']);
        $index = $seoMeta['seo_robots_index'] ?? 'index';
        $follow = $seoMeta['seo_robots_follow'] ?? 'follow';
        
        return "{$index}, {$follow}";
    }

    public static function generateSchema(?array $post = null, string $pageType = 'single'): array
    {
        $schemas = [];
        
        // Website schema (always included)
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_site_name(),
            'url' => SITE_URL,
        ];
        
        $websiteSchema['potentialAction'] = [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => SITE_URL . '/?s={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ];
        
        $schemas[] = $websiteSchema;
        
        // Organization/Person schema
        $orgType = getOption('seo_schema_org_type', 'Organization');
        if ($orgType) {
            $orgSchema = [
                '@context' => 'https://schema.org',
                '@type' => $orgType,
                'name' => getOption('seo_schema_org_name', get_site_name()),
                'url' => SITE_URL,
            ];
            
            $logo = getOption('seo_schema_org_logo', '');
            if ($logo) {
                $orgSchema['logo'] = $logo;
            }
            
            $schemas[] = $orgSchema;
        }
        
        // Article schema for posts
        if ($post !== null && ($post['post_type'] ?? 'post') === 'post') {
            $articleSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post['title'] ?? '',
                'url' => self::getCanonicalUrl($post),
                'datePublished' => !empty($post['published_at']) ? date('c', strtotime($post['published_at'])) : '',
                'dateModified' => !empty($post['updated_at']) ? date('c', strtotime($post['updated_at'])) : '',
            ];
            
            // Description
            $description = self::generateDescription($post);
            if ($description) {
                $articleSchema['description'] = $description;
            }
            
            // Author
            if (!empty($post['author_id']) && class_exists('User')) {
                $author = User::find($post['author_id']);
                if ($author) {
                    $articleSchema['author'] = [
                        '@type' => 'Person',
                        'name' => $author['display_name'] ?? $author['username'],
                    ];
                }
            }
            
            // Publisher
            $articleSchema['publisher'] = [
                '@type' => 'Organization',
                'name' => get_site_name(),
                'url' => SITE_URL,
            ];
            
            $logo = getOption('seo_schema_org_logo', '') ?: get_site_logo_url();
            if ($logo) {
                $articleSchema['publisher']['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => $logo,
                ];
            }
            
            // Image
            $seoMeta = self::getPostMeta($post['id']);
            $ogImage = self::getOgImage($post, $seoMeta);
            if ($ogImage) {
                $articleSchema['image'] = $ogImage['url'];
            }
            
            $schemas[] = $articleSchema;
        }
        
        // BreadcrumbList schema
        if ($post !== null) {
            $breadcrumbSchema = self::generateBreadcrumbSchema($post);
            if ($breadcrumbSchema) {
                $schemas[] = $breadcrumbSchema;
            }
        }
        
        return $schemas;
    }

    private static function generateBreadcrumbSchema(array $post): ?array
    {
        $items = [];
        $position = 1;
        
        // Home
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Home',
            'item' => SITE_URL,
        ];
        
        // Post type archive (if applicable)
        $postType = $post['post_type'] ?? 'post';
        if ($postType !== 'page') {
            $typeConfig = Post::getType($postType);
            if ($typeConfig) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $typeConfig['label'] ?? ucfirst($postType),
                    'item' => SITE_URL . '/' . $postType,
                ];
            }
        }
        
        // Current page
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $post['title'] ?? '',
            'item' => self::getCanonicalUrl($post),
        ];
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Echo all SEO meta tags for the current page into <head>.
     * Outputs: title, description, keywords, canonical, robots, Open Graph, Twitter Cards, JSON-LD schema.
     * Hooked to 'vf_head' automatically during SEO::init().
     */
    public static function outputMetaTags(): void
    {
        global $post;
        
        $currentPost = $post ?? null;
        $pageType = $currentPost ? 'single' : 'home';
        
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = parse_url(SITE_URL, PHP_URL_PATH) ?: '';
        $path = trim(str_replace($basePath, '', $requestUri), '/');
        
        if (empty($path)) {
            $pageType = 'home';
            $currentPost = null;
        }
        
        echo "\n<!-- VoidForge SEO -->\n";
        
        // Meta description
        $description = self::generateDescription($currentPost, $pageType);
        if ($description) {
            echo '<meta name="description" content="' . esc($description) . '">' . "\n";
        }
        
        // Keywords (optional)
        if ($currentPost) {
            $seoMeta = self::getPostMeta($currentPost['id']);
            if (!empty($seoMeta['seo_keywords'])) {
                echo '<meta name="keywords" content="' . esc($seoMeta['seo_keywords']) . '">' . "\n";
            }
        }
        
        // Robots
        $robots = self::generateRobotsMeta($currentPost, $pageType);
        echo '<meta name="robots" content="' . esc($robots) . '">' . "\n";
        
        // Canonical
        $canonical = self::getCanonicalUrl($currentPost);
        echo '<link rel="canonical" href="' . esc($canonical) . '">' . "\n";
        
        // Open Graph
        $og = self::generateOpenGraph($currentPost, $pageType);
        foreach ($og as $property => $content) {
            if ($content) {
                echo '<meta property="' . esc($property) . '" content="' . esc($content) . '">' . "\n";
            }
        }
        
        // Twitter Card
        $twitter = self::generateTwitterCard($currentPost, $pageType);
        foreach ($twitter as $name => $content) {
            if ($content) {
                echo '<meta name="' . esc($name) . '" content="' . esc($content) . '">' . "\n";
            }
        }
        
        // JSON-LD Schema
        $schemas = self::generateSchema($currentPost, $pageType);
        foreach ($schemas as $schema) {
            echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
        
        echo "<!-- /VoidForge SEO -->\n\n";
    }

    /**
     * Generate the XML sitemap document for all published posts and taxonomy terms.
     * Respects the seo_sitemap_post_types and seo_sitemap_taxonomies options.
     *
     * @return string Complete XML string ready to output with Content-Type: application/xml
     */
    public static function generateSitemap(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Homepage
        $xml .= self::sitemapUrl(SITE_URL, date('c'), 'daily', '1.0');
        
        $enabledTypes = getOption('seo_sitemap_post_types', ['post', 'page']);
        if (!is_array($enabledTypes)) {
            $enabledTypes = ['post', 'page'];
        }
        
        // Query published posts
        foreach ($enabledTypes as $postType) {
            $posts = Post::query([
                'post_type' => $postType,
                'status' => 'published',
                'limit' => 1000,
                'orderby' => 'updated_at',
                'order' => 'DESC',
            ]);
            
            foreach ($posts as $post) {
                // Skip if noindex
                $seoMeta = self::getPostMeta($post['id']);
                if (($seoMeta['seo_robots_index'] ?? 'index') === 'noindex') {
                    continue;
                }
                
                $url = Post::permalink($post);
                $lastmod = $post['updated_at'] ?? $post['published_at'] ?? $post['created_at'];
                $changefreq = $postType === 'page' ? 'weekly' : 'monthly';
                $priority = $postType === 'page' ? '0.8' : '0.6';
                
                $xml .= self::sitemapUrl($url, date('c', strtotime($lastmod)), $changefreq, $priority);
            }
        }
        
        // Include taxonomy terms if enabled
        if (getOption('seo_sitemap_taxonomies', true)) {
            $taxonomies = Taxonomy::getAll();
            foreach ($taxonomies as $taxSlug => $taxonomy) {
                $terms = Taxonomy::getTerms($taxSlug);
                foreach ($terms as $term) {
                    if ($term['count'] > 0) {
                        $url = SITE_URL . '/' . $taxSlug . '/' . $term['slug'];
                        $xml .= self::sitemapUrl($url, date('c'), 'weekly', '0.5');
                    }
                }
            }
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }

    private static function sitemapUrl(string $url, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n" .
               "    <loc>" . esc($url) . "</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }

    public static function generateSitemapIndex(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $enabledTypes = getOption('seo_sitemap_post_types', ['post', 'page']);
        
        foreach ($enabledTypes as $postType) {
            $count = Post::count(['post_type' => $postType, 'status' => 'published']);
            if ($count > 0) {
                $xml .= "  <sitemap>\n";
                $xml .= "    <loc>" . SITE_URL . "/sitemap-{$postType}.xml</loc>\n";
                $xml .= "    <lastmod>" . date('c') . "</lastmod>\n";
                $xml .= "  </sitemap>\n";
            }
        }
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }

    /**
     * Generate the robots.txt content, using any custom text saved in settings.
     *
     * @return string Plain-text content ready to output with Content-Type: text/plain
     */
    public static function generateRobotsTxt(): string
    {
        $custom = getOption('seo_robots_txt', '');
        if (!empty($custom)) {
            return $custom;
        }
        
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "\n";
        
        // Disallow admin
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /includes/\n";
        $robots .= "Disallow: /plugins/\n";
        
        if (getOption('seo_sitemap_enabled', true)) {
            $robots .= "\n";
            $robots .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";
        }
        
        return $robots;
    }

    /**
     * Score the SEO quality of a post and return actionable suggestions.
     *
     * @param array $seoMeta SEO meta as returned by getPostMeta()
     * @return array{score:int,checks:array<array{passed:bool,message:string,weight:int}>}
     */
    public static function analyzeContent(array $post, array $seoMeta): array
    {
        $issues = [];
        $score = 100;
        $focusKeyword = $seoMeta['seo_focus_keyword'] ?? '';
        
        $title = $post['title'] ?? '';
        $content = $post['content'] ?? '';
        $seoTitle = $seoMeta['seo_title'] ?? '';
        $seoDescription = $seoMeta['seo_description'] ?? '';
        
        // Extract text from Anvil blocks if needed
        if (isset($content[0]) && $content[0] === '[') {
            $content = self::extractTextFromBlocks($content);
        }
        
        $contentLength = str_word_count(strip_tags($content));
        
        // Title checks
        if (empty($title)) {
            $issues[] = ['type' => 'error', 'message' => 'Post has no title'];
            $score -= 20;
        } elseif (strlen($title) > 60) {
            $issues[] = ['type' => 'warning', 'message' => 'Title is too long (over 60 characters)'];
            $score -= 5;
        }
        
        // Meta description checks
        if (empty($seoDescription)) {
            $issues[] = ['type' => 'warning', 'message' => 'No meta description set'];
            $score -= 10;
        } elseif (strlen($seoDescription) < 120) {
            $issues[] = ['type' => 'warning', 'message' => 'Meta description is too short (under 120 characters)'];
            $score -= 5;
        } elseif (strlen($seoDescription) > 160) {
            $issues[] = ['type' => 'warning', 'message' => 'Meta description is too long (over 160 characters)'];
            $score -= 5;
        }
        
        // Content length
        if ($contentLength < 300) {
            $issues[] = ['type' => 'warning', 'message' => 'Content is thin (under 300 words)'];
            $score -= 10;
        }
        
        // Focus keyword checks
        if (!empty($focusKeyword)) {
            $focusLower = strtolower($focusKeyword);
            
            // In title
            if (stripos($title, $focusKeyword) === false) {
                $issues[] = ['type' => 'warning', 'message' => 'Focus keyword not found in title'];
                $score -= 5;
            }
            
            // In meta description
            if (!empty($seoDescription) && stripos($seoDescription, $focusKeyword) === false) {
                $issues[] = ['type' => 'info', 'message' => 'Focus keyword not found in meta description'];
                $score -= 3;
            }
            
            // In content
            $keywordCount = substr_count(strtolower($content), $focusLower);
            if ($keywordCount === 0) {
                $issues[] = ['type' => 'error', 'message' => 'Focus keyword not found in content'];
                $score -= 15;
            } else {
                // Keyword density
                $density = ($keywordCount / max(1, $contentLength)) * 100;
                if ($density < 0.5) {
                    $issues[] = ['type' => 'info', 'message' => 'Focus keyword density is low (' . number_format($density, 1) . '%)'];
                    $score -= 3;
                } elseif ($density > 3) {
                    $issues[] = ['type' => 'warning', 'message' => 'Focus keyword may be overused (' . number_format($density, 1) . '%)'];
                    $score -= 5;
                }
            }
            
            // In slug
            $slug = $post['slug'] ?? '';
            if (!empty($slug) && stripos($slug, str_replace(' ', '-', $focusLower)) === false) {
                $issues[] = ['type' => 'info', 'message' => 'Focus keyword not found in URL slug'];
                $score -= 3;
            }
        }
        
        // Featured image
        if (empty($post['featured_image_id'])) {
            $issues[] = ['type' => 'info', 'message' => 'No featured image set'];
            $score -= 5;
        }
        
        // Ensure score is within bounds
        $score = max(0, min(100, $score));
        
        return [
            'score' => $score,
            'issues' => $issues,
            'stats' => [
                'word_count' => $contentLength,
                'title_length' => strlen($title),
                'description_length' => strlen($seoDescription),
            ],
        ];
    }

    public static function getScoreColor(int $score): string
    {
        if ($score >= 80) {
            return '#10b981'; // Green
        } elseif ($score >= 50) {
            return '#f59e0b'; // Orange
        }
        return '#ef4444'; // Red
    }

    /**
     * Return a human-readable label for an SEO score (0–100).
     * 80+ → Good, 50–79 → Needs Improvement, 0–49 → Poor.
     */
    public static function getScoreLabel(int $score): string
    {
        if ($score >= 80) {
            return 'Good';
        } elseif ($score >= 50) {
            return 'Needs Improvement';
        }
        return 'Poor';
    }

    /**
     * Output debug panel if ?seo_debug=1 and user is admin
     * Access any page with ?seo_debug=1 to see SEO data
     */
    public static function maybeOutputDebugPanel(): void
    {
        if (!isset($_GET['seo_debug']) || $_GET['seo_debug'] !== '1') {
            return;
        }
        
        if (!class_exists('User')) {
            return;
        }
        
        User::startSession();
        if (!User::isLoggedIn() || !User::isAdmin()) {
            return;
        }
        
        global $post;
        $pageType = 'home';
        $currentPost = null;
        
        if (!empty($post)) {
            $currentPost = $post;
            $pageType = 'single';
        }
        
        $title = self::generateTitle($currentPost, $pageType);
        $description = self::generateDescription($currentPost, $pageType);
        $canonical = self::getCanonicalUrl($currentPost);
        $robots = self::generateRobotsMeta($currentPost, $pageType);
        $og = self::generateOpenGraph($currentPost, $pageType);
        $twitter = self::generateTwitterCard($currentPost, $pageType);
        $schema = self::generateSchema($currentPost, $pageType);
        
        $seoMeta = [];
        $analysis = null;
        if ($currentPost) {
            $seoMeta = self::getPostMeta($currentPost['id']);
            $analysis = self::analyzeContent($currentPost, $seoMeta);
        }
        
        $score = $analysis['score'] ?? 0;
        $scoreColor = self::getScoreColor($score);
        $scoreLabel = self::getScoreLabel($score);
        ?>
        <div id="seo-debug-panel" style="
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 420px;
            max-height: 80vh;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            color: #e2e8f0;
            z-index: 999999;
            overflow: hidden;
        ">
            <div style="
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                cursor: move;
            " id="seo-debug-header">
                <span style="font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                    SEO Debug
                </span>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <?php if ($analysis): ?>
                    <span style="background: <?= $scoreColor ?>; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;"><?= $score ?></span>
                    <?php endif; ?>
                    <button onclick="document.getElementById('seo-debug-panel').style.display='none'" style="background: none; border: none; color: white; cursor: pointer; padding: 4px;">✕</button>
                </div>
            </div>
            <div style="max-height: calc(80vh - 50px); overflow-y: auto; padding: 16px;">
                <!-- Title -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 6px;">Title (<?= strlen($title) ?> chars)</div>
                    <div style="color: #a5f3fc; word-break: break-word;"><?= htmlspecialchars($title) ?></div>
                </div>
                
                <!-- Description -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 6px;">Description (<?= strlen($description) ?> chars)</div>
                    <div style="color: #d1d5db; word-break: break-word;"><?= htmlspecialchars($description) ?></div>
                </div>
                
                <!-- Quick Info -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px;">
                    <div style="background: #1e293b; padding: 10px; border-radius: 6px;">
                        <div style="font-size: 10px; color: #64748b; margin-bottom: 4px;">Robots</div>
                        <div style="font-family: monospace; font-size: 11px; color: #fbbf24;"><?= htmlspecialchars($robots) ?></div>
                    </div>
                    <div style="background: #1e293b; padding: 10px; border-radius: 6px;">
                        <div style="font-size: 10px; color: #64748b; margin-bottom: 4px;">Canonical</div>
                        <div style="font-family: monospace; font-size: 11px; color: #34d399; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($canonical) ?></div>
                    </div>
                </div>
                
                <!-- Open Graph -->
                <details style="margin-bottom: 12px;">
                    <summary style="cursor: pointer; padding: 8px 12px; background: #1e293b; border-radius: 6px; font-weight: 500;">Open Graph</summary>
                    <div style="padding: 12px; background: #1e293b; border-radius: 0 0 6px 6px; margin-top: -6px;">
                        <?php foreach ($og as $prop => $value): ?>
                        <div style="display: flex; margin-bottom: 6px; font-size: 12px;">
                            <span style="color: #64748b; width: 100px; flex-shrink: 0;"><?= htmlspecialchars($prop) ?></span>
                            <span style="color: #e2e8f0; word-break: break-word;"><?= htmlspecialchars($value) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </details>
                
                <!-- Twitter Card -->
                <details style="margin-bottom: 12px;">
                    <summary style="cursor: pointer; padding: 8px 12px; background: #1e293b; border-radius: 6px; font-weight: 500;">Twitter Card</summary>
                    <div style="padding: 12px; background: #1e293b; border-radius: 0 0 6px 6px; margin-top: -6px;">
                        <?php foreach ($twitter as $name => $value): ?>
                        <div style="display: flex; margin-bottom: 6px; font-size: 12px;">
                            <span style="color: #64748b; width: 100px; flex-shrink: 0;"><?= htmlspecialchars($name) ?></span>
                            <span style="color: #e2e8f0; word-break: break-word;"><?= htmlspecialchars($value) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </details>
                
                <!-- Schema -->
                <details style="margin-bottom: 12px;">
                    <summary style="cursor: pointer; padding: 8px 12px; background: #1e293b; border-radius: 6px; font-weight: 500;">JSON-LD Schema (<?= count($schema) ?>)</summary>
                    <div style="padding: 12px; background: #0f172a; border-radius: 0 0 6px 6px; margin-top: -6px; max-height: 200px; overflow: auto;">
                        <pre style="margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 10px; color: #a5f3fc; white-space: pre-wrap;"><?= htmlspecialchars(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                    </div>
                </details>
                
                <!-- Analysis -->
                <?php if ($analysis && !empty($analysis['issues'])): ?>
                <details open style="margin-bottom: 12px;">
                    <summary style="cursor: pointer; padding: 8px 12px; background: #1e293b; border-radius: 6px; font-weight: 500;">
                        Issues (<?= count($analysis['issues']) ?>)
                    </summary>
                    <div style="padding: 12px; background: #1e293b; border-radius: 0 0 6px 6px; margin-top: -6px;">
                        <?php foreach ($analysis['issues'] as $issue): ?>
                        <div style="
                            display: flex;
                            align-items: flex-start;
                            gap: 8px;
                            padding: 6px 8px;
                            margin-bottom: 4px;
                            border-radius: 4px;
                            font-size: 11px;
                            background: <?= $issue['type'] === 'error' ? 'rgba(239,68,68,0.15)' : ($issue['type'] === 'warning' ? 'rgba(245,158,11,0.15)' : 'rgba(59,130,246,0.15)') ?>;
                            color: <?= $issue['type'] === 'error' ? '#fca5a5' : ($issue['type'] === 'warning' ? '#fcd34d' : '#93c5fd') ?>;
                        ">
                            <?= htmlspecialchars($issue['message']) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endif; ?>
                
                <!-- Post Info -->
                <?php if ($currentPost): ?>
                <div style="padding: 10px; background: #1e293b; border-radius: 6px; font-size: 11px; color: #64748b;">
                    <strong style="color: #e2e8f0;">Post:</strong> <?= htmlspecialchars($currentPost['title']) ?> 
                    <span style="color: #6366f1;">(ID: <?= $currentPost['id'] ?>)</span>
                </div>
                <?php else: ?>
                <div style="padding: 10px; background: #1e293b; border-radius: 6px; font-size: 11px; color: #64748b;">
                    <strong style="color: #e2e8f0;">Page Type:</strong> Homepage
                </div>
                <?php endif; ?>
            </div>
        </div>
        <script>
        (function() {
            const panel = document.getElementById('seo-debug-panel');
            const header = document.getElementById('seo-debug-header');
            let isDragging = false, offsetX, offsetY;
            header.addEventListener('mousedown', (e) => {
                isDragging = true;
                offsetX = e.clientX - panel.offsetLeft;
                offsetY = e.clientY - panel.offsetTop;
                panel.style.right = 'auto';
                panel.style.bottom = 'auto';
            });
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                panel.style.left = (e.clientX - offsetX) + 'px';
                panel.style.top = (e.clientY - offsetY) + 'px';
            });
            document.addEventListener('mouseup', () => isDragging = false);
        })();
        </script>
        <?php
    }
}
