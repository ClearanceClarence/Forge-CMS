<?php
/**
 * Home Page Template
 * 
 * @package Flavor
 * @version 2.1.0
 */

get_header();

// Get recent posts
$recentPosts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 3,
    'orderby' => 'created_at',
    'order' => 'DESC'
]);
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-decoration">
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>
        <div class="hero-blob hero-blob-3"></div>
        <div class="hero-grid-pattern"></div>
    </div>
    
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="badge-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                </span>
                <span>VoidForge CMS v<?= CMS_VERSION ?></span>
                <span class="badge-new">New</span>
            </div>
            
            <h1 class="hero-title">
                The CMS that gets<br>
                <span class="gradient-text">out of your way</span>
            </h1>
            
            <p class="hero-description">
                A lightweight, blazing-fast content management system for creators who value simplicity. 
                No bloat, no complexity—just pure performance and complete control.
            </p>
            
            <div class="hero-actions">
                <a href="<?php echo site_url('/admin'); ?>" class="btn btn-primary btn-xl">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    Open Dashboard
                </a>
                <a href="#features" class="btn btn-secondary btn-xl">
                    Explore Features
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        
        <div class="hero-stats">
            <div class="hero-stat">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value">&lt;50ms</span>
                    <span class="stat-label">Page Load</span>
                </div>
            </div>
            <div class="hero-stat">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value">~400KB</span>
                    <span class="stat-label">Total Size</span>
                </div>
            </div>
            <div class="hero-stat">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value">21+</span>
                    <span class="stat-label">Block Types</span>
                </div>
            </div>
            <div class="hero-stat">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <span class="stat-value">100%</span>
                    <span class="stat-label">Open Source</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section" id="features">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Features</span>
            <h2 class="section-title">Everything you need to build amazing websites</h2>
            <p class="section-subtitle">Powerful tools designed with simplicity in mind. Build faster, ship sooner.</p>
        </div>
        
        <div class="features-grid">
            <!-- Feature 1: Visual Editor -->
            <div class="feature-card feature-primary">
                <div class="feature-icon-wrap">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h3 class="feature-title">Anvil Block Editor</h3>
                    <p class="feature-description">21+ customizable blocks with drag-and-drop editing. Headings, images, galleries, accordions, tables, and more—all visually editable.</p>
                    <ul class="feature-list">
                        <li>Drag-and-drop reordering</li>
                        <li>Inline rich text editing</li>
                        <li>Live frontend editing mode</li>
                    </ul>
                </div>
            </div>
            
            <!-- Feature 2: SEO Tools -->
            <div class="feature-card feature-new">
                <span class="feature-badge">New in v0.3</span>
                <div class="feature-icon-wrap">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h3 class="feature-title">Complete SEO Suite</h3>
                    <p class="feature-description">Built-in SEO tools that rival premium plugins. Optimize your content without extra dependencies.</p>
                    <ul class="feature-list">
                        <li>Meta tags & Open Graph</li>
                        <li>JSON-LD structured data</li>
                        <li>XML sitemap generation</li>
                        <li>Real-time SEO analysis</li>
                    </ul>
                </div>
            </div>
            
            <!-- Feature 3: Custom Fields -->
            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h3 class="feature-title">16+ Custom Field Types</h3>
                    <p class="feature-description">Text, media, repeaters, relationships, color pickers, and more. Create any content structure you need.</p>
                </div>
            </div>
            
            <!-- Feature 4: REST API -->
            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h3 class="feature-title">REST API</h3>
                    <p class="feature-description">Full CRUD operations with token authentication and rate limiting. Build headless or integrate with anything.</p>
                </div>
            </div>
            
            <!-- Feature 5: Plugin System -->
            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h3 class="feature-title">Plugin Architecture</h3>
                    <p class="feature-description">WordPress-compatible hooks with 90+ actions and filters. Extend functionality without modifying core files.</p>
                </div>
            </div>
            
            <!-- Feature 6: Media Library -->
            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h3 class="feature-title">Media Library</h3>
                    <p class="feature-description">Organize uploads with folders, drag-and-drop management, automatic thumbnails, and quick editing.</p>
                </div>
            </div>
            
            <!-- Feature 7: Comments -->
            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h3 class="feature-title">Comment System</h3>
                    <p class="feature-description">Built-in threaded comments with moderation, Gravatar support, and spam protection options.</p>
                </div>
            </div>
            
            <!-- Feature 8: Performance -->
            <div class="feature-card">
                <div class="feature-icon-wrap">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </div>
                <div class="feature-content">
                    <h3 class="feature-title">Lightning Fast</h3>
                    <p class="feature-description">Sub-50ms page loads. No bloated frameworks, no unnecessary dependencies. Framework-free PHP for pure speed.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">How It Works</span>
            <h2 class="section-title">Up and running in minutes</h2>
        </div>
        
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>
                <h3 class="step-title">Upload & Install</h3>
                <p class="step-description">Drop files on your server and run the one-click installer. Database configured automatically.</p>
            </div>
            
            <div class="step-connector"></div>
            
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </div>
                <h3 class="step-title">Create Content</h3>
                <p class="step-description">Use the visual editor to craft pages and posts. Drag blocks, add media, configure SEO.</p>
            </div>
            
            <div class="step-connector"></div>
            
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
                <h3 class="step-title">Publish</h3>
                <p class="step-description">Hit publish and your content is live. Fast, secure, and optimized for search engines.</p>
            </div>
        </div>
    </div>
