# VoidForge CMS

A modern, lightweight content management system built with pure PHP. No frameworks, no bloat — just powerful features and clean code.

![Version](https://img.shields.io/badge/version-0.3.2-6366f1?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square)
![License](https://img.shields.io/badge/license-MIT-10b981?style=flat-square)

## Highlights

- ⚡ **Blazing Fast** — Sub-50ms page loads, ~400KB total install size
- 🎨 **Visual Block Editor** — 21+ block types with drag-and-drop and live frontend editing
- 🔍 **Built-in SEO** — Meta tags, Open Graph, JSON-LD schema, XML sitemap (no plugins needed)
- 🔌 **Extensible** — WordPress-compatible hooks with 90+ actions and filters
- 📱 **Responsive Admin** — Modern interface with customizable themes
- 🛡️ **Secure** — CSRF protection, XSS filtering, secure sessions

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx with mod_rewrite enabled

## Installation

1. Upload the `voidforge` folder to your web server
2. Create a MySQL database
3. Visit your site URL in a browser
4. Follow the installation wizard
5. Log in to the admin panel at `/admin`

## Features

### What's New in v0.3.2

- **Production-safe error reporting** — `display_errors` is now off by default; toggle via `VOIDFORGE_DEBUG=1` environment variable
- **Login rate limiting** — Brute-force protection with 5-attempt lockout and session-based countdown
- **Login CSRF protection** — Login form now issues and verifies a CSRF token
- **Stronger file upload validation** — Detected MIME type is cross-checked against the expected type for the file's extension
- **Salt generator cleaned up** — Now emits only the 3 constants VoidForge actually uses (`AUTH_KEY`, `SECURE_AUTH_KEY`, `NONCE_SALT`)
- **`NONCE_SALT` now properly defined** — Was missing from config and installer; nonces were silently sharing a hardcoded fallback key across all installs
- **Single version source of truth** — All version references now read from `CMS_VERSION` in `config.php`

### SEO Tools

Comprehensive search engine optimization built into every page:

- **Meta Tags** — Custom title, description, and keywords per page
- **Open Graph** — Facebook and social media sharing optimization
- **Twitter Cards** — Optimized Twitter/X sharing with image support
- **JSON-LD Schema** — Structured data for rich search results (WebSite, Organization, Article, BreadcrumbList)
- **XML Sitemap** — Automatic Google-compatible sitemap at `/sitemap.xml`
- **Robots.txt** — Customizable robots.txt at `/robots.txt`
- **SEO Analysis** — Real-time scoring (0-100) with actionable suggestions
- **Google Preview** — Live preview of search result appearance in editor
- **Debug Tools** — Add `?seo_debug=1` to any page URL to inspect SEO output (admin only)

### Anvil Block Editor

A powerful block-based content editor with 21 block types, now available as a bundled plugin:

- **Text**: Paragraph, Heading, List, Quote, Code, Table
- **Media**: Image, Gallery, Video
- **Layout**: Columns, Spacer, Separator, Button, Accordion, Alert, Card
- **Content**: Testimonial, Icon Box, Social Links, HTML, Embed

Features include drag-and-drop reordering, inline settings panel, undo/redo (50 levels), and media library integration.

#### Block Style Variants
Each block supports multiple style variants through the settings panel:
- **Button**: Primary, Secondary, Outline, Ghost
- **Alert**: Info, Success, Warning, Error
- **Card**: Default, Bordered, Flat
- **Testimonial**: Default, Bordered, Filled, Minimal
- **Accordion**: Default, Bordered, Minimal
- **Icon Box**: Default, Boxed, Bordered
- **Social Links**: Default, Filled, Outline

### Anvil Live Editor

Frontend visual editing with real-time preview:

- Edit content directly on the live site
- Drag and drop blocks with visual indicators
- Inline text editing with rich text toolbar
- Device preview (desktop, tablet, mobile)
- Typography controls (size, weight, line height, spacing)
- Color controls (text, background, links)
- Border and shadow styling
- Background options (solid, gradient, image)
- Entrance animations and hover effects
- Transform controls (rotate, scale, translate, skew)
- Responsive visibility toggles
- Page-level settings (content width, padding, margin)

### Content Management

- **Custom Post Types**: Create unlimited content types with custom fields, icons, and URLs
- **Custom Fields**: 16+ field types including repeaters and groups
- **Taxonomies**: Categories, tags, and custom taxonomies
- **Menu Builder**: Visual drag-and-drop menu management
- **Post Revisions**: Automatic history with compare and restore
- **Media Library**: Grid/list views, folders, drag-and-drop uploads
- **Comments**: Threaded comments with moderation and guest support
- **Bulk Actions**: Multi-select for trash, publish, draft, taxonomy assignment
- **Quick Edit**: Inline editing with AJAX save
- **Scheduled Publishing**: Publish posts at a future date/time

### Administration

- Modern admin interface with dark sidebar
- Customizable color schemes and fonts
- Login screen editor with 80+ settings and 12 presets
- Live CSS editor for admin and frontend
- Role-based users (Admin, Editor, Subscriber)
- 80+ admin icons

### Developer Features

- **Plugin System**: WordPress-style hooks and filters (90+ available)
- **Theme System**: Simple PHP templates with full data access
- **REST API**: Full CRUD with API key authentication
- **Shortcodes**: `[tag]` syntax for dynamic content
- **Auto Updates**: One-click updates with automatic backups

## File Structure

```
voidforge/
├── admin/              # Admin panel pages and assets
│   ├── seo-settings.php    # SEO configuration (5 tabs)
│   ├── seo-test.php        # SEO diagnostic tool
│   └── includes/
│       └── seo-metabox.php # Post editor SEO section
├── includes/           # Core PHP classes
│   ├── seo.php             # SEO class (meta, sitemap, schema)
│   └── *.php               # Core classes (Post, Media, User, Plugin, etc.)
├── themes/             # Theme files
│   └── flavor/         # Default theme
├── plugins/            # Plugin files
│   └── anvil/          # Bundled Anvil block editor plugin
│       ├── includes/   # Block editor classes and 21 block types
│       ├── assets/
│       │   ├── css/
│       │   │   ├── anvil-live.css      # Editor UI styles
│       │   │   └── anvil-frontend.css  # Frontend block styles
│       │   └── js/     # Editor JavaScript modules
│       └── admin/      # Editor UI templates
├── uploads/            # Media uploads
└── docs/               # HTML documentation
```

## Configuration

Database and site settings are in `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_PREFIX', 'vf_');

define('SITE_URL', 'https://yoursite.com');
```

## Theme Development

Themes live in `/themes/your-theme/` and require:

- `theme.json` — Theme metadata
- `style.css` — Theme styles
- `index.php` — Main template
- `header.php` / `footer.php` — Layout partials

Basic template example:

```php
<?php get_header(); ?>

<main>
    <?php if (have_posts()): ?>
        <?php foreach (get_posts() as $post): ?>
            <article>
                <h2><?php echo esc($post['title']); ?></h2>
                <?php echo the_content(); ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
```

See `/docs/theme-development.html` for the complete guide.

## Plugin Development

Plugins live in `/plugins/your-plugin/` with a main PHP file:

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: What it does
 * Version: 1.0.0
 */

// Hook into initialization
add_action('init', function() {
    // Your code here
});

// Modify content
add_filter('the_content', function($content) {
    return $content . '<p>Added by plugin!</p>';
});

// Register a shortcode
add_shortcode('hello', function($atts) {
    $name = $atts['name'] ?? 'World';
    return "<p>Hello, {$name}!</p>";
});
```

See `/docs/plugin-development.html` for the complete guide.

## REST API

API endpoints are available at `/api/v1/`:

- `GET /posts` — List posts
- `GET /posts/{id}` — Get single post
- `POST /posts` — Create post
- `PUT /posts/{id}` — Update post
- `DELETE /posts/{id}` — Delete post

Same pattern for `/pages`, `/media`, `/users`, `/taxonomies`.

Authentication via API key header:
```
X-API-Key: your-api-key
```

Manage API keys at Admin → Settings → API Keys.

See `/docs/rest-api.html` for the complete reference.

## Updating

### Automatic Update

1. Go to Admin → Updates
2. Upload the new version ZIP
3. Click Install Update

VoidForge will backup your installation, extract new files, preserve your config/uploads/customizations, and run migrations.

### Manual Update

1. Backup your installation
2. Replace all files except:
   - `includes/config.php`
   - `uploads/` directory
   - Custom themes and plugins
3. Visit the admin panel to run migrations

## Documentation

- `/docs/plugin-development.html` — Complete plugin development guide
- `/docs/theme-development.html` — Theme creation guide
- `/docs/rest-api.html` — REST API reference

## License

MIT License — see LICENSE file for details.

---

**VoidForge CMS** — Modern Content Management