</section>

<!-- Code Section -->
<section class="code-section">
    <div class="container">
        <div class="code-grid">
            <div class="code-content">
                <span class="section-label">Developer Friendly</span>
                <h2 class="section-title">Simple theming.<br>Zero magic.</h2>
                <p class="code-description">
                    No proprietary template language to learn. Just PHP, HTML, and CSS—the tools you already know. 
                    Create custom themes in minutes, not days.
                </p>
                
                <ul class="code-features">
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Standard PHP templates
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        WordPress-style functions
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        No build step required
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Full documentation included
                    </li>
                </ul>
                
                <a href="<?php echo site_url(); ?>/docs/theme-development.html" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                    Read Documentation
                </a>
            </div>
            
            <div class="code-showcase">
                <div class="code-window">
                    <div class="code-header">
                        <div class="code-dots">
                            <span></span><span></span><span></span>
                        </div>
                        <span class="code-filename">single.php</span>
                    </div>
                    <pre class="code-block"><code><span class="c-php">&lt;?php</span> <span class="c-fn">get_header</span>(); <span class="c-php">?&gt;</span>

<span class="c-tag">&lt;article</span> <span class="c-attr">class</span>=<span class="c-str">"post"</span><span class="c-tag">&gt;</span>
    <span class="c-tag">&lt;h1&gt;</span><span class="c-php">&lt;?=</span> <span class="c-fn">esc</span>(<span class="c-var">$post</span>[<span class="c-str">'title'</span>]) <span class="c-php">?&gt;</span><span class="c-tag">&lt;/h1&gt;</span>
    
    <span class="c-tag">&lt;div</span> <span class="c-attr">class</span>=<span class="c-str">"content"</span><span class="c-tag">&gt;</span>
        <span class="c-php">&lt;?php</span> <span class="c-fn">the_content</span>(); <span class="c-php">?&gt;</span>
    <span class="c-tag">&lt;/div&gt;</span>
<span class="c-tag">&lt;/article&gt;</span>

<span class="c-php">&lt;?php</span> <span class="c-fn">get_footer</span>(); <span class="c-php">?&gt;</span></code></pre>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Comparison Section -->
<section class="comparison-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Why VoidForge</span>
            <h2 class="section-title">The lightweight alternative</h2>
            <p class="section-subtitle">See how VoidForge compares to traditional content management systems.</p>
        </div>
        
        <div class="comparison-table">
            <div class="comparison-header">
                <div class="comparison-cell"></div>
                <div class="comparison-cell highlight">VoidForge</div>
                <div class="comparison-cell">Traditional CMS</div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Install Size</div>
                <div class="comparison-cell highlight"><span class="badge-good">~400 KB</span></div>
                <div class="comparison-cell"><span class="badge-neutral">50+ MB</span></div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Page Load</div>
                <div class="comparison-cell highlight"><span class="badge-good">&lt;50ms</span></div>
                <div class="comparison-cell"><span class="badge-neutral">200-500ms</span></div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Dependencies</div>
                <div class="comparison-cell highlight"><span class="badge-good">PHP + MySQL</span></div>
                <div class="comparison-cell"><span class="badge-neutral">Multiple</span></div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Learning Curve</div>
                <div class="comparison-cell highlight"><span class="badge-good">Minutes</span></div>
                <div class="comparison-cell"><span class="badge-neutral">Days/Weeks</span></div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">SEO Tools</div>
                <div class="comparison-cell highlight"><span class="badge-good">Built-in</span></div>
                <div class="comparison-cell"><span class="badge-neutral">Plugin Required</span></div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Cost</div>
                <div class="comparison-cell highlight"><span class="badge-good">Free Forever</span></div>
                <div class="comparison-cell"><span class="badge-neutral">Free / Paid</span></div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Posts Section -->
<?php if (!empty($recentPosts)): ?>
<section class="posts-section">
    <div class="container">
        <div class="section-header-row">
            <div>
                <span class="section-label">Latest Posts</span>
                <h2 class="section-title">From the blog</h2>
            </div>
            <a href="<?php echo site_url('/blog'); ?>" class="btn btn-outline">
                View All Posts
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        
        <div class="posts-grid">
            <?php foreach ($recentPosts as $post): 
                $author = User::find($post['author_id']);
                $thumbnail = Post::featuredImage($post);
            ?>
            <article class="post-card">
                <div class="post-image">
                    <a href="<?php echo Post::permalink($post); ?>">
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo esc($thumbnail); ?>" alt="<?php echo esc($post['title']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="post-placeholder">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="post-content">
                    <div class="post-meta">
                        <time datetime="<?php echo $post['created_at']; ?>">
                            <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                        </time>
                        <?php if ($author): ?>
                        <span>&middot;</span>
                        <span><?php echo esc($author['display_name'] ?? $author['username']); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="post-title">
                        <a href="<?php echo Post::permalink($post); ?>">
                            <?php echo esc($post['title']); ?>
                        </a>
                    </h3>
                    <p class="post-excerpt"><?php echo esc(flavor_excerpt($post, 120)); ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">FAQ</span>
            <h2 class="section-title">Common questions</h2>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <h3 class="faq-question">Is VoidForge really free?</h3>
                <p class="faq-answer">Yes, 100% free and open source under the MIT license. Use it for personal or commercial projects without any restrictions.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">What are the server requirements?</h3>
                <p class="faq-answer">PHP 8.0+, MySQL 5.7+ or MariaDB 10.3+, and a web server (Apache/Nginx). No complex dependencies or build tools needed.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">Can I migrate from WordPress?</h3>
                <p class="faq-answer">Yes! The hook system is WordPress-compatible, making plugin adaptation straightforward. Export your content and rebuild with familiar patterns.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">Do I need SEO plugins?</h3>
                <p class="faq-answer">No—VoidForge v0.3 includes a complete SEO suite with meta tags, Open Graph, JSON-LD schema, sitemaps, and real-time content analysis built-in.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">How do I create custom themes?</h3>
                <p class="faq-answer">Themes are simple PHP templates. If you know HTML and basic PHP, you can build anything. Check our documentation for step-by-step guides.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">Where can I get support?</h3>
                <p class="faq-answer">Check our comprehensive documentation, browse GitHub issues, or reach out to the community. We're always happy to help.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-card">
            <div class="cta-content">
                <h2 class="cta-title">Ready to build something amazing?</h2>
                <p class="cta-description">Start creating with VoidForge today. No credit card required, no strings attached.</p>
                <div class="cta-actions">
                    <a href="<?php echo site_url('/admin'); ?>" class="btn btn-white btn-xl">
                        Get Started Free
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="<?php echo site_url(); ?>/docs" class="btn btn-ghost-white btn-xl">
                        View Documentation
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* ========================================
   Variables & Base
   ======================================== */
:root {
    --home-primary: #6366f1;
    --home-primary-dark: #4f46e5;
    --home-primary-light: #818cf8;
    --home-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
}

/* ========================================
   Hero Section
   ======================================== */
.hero {
    position: relative;
    padding: 8rem 0 6rem;
    overflow: hidden;
    background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
}

.hero-decoration {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.hero-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
}

.hero-blob-1 {
    width: 600px;
    height: 600px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%);
    top: -200px;
    right: -100px;
}

.hero-blob-2 {
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    bottom: -100px;
    left: -100px;
}

.hero-blob-3 {
    width: 300px;
    height: 300px;
    background: rgba(99, 102, 241, 0.08);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.hero-grid-pattern {
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
    background-size: 60px 60px;
}

.hero .container {
    position: relative;
    z-index: 1;
}

.hero-content {
    max-width: 720px;
    margin: 0 auto;
    text-align: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem 0.5rem 0.75rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 100px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.badge-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: var(--home-gradient);
    border-radius: 50%;
    color: #fff;
}

.badge-icon svg {
    width: 12px;
    height: 12px;
}

.badge-new {
    padding: 0.125rem 0.5rem;
    background: #dcfce7;
    color: #16a34a;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 100px;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: #0f172a;
    margin: 0 0 1.5rem;
}

.gradient-text {
    background: var(--home-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-description {
    font-size: 1.25rem;
    line-height: 1.7;
    color: #64748b;
    margin: 0 0 2rem;
}

.hero-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 4rem;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.2s;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.btn-xl {
    padding: 1rem 1.75rem;
    font-size: 1rem;
}

.btn-primary {
    background: var(--home-gradient);
    color: #fff;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
}

.btn-secondary {
    background: #fff;
    color: #334155;
    border: 1.5px solid #e2e8f0;
}

.btn-secondary:hover {
    border-color: var(--home-primary);
    color: var(--home-primary);
}

.btn-outline {
    background: transparent;
    color: #334155;
    border: 1.5px solid #e2e8f0;
    padding: 0.75rem 1.25rem;
    font-size: 0.9375rem;
}

.btn-outline:hover {
    border-color: var(--home-primary);
    color: var(--home-primary);
}

.btn-white {
    background: #fff;
    color: var(--home-primary);
}

.btn-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255,255,255,0.3);
}

.btn-ghost-white {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border: 1.5px solid rgba(255,255,255,0.2);
}

.btn-ghost-white:hover {
    background: rgba(255,255,255,0.2);
}

/* Hero Stats */
.hero-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
    max-width: 800px;
    margin: 0 auto;
}

.hero-stat {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    border-radius: 12px;
    color: var(--home-primary);
    flex-shrink: 0;
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.stat-label {
    font-size: 0.8125rem;
    color: #64748b;
}

/* ========================================
   Section Styles
   ======================================== */
.section-header {
    text-align: center;
    max-width: 640px;
    margin: 0 auto 4rem;
}

.section-label {
    display: inline-block;
    padding: 0.375rem 0.875rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    color: var(--home-primary);
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: 100px;
    margin-bottom: 1rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.2;
    margin: 0 0 1rem;
}

.section-subtitle {
    font-size: 1.125rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

.section-header-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 3rem;
}

.section-header-row .section-label {
    margin-bottom: 0.5rem;
}

.section-header-row .section-title {
    margin: 0;
    font-size: 2rem;
}

/* ========================================
   Features Section
   ======================================== */
.features-section {
    padding: 6rem 0;
    background: #fff;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.feature-card {
    position: relative;
    padding: 2rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    transition: all 0.3s;
}

.feature-card:hover {
    background: #fff;
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 8px 32px rgba(99, 102, 241, 0.1);
    transform: translateY(-4px);
}

.feature-card.feature-primary {
    grid-column: span 2;
    background: linear-gradient(135deg, #f8fafc 0%, rgba(99, 102, 241, 0.05) 100%);
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.feature-card.feature-new {
    grid-column: span 2;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(99, 102, 241, 0.05) 100%);
    border-color: rgba(34, 197, 94, 0.2);
}

.feature-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.625rem;
    background: #dcfce7;
    color: #16a34a;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 100px;
}

.feature-icon-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    background: var(--home-gradient);
    border-radius: 16px;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.25);
}

.feature-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.75rem;
}

.feature-description {
    font-size: 0.9375rem;
    color: #64748b;
    line-height: 1.7;
    margin: 0;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 1rem 0 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.feature-list li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #475569;
}

.feature-list li::before {
    content: '';
    width: 6px;
    height: 6px;
    background: var(--home-primary);
    border-radius: 50%;
}

/* ========================================
   How It Works Section
   ======================================== */
.how-section {
    padding: 6rem 0;
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
}

.steps-grid {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 2rem;
}

.step-card {
    flex: 1;
    max-width: 300px;
    text-align: center;
    padding: 2rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    position: relative;
}

.step-number {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 32px;
    background: var(--home-gradient);
    color: #fff;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.step-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    border-radius: 20px;
    color: var(--home-primary);
    margin: 1rem auto 1.5rem;
}

.step-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.75rem;
}

.step-description {
    font-size: 0.9375rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

.step-connector {
    width: 80px;
    height: 2px;
    background: linear-gradient(90deg, var(--home-primary) 0%, rgba(99, 102, 241, 0.2) 100%);
    margin-top: 4rem;
    border-radius: 2px;
}

/* ========================================
   Code Section
   ======================================== */
.code-section {
    padding: 6rem 0;
    background: #fff;
}

.code-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 4rem;
    align-items: center;
}

.code-content .section-title {
    text-align: left;
}

.code-description {
    font-size: 1.0625rem;
    color: #64748b;
    line-height: 1.7;
    margin: 0 0 2rem;
}

.code-features {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem;
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}

.code-features li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1rem;
    color: #334155;
    font-weight: 500;
}

.code-features li svg {
    color: #22c55e;
    flex-shrink: 0;
}

.code-window {
    background: #1e293b;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

.code-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: #0f172a;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.code-dots {
    display: flex;
    gap: 0.5rem;
}

.code-dots span {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.code-dots span:nth-child(1) { background: #ef4444; }
.code-dots span:nth-child(2) { background: #eab308; }
.code-dots span:nth-child(3) { background: #22c55e; }

.code-filename {
    font-size: 0.8125rem;
    color: #94a3b8;
    font-family: 'JetBrains Mono', monospace;
}

.code-block {
    padding: 1.5rem;
    margin: 0;
    overflow-x: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.8125rem;
    line-height: 1.7;
}

.code-block code {
    color: #e2e8f0;
}

.c-php { color: #f472b6; }
.c-fn { color: #60a5fa; }
.c-tag { color: #f472b6; }
.c-attr { color: #fbbf24; }
.c-str { color: #4ade80; }
.c-var { color: #c4b5fd; }

/* ========================================
   Comparison Section
   ======================================== */
.comparison-section {
    padding: 6rem 0;
    background: #f8fafc;
}

.comparison-table {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
}

.comparison-header,
.comparison-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
}

.comparison-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.comparison-cell {
    padding: 1.25rem 1.5rem;
    font-size: 0.9375rem;
    color: #64748b;
}

.comparison-header .comparison-cell {
    font-weight: 700;
    color: #0f172a;
}

.comparison-cell.label {
    font-weight: 600;
    color: #334155;
}

.comparison-cell.highlight {
    background: rgba(99, 102, 241, 0.04);
}

.comparison-row {
    border-bottom: 1px solid #f1f5f9;
}

.comparison-row:last-child {
    border-bottom: none;
}

.badge-good {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    background: #dcfce7;
    color: #16a34a;
    font-size: 0.8125rem;
    font-weight: 600;
    border-radius: 100px;
}

.badge-neutral {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    background: #f1f5f9;
    color: #64748b;
    font-size: 0.8125rem;
    font-weight: 500;
    border-radius: 100px;
}

/* ========================================
   Posts Section
   ======================================== */
.posts-section {
    padding: 6rem 0;
    background: #fff;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.post-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s;
}

.post-card:hover {
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 8px 32px rgba(0,0,0,0.08);
    transform: translateY(-4px);
}

.post-image {
    aspect-ratio: 16/10;
    overflow: hidden;
    background: #f8fafc;
}

.post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.post-card:hover .post-image img {
    transform: scale(1.05);
}

.post-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
}

.post-content {
    padding: 1.5rem;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #94a3b8;
    margin-bottom: 0.75rem;
}

.post-title {
    font-size: 1.125rem;
    font-weight: 700;
    line-height: 1.4;
    margin: 0 0 0.75rem;
}

.post-title a {
    color: #0f172a;
    text-decoration: none;
}

.post-title a:hover {
    color: var(--home-primary);
}

.post-excerpt {
    font-size: 0.9375rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0;
}

/* ========================================
   FAQ Section
   ======================================== */
.faq-section {
    padding: 6rem 0;
    background: #f8fafc;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    max-width: 900px;
    margin: 0 auto;
}

.faq-item {
    padding: 1.75rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    transition: all 0.2s;
}

.faq-item:hover {
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
}

.faq-question {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.75rem;
}

.faq-answer {
    font-size: 0.9375rem;
    color: #64748b;
    line-height: 1.7;
    margin: 0;
}

/* ========================================
   CTA Section
   ======================================== */
.cta-section {
    padding: 6rem 0;
    background: #fff;
}

.cta-card {
    background: var(--home-gradient);
    border-radius: 24px;
    padding: 4rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.cta-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
    transform: rotate(-30deg);
}

.cta-content {
    position: relative;
    z-index: 1;
}

.cta-title {
    font-size: 2.25rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 1rem;
}

.cta-description {
    font-size: 1.125rem;
    color: rgba(255,255,255,0.85);
    margin: 0 0 2rem;
}

.cta-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

/* ========================================
   Responsive
   ======================================== */
@media (max-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .feature-card.feature-primary,
    .feature-card.feature-new {
        grid-column: span 2;
    }
    
    .code-grid {
        grid-template-columns: 1fr;
        gap: 3rem;
    }
    
    .code-content {
        text-align: center;
    }
    
    .code-content .section-title {
        text-align: center;
    }
    
    .code-features {
        max-width: 300px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .posts-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .hero {
        padding: 5rem 0 4rem;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-description {
        font-size: 1.0625rem;
    }
    
    .hero-actions {
        flex-direction: column;
    }
    
    .hero-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .feature-card.feature-primary,
    .feature-card.feature-new {
        grid-column: span 1;
    }
    
    .steps-grid {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .step-card {
        max-width: 100%;
    }
    
    .step-connector {
        width: 2px;
        height: 40px;
        background: linear-gradient(180deg, var(--home-primary) 0%, rgba(99, 102, 241, 0.2) 100%);
        margin: 0;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .section-header-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
    }
    
    .posts-grid {
        grid-template-columns: 1fr;
    }
    
    .faq-grid {
        grid-template-columns: 1fr;
    }
    
    .comparison-header,
    .comparison-row {
        grid-template-columns: 1.2fr 1fr 1fr;
    }
    
    .comparison-cell {
        padding: 1rem;
        font-size: 0.8125rem;
    }
    
    .cta-card {
        padding: 3rem 2rem;
    }
    
    .cta-title {
        font-size: 1.75rem;
    }
    
    .cta-actions {
        flex-direction: column;
    }
}
</style>

<?php get_footer(); ?>
