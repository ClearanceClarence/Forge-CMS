# Changelog

All notable changes to VoidForge CMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [0.3.2] - 2025-12-29

### 🔒 Security Fixes

#### Environment-Aware Error Reporting
- **Fixed production info leak** — `display_errors` is now off by default on all installs
- **Introduced `CMS_DEBUG` flag** — Controlled via `VOIDFORGE_DEBUG=1` environment variable; no more editing `config.php` to toggle debug mode
- **Error logging enabled in production** — `log_errors = 1` is set when debug is off so errors are still captured server-side
- **Installer-generated configs are production-safe** — Fresh installs no longer expose stack traces

#### Login Rate Limiting
- **Brute-force protection on login form** — After 5 failed attempts the session is locked for 5 minutes
- **Countdown messaging** — Users see how many attempts remain and how long the lockout lasts
- **Auto-reset on success** — Rate limit state is cleared on a successful login
- **CSRF protection on login** — Login form now includes and verifies a CSRF token

#### File Upload MIME Validation
- **Cross-validated extension vs detected MIME** — A file renamed from `.php` to `.jpg` is now rejected; `finfo` magic-byte detection is checked against the expected MIME for the declared extension
- **JPEG alias handling** — Both `image/jpeg` and `image/pjpeg` accepted for `.jpg`/`.jpeg`
- **Loose MIME allowlist for office/zip formats** — `.docx`, `.xlsx`, `.zip`, `.pdf` allow the range of MIME types these formats legitimately produce across platforms

#### Constant Name Cleanup
- **Fixed mismatched salt constants** — `AUTH_SALT`/`SECURE_AUTH_SALT` in the config template were dead (nothing read them); renamed to `AUTH_KEY`/`SECURE_AUTH_KEY` to match what the installer generates
- **Added `HASH_COST` and `SESSION_LIFETIME`** to installer-generated configs (were missing, causing fallback to undefined)

### 📁 Files Modified

```
includes/
├── config.php       # CMS_DEBUG flag, env-aware error reporting, fixed constant names
└── migrations.php   # Version bump to 0.3.2

admin/
└── login.php        # CSRF token + session-based rate limiting

install.php          # Generated config now production-safe, includes all constants

```

---



### 🐛 Bug Fixes

#### Media Library
- **Fixed folder assignment** — Files now correctly save to folders when uploading (was using wrong column name `folder` instead of `folder_id`)
- **Fixed undefined variable** — Resolved `$folders` undefined error in media page header
- **Added quick delete buttons** — Delete button now visible on hover for both grid and list views without needing to open the modal
- **Improved delete UX** — Can delete files directly from the media grid with a confirmation dialog

#### Comments System
- **Fixed comment submission** — Comment form now posts to the correct URL (`/comment-submit` instead of `/comments/submit`)
- **Added comments to pages** — Page template now supports comments (if enabled for pages in Settings → Comments)
- **Added feedback messages** — Success and error messages now display after comment submission

### 🎨 Design Overhaul

#### Complete Home Page Redesign
- **Light mode design** — Clean white/gray gradient background with soft purple accent blobs
- **Subtle grid pattern** — Background texture for visual depth
- **Hero section** — Version badge (v0.3.1), gradient headline, updated stats (21+ blocks, ~400KB)
- **Features grid** — 8 feature cards including new "SEO Suite" with "New in v0.3" badge
- **How it works** — 3-step cards with numbered badges and connectors
- **Code section** — Dark syntax-highlighted code window with developer features checklist
- **Comparison table** — VoidForge vs Traditional CMS with "SEO Tools: Built-in" row
- **Blog section** — Recent posts grid with hover effects
- **FAQ section** — Updated Q&A including SEO plugin question
- **CTA section** — Gradient purple call-to-action card
- **Responsive** — Full mobile optimization with breakpoints

#### Complete Comments Redesign
- **Comments header** — New design with accent bar and gradient count badge
- **Comment cards** — Modern card design with hover effects and proper depth for replies  
- **Avatar styling** — Larger avatars (52px) with subtle shadow and border
- **Time ago format** — Comments now show relative time ("2 hours ago") instead of just date
- **Reply button** — Redesigned with outline style that fills on hover
- **Reply indicator** — Visual indicator when replying to a comment with cancel option
- **Empty state** — Beautiful gradient background with decorative elements and icon
- **Comment form** — Gradient header with icon, improved input styling, animated submit button
- **Form labels** — Added icons to Name, Email, and Comment labels
- **Mobile responsive** — Improved spacing and layout on smaller screens

### 📁 Theme Updates

```
themes/flavor/
├── home.php        # Complete redesign (v2.1.0) - Light mode, updated features
├── single.php      # Comment section redesign
├── page.php        # Comment section added (v2.0.1)
├── functions.php   # Added flavor_time_ago() function
└── style.css       # 200+ lines of new comment styles
```

---

## [0.3.0] - 2025-12-27

### 🔍 SEO Tools

Comprehensive SEO system for optimizing your content for search engines and social media.

---

### ✨ New Features

#### SEO Class (`/includes/seo.php`)
- **Complete SEO management** — New `SEO` class handles all SEO functionality
- **Meta tag generation** — Automatic meta description, keywords, and robots tags
- **Open Graph support** — Full Facebook/social sharing optimization
- **Twitter Cards** — Summary and large image card types
- **JSON-LD Schema** — Structured data for rich search results
- **XML Sitemap** — Automatic sitemap generation at `/sitemap.xml`
- **Robots.txt** — Customizable robots.txt at `/robots.txt`
- **SEO Analysis** — Real-time content scoring with actionable suggestions

#### Post Editor SEO Meta Box
- **Google Preview** — Live preview of search result appearance
- **Focus Keyword** — Target keyword tracking and analysis
- **SEO Title** — Custom title with character counter (60 char limit)
- **Meta Description** — Custom description with character counter (160 char limit)
- **Canonical URL** — Override canonical URLs when needed
- **Robots Control** — Per-page index/noindex and follow/nofollow
- **Open Graph Override** — Custom OG title, description, and image
- **Twitter Override** — Custom Twitter card content
- **SEO Score** — Visual score indicator (0-100) with color coding

#### SEO Settings Page (`/admin/seo-settings.php`)
- **General Tab** — Title separator, format, homepage SEO, search visibility
- **Social Media Tab** — Default OG image, Twitter card type, site username
- **Schema Tab** — Organization/Person type, name, logo for JSON-LD
- **Sitemap Tab** — Enable/disable, post type selection, taxonomy inclusion
- **Robots.txt Tab** — Custom robots.txt editor with preview

---

### 🔧 Technical Details

#### SEO Meta Storage
Uses existing `postmeta` table with prefixed keys:
- `_seo_title` — Custom page title
- `_seo_description` — Meta description
- `_seo_keywords` — Meta keywords
- `_seo_canonical` — Canonical URL override
- `_seo_robots_index` — Index/noindex setting
- `_seo_robots_follow` — Follow/nofollow setting
- `_seo_og_title` — Open Graph title
- `_seo_og_description` — Open Graph description
- `_seo_og_image` — Open Graph image ID
- `_seo_focus_keyword` — Target keyword for analysis

#### Global SEO Options
- `seo_title_separator` — Character between title and site name
- `seo_title_format` — Post Title | Site Name or Site Name | Post Title
- `seo_home_title` — Custom homepage title
- `seo_home_description` — Homepage meta description
- `seo_og_default_image` — Default image for social sharing
- `seo_twitter_card_type` — Summary or summary_large_image
- `seo_twitter_site` — Twitter @username
- `seo_schema_org_type` — Organization, Person, LocalBusiness
- `seo_sitemap_enabled` — Enable/disable XML sitemap
- `seo_sitemap_post_types` — Post types to include in sitemap
- `seo_robots_txt` — Custom robots.txt content

#### JSON-LD Schema Types
- **WebSite** — Site-wide with SearchAction
- **Organization/Person** — Based on schema settings
- **Article** — For blog posts with author, dates, publisher
- **BreadcrumbList** — Navigation path for all content

#### SEO Debug Tools
- **Debug Panel** — Add `?seo_debug=1` to any frontend URL to see SEO data overlay (admin only)
- **SEO Test Page** — `/admin/seo-test.php` for comprehensive SEO analysis of any page

---

### 📁 New Files

```
includes/
└── seo.php                    # Core SEO class (800+ lines)

admin/
├── seo-settings.php           # SEO settings page (5 tabs)
├── seo-test.php               # SEO diagnostic tool
└── includes/
    └── seo-metabox.php        # Post editor SEO section
```

---

### 📝 Modified Files

```
includes/
├── config.php                 # Version updated to 0.3.0
├── functions.php              # get_page_title() now uses SEO class
├── migrations.php             # Added v0.3.0 SEO defaults

admin/
├── post-edit.php              # Added SEO meta box integration
└── includes/
    └── sidebar.php            # Added SEO settings menu item

themes/flavor/
└── header.php                 # Removed static meta description (now via vf_head hook)

index.php                      # Added sitemap.xml and robots.txt routes
```

---

### 🎯 SEO Analysis Checks

The SEO analyzer evaluates content based on:
- **Title presence and length** — Required, under 60 characters
- **Meta description** — Recommended 120-160 characters
- **Content length** — Minimum 300 words recommended
- **Focus keyword usage** — In title, description, content, URL
- **Keyword density** — Optimal range 0.5-3%
- **Featured image** — Recommended for all content

Score ranges:
- **80-100** — Good (green)
- **50-79** — Needs improvement (orange)
- **0-49** — Poor (red)

---

## [0.2.5] - 2025-12-23

### 🔌 Anvil Block Editor Plugin

The Anvil block editor and Anvil Live frontend editor have been moved from core to a bundled plugin. This architectural change improves maintainability, reduces core footprint, and allows the block editor to be updated independently.

---

### ✨ New Plugin Architecture

#### Anvil Plugin (`/plugins/anvil/`)
- **Self-contained plugin** — All Anvil functionality is now modular
- **21 block types** — Paragraph, Heading, List, Quote, Code, Table, Image, Gallery, Video, Columns, Spacer, Separator, Button, Accordion, Alert, Card, Testimonial, Icon Box, Social Links, HTML, Embed
- **Frontend visual editor** — Anvil Live for drag-and-drop editing on the live site
- **Plugin constants** — `ANVIL_PATH` and `ANVIL_URL` for easy path resolution
- **Template helper functions** — `get_anvil_live_edit_url()`, `is_anvil_live_editing()`, `anvil_live_backend_button()`, `anvil_live_edit_bar()`, `anvil_wrap_content_with_page_settings()`

#### Plugin File Structure
```
plugins/anvil/
├── anvil.php              # Main plugin file with hooks
├── includes/
│   ├── class-anvil.php    # Block editor class
│   ├── class-anvil-live.php # Frontend editor class
│   ├── AnvilBlock.php     # Base block class
│   └── blocks/            # 21 block type classes
├── assets/
│   ├── css/
│   │   ├── anvil-live.css     # Editor UI styles
│   │   └── anvil-frontend.css # Frontend block styles (NEW)
│   └── js/                # Editor JavaScript + modules
└── admin/
    └── editor-ui.php      # Editor UI template
```

---

### 🎨 Frontend Block Styles

**New comprehensive CSS file** (`anvil-frontend.css`) for styling all 21 block types on the frontend.

#### All 21 Blocks Styled
- **Paragraph** — Line height, size variants (small/large), drop cap support
- **Heading** — Font weights, margin, heading levels h1-h6
- **Image** — Border radius, captions, alignment (left/center/right float)
- **Video** — Responsive 16:9 wrapper, captions
- **Button** — 4 styles (primary, secondary, outline, ghost) with hover states
- **List** — Ordered/unordered, custom marker colors
- **Quote** — Left border accent, citation styling, background
- **Code** — Dark theme, monospace font, syntax colors
- **Separator** — Solid/dotted/dashed, width variants
- **Spacer** — Height controlled via inline style
- **Columns** — CSS Grid with gap, responsive stacking
- **Card** — Shadow, hover lift, image cover, style variants (bordered/flat)
- **Alert** — 4 types (info/success/warning/error) with icons and colors
- **Table** — Header styling, striped/bordered variants
- **Accordion** — HTML5 `<details>/<summary>`, open state icon rotation, style variants
- **Testimonial** — 4 styles (default/bordered/filled/minimal), star ratings, avatar placeholder
- **Gallery** — Grid layout, hover zoom, responsive columns
- **Icon Box** — Icon background, alignment, style variants (boxed/bordered)
- **Social Links** — Circular icons, size/style variants, brand color on hover
- **Embed** — Responsive iframe wrapper
- **HTML** — Clean container styling

#### CSS Loading
- **Theme integration** — CSS loaded via `header.php` before content renders
- **Automatic detection** — Checks `ANVIL_URL` constant, falls back to file path
- **No hook dependency** — Removed unreliable `vf_head` hook approach

---

### 🔧 Bug Fixes

#### Frontend CSS Not Loading
- **Problem**: Anvil blocks rendered unstyled on frontend pages
- **Cause**: `vf_head` hook timing issues prevented CSS from loading
- **Solution**: Direct CSS loading in theme `header.php` with plugin detection

#### Testimonial Block Styles
- **Problem**: Style dropdown (default/bordered/filled/minimal) had no effect
- **Solution**: Added all 4 style variant CSS rules:
  - `.anvil-testimonial--default` — Light gray background
  - `.anvil-testimonial--bordered` — White with border
  - `.anvil-testimonial--filled` — Purple gradient with white text
  - `.anvil-testimonial--minimal` — Transparent, no padding

#### Accordion HTML5 Support
- Fixed styles for native `<details>/<summary>` elements
- Added `::-webkit-details-marker { display: none }` to hide default arrow
- Icon rotation on `[open]` attribute instead of `.active` class

#### Missing CSS Classes
- Added `.anvil-testimonial-meta` for role/company text
- Added `.anvil-testimonial-avatar--placeholder` for initials display
- Added `.anvil-star` and `.anvil-star--empty` for ratings
- Added alignment classes (`.aligncenter`, `.alignleft`, `.alignright`)

---

### 🗑️ Removed

- **hello-world plugin** — Removed the example plugin from bundled plugins
- **Core Anvil files** — Moved from `/includes/anvil/` and `/includes/anvil-live/` to plugin

---

### 🔧 Core Changes

#### Graceful Degradation
- Core now checks `class_exists('Anvil')` before using block editor features
- Core now checks `class_exists('AnvilLive')` before using frontend editor features
- Content displays as raw text if Anvil plugin is deactivated
- Admin post editor hides Anvil CTA when plugin is inactive

#### Migration
- Automatic migration adds `anvil` to active plugins on update
- Automatic migration removes `hello-world` from active plugins

---

### 📁 Files Modified

```
includes/
├── config.php         # Version updated to 0.2.5
├── functions.php      # Removed Anvil helper functions (now in plugin)
├── migrations.php     # Added v0.2.5 migration for plugin activation
└── (removed anvil.php, anvil-live.php, anvil/, anvil-live/)

admin/
└── post-edit.php      # Made Anvil features conditional

index.php              # Removed Anvil requires and initialization

themes/flavor/
└── header.php         # Added Anvil CSS loading with plugin detection

plugins/
├── (removed hello-world/)
└── anvil/
    └── assets/css/
        └── anvil-frontend.css  # NEW: Complete frontend block styles
```

---

## [0.2.4.1] - 2025-12-21

### 🎨 Flavor Theme Redesign

Complete overhaul of the Flavor theme's header, footer, home page, and 404 page with modern design patterns and improved user experience.

---

### 🏠 Home Page Redesign

A completely new home page with engaging visuals and useful content sections.

#### Hero Section
- **Animated gradient background** — Glowing orbs and grid pattern
- **Pulsing badge** — "Introducing VoidForge CMS" with animated dot
- **Gradient animated text** — Shimmer effect on "stand out"
- **Stats bar** — <50ms page load, ~350KB size, 100% open source
- **Browser mockup** — Animated admin interface preview
- **Dual CTAs** — "Start Building Free" + "Watch Demo" buttons

#### Technology Logos
- PHP, Open Source, MySQL, JavaScript, CSS3 icons
- "Built with technologies you trust" label

#### Features Grid
- **Large featured card** — Visual block editor demo with animated blocks
- **5 feature cards** — Block Editor, Live Editing, Custom Fields, REST API, Plugin System, Lightning Fast
- **Hover effects** — Purple border glow on interaction

#### How It Works
- **3-step visual flow** — Upload & Install → Create Content → Publish & Share
- Numbered steps with connecting arrows
- Icon illustrations for each step

#### Code Showcase (replaces testimonials)
- **Tabbed code editor** — Dark theme with syntax highlighting
- **3 code examples**: single.php, loop example, custom fields
- **Feature checklist** — Standard PHP templates, WordPress-style functions, No build step
- **"Read the Docs" CTA**

#### Comparison Table
- **VoidForge vs Traditional CMS**
- 6 comparison points with highlighted advantages
- Install Size, Page Load, Dependencies, Learning Curve, Template Language, Cost

#### FAQ Section
- 6 common questions with answers
- 2-column responsive grid

---

### 📄 Header Redesign

Modern header with search, mobile menu, and call-to-action button.

#### New Features
- **Search overlay** — Full-screen modal with blur backdrop
- **CTA button** — "Get Started" with gradient and arrow animation (desktop)
- **Animated hamburger** — 3-line icon transforms to X when open
- **Mobile menu** — Slides from right with touch-friendly links

#### Improvements
- Centered navigation with balanced layout
- Active nav items have purple background highlight
- Improved glassmorphism on scroll
- Keyboard support (Escape to close search)

---

### 🦶 Footer Redesign

Professional footer with pre-footer CTA, newsletter signup, and better organization.

#### Pre-Footer CTA
- Full-width gradient section with pattern overlay
- "Ready to get started?" messaging
- Action button

#### Main Footer
- **4-column grid** — Brand, Navigation, Resources, Newsletter
- **Newsletter signup** — Email input with arrow button
- **Social icons** — X/Twitter, GitHub, LinkedIn, YouTube (filled icons)
- Proper heading hierarchy

#### Footer Bottom
- Copyright + Legal links (Privacy, Terms, Cookies)
- Divider dots between links
- Responsive stacking

---

### ❌ 404 Page Redesign

Engaging error page with animation and helpful guidance.

#### Visual Design
- **Animated SVG illustration** — Large "404" with floating shapes
- **Pulsing background circles** — Depth effect
- **Twinkling sparkles** — Star animations
- **Floating geometric shapes** — Circles and squares with float animation
- **Broken link animation** — Gap in chain visual

#### Content
- "Page Not Found" heading with gradient
- Friendly error message
- **Two action buttons** — "Go Home" (primary) + "Go Back" (outline)
- **Suggestions card** — Helpful tips for users

---

### 🔧 Bug Fixes

- **Admin Bar Z-Index** — Sticky headers now properly offset by 32px when admin bar is active. Added CSS rules for `.site-header`, `header.sticky`, and inline sticky elements
- **Footer Logo Consistency** — Footer was using old hardcoded SVG (lines icon). Now uses same logo logic as header with `has_site_logo()` check, displaying custom uploaded logo or default stacked layers icon with "VoidForge" text
- **Nested Block Settings** — Fixed settings panel not showing for blocks inside columns (z-index/overflow issues)

---

### 🎨 CSS Enhancements

#### New Component Styles
- **Pre-footer CTA** — Full-width gradient section with pattern background overlay
- **Newsletter form** — Dark input with focus states and arrow submit button
- **Social links** — 36px circular icons with hover lift effect
- **Search overlay** — Full-screen blur backdrop with centered search form
- **Mobile menu** — Slide-in panel with touch-friendly navigation
- **Code showcase** — Tabbed interface with syntax-highlighted code blocks
- **Comparison table** — Responsive grid with highlighted column

#### Animation Keyframes
- `glow-pulse` — Glowing orb effect for hero background
- `pulse` — Badge dot animation
- `gradient-shift` — Animated gradient text
- `float` — Floating geometric shapes
- `twinkle` — Sparkling star effect
- `broken-link` — Animated broken chain

#### Responsive Breakpoints
- 1024px — Code section grid stacks, feature cards 2-column
- 768px — Mobile menu activates, hero stats stack, comparison table compact
- 640px — Footer columns stack, FAQ single column

---

### 🖼️ Default Branding Updates

- **Header logo** — Stacked layers icon with gradient + "VoidForge" text (dark "Void", purple gradient "Forge")
- **Footer logo** — Same stacked layers icon with white "Void" text for dark background
- **Consistent iconography** — Both header and footer use matching layered diamond design

---

### 📝 Blog Section

- **Section header row** — Title left-aligned with "View All" button right-aligned
- **3-column post grid** — Responsive cards with image, meta, title, excerpt
- **Image hover zoom** — 1.05 scale on card hover
- **Placeholder images** — SVG icon for posts without featured images
- **Read article links** — Arrow icon with hover animation

---

### 🔌 JavaScript Additions

- **Header interactions** — Scroll effect, mobile menu toggle, search overlay open/close
- **Code tabs** — Tab switching for code showcase panels
- **Keyboard support** — Escape key closes search overlay
- **Admin bar dropdowns** — Hover and keyboard accessible dropdown menus

---

### ✨ New Features

- **Frontend Admin Bar** — WordPress-style admin bar for logged-in users with quick access to Dashboard, New content dropdown, Edit current page, Comments (with pending badge), and user profile/logout
- **Site Identity Settings** — New customizer tab for setting site logo and favicon with media library integration, dimension controls, and live preview
- **Anvil Live Page Settings** — New Page tab with content width, padding, and margin controls
- **Preview Button** — View content without editor UI, auto-saves before previewing
- **Per-Post Comments Toggle** — Enable/disable comments on individual posts
- **Theme Entry Header Controls** — Show/hide entry title and meta in theme settings

---

### 🔧 Improvements

- **Modular JS Architecture** — Started splitting anvil-live.js into logical modules for maintainability
- **Theme Settings** — Added "Show Entry Title" and "Show Entry Meta" toggles to Flavor theme
- **Admin Bar Replaces Floating Edit Bar** — The floating Anvil Live edit button is now hidden when admin bar is active to avoid redundancy
- **Logo/Favicon Helper Functions** — Updated `get_site_logo()`, `get_site_favicon()`, `the_site_logo()`, `the_favicon()`, `has_site_logo()` to support URL-based storage with dimension settings

---

### 📁 Files Modified

```
themes/flavor/
├── home.php           # Complete redesign with 8 sections
├── header.php         # Search overlay, mobile menu, CTA button
├── footer.php         # Pre-footer CTA, newsletter, 4-column layout
├── style.css          # New header/footer/home page styles
├── 404.php            # Animated SVG illustration
├── functions.php      # Updated comment rendering

includes/
├── functions.php      # Admin bar system, logo/favicon helpers, sticky header offset
├── anvil-live.php     # Page settings tab, preview functionality

admin/
├── customize.php      # Site Identity tab with logo/favicon upload
├── post-edit.php      # Per-post comments toggle
├── theme-settings.php # Entry header visibility controls
```

---

## [0.2.4] - 2025-12-20

### ✨ New Features

- **Anvil Live Enhancements** — Various improvements to the visual frontend editor

---

## [0.2.3.1] - 2025-12-19

### 💬 Comments System Improvements

Major improvements to the comments system including orphaned comment cleanup, frontend redesign, and cascade deletion.

---

### 🧹 Orphaned Comments Cleanup

New feature to detect and remove comments attached to permanently deleted posts.

#### Admin UI
- **"Clean Up Orphans" Button** — Appears on Comments page when orphans exist
- **Count Badge** — Shows number of orphaned comments detected
- **Confirmation Modal** — Warning dialog before permanent deletion
- **Direct SQL Detection** — Reliable detection across all existing comments

#### Implementation
- Uses `NOT EXISTS` subquery for reliable orphan detection
- Handles comments from posts deleted before this update
- Added `Comment::deleteByPost()` method for targeted deletion

---

### 🔗 Comment Cascade Delete

Comments are now automatically deleted when their parent post is permanently deleted.

#### Changes
- **Post::delete()** — Now calls `Comment::deleteByPost()` during permanent deletion
- **Comment class include** — Added to `admin/posts.php` for class availability
- Only triggers on permanent deletion, not when moving to trash

---

### 🎨 Frontend Comments Redesign

Complete visual overhaul of the public-facing comments section.

#### Comment Display
- **Card-based design** — Modern cards with hover effects
- **Larger avatars** — 52px with accent border on hover
- **Clock icon** — Next to comment dates for visual clarity
- **Styled reply buttons** — Icon + text with border, fills on hover
- **Nested replies** — Left border accent indicator with proper indentation
- **Responsive layout** — Collapses gracefully on mobile

#### Comment Form
- **Gradient icon header** — Purple gradient circular icon with chat bubble
- **Two-column layout** — Name and email fields side by side
- **Enhanced inputs** — Focus states with accent border and glow
- **Gradient submit button** — Purple gradient with hover lift effect
- **Logged-in state** — Shows user avatar and name when authenticated
- **Improved alerts** — Error/success messages with icons

#### Empty State
- **Friendly messaging** — "No comments yet" with gradient icon bubble
- **Call to action** — "Be the first to share your thoughts!"

---

### 🎨 Styled Error Pages

Beautiful, branded error pages for database issues.

#### Setup Required Page
- **Dark gradient background** — Matches VoidForge theme
- **Glassmorphic card** — Blur effect with subtle border
- **Purple gradient logo** — Layered box icon
- **Yellow warning status** — Clear "Setup Required" messaging
- **Dynamic installer link** — Works from any directory (admin, subdirectories)

#### Database Error Page
- **Same dark theme styling** — Consistent branding
- **Red error icon** — Circular X indicator
- **Monospace error details** — Easy to read error messages
- **Help text** — Troubleshooting suggestions
- **Proper HTTP codes** — 503 for setup, 500 for errors

---

### 🚀 Installer Redesign

Modern step indicator for the installation wizard.

#### New Step Indicators
- **Circular step numbers** — 48px circles with numbers
- **Connecting lines** — Fill with gradient as steps complete
- **Labels below circles** — Uppercase with letter spacing
- **Checkmark SVG** — Replaces number when step completes

#### Visual States
| State | Appearance |
|-------|------------|
| **Upcoming** | Gray circle with border, muted label |
| **Active** | Purple gradient with glow + pulse animation |
| **Completed** | Green circle with checkmark, filled connector |

#### Other Fixes
- **Removed debug output** — No more `error_reporting` warnings
- **Fixed constant order** — Prevents "already defined" errors
- **Updated version** — Fallback version now 0.2.3.1

---

### 🎨 Dashboard Theme Colors

Dashboard now fully respects admin theme color customization.

#### Updated Elements
| Element | CSS Variable |
|---------|--------------|
| **Hero gradient** | `--forge-primary`, `--forge-secondary` |
| **Hero buttons** | `--forge-primary`, `--forge-primary-dark` |
| **Stat numbers** | `--forge-primary` |
| **Quick action icons** | `--forge-primary`, `--forge-secondary` gradient |
| **Comment avatars** | `--forge-primary`, `--forge-secondary` gradient |
| **Version badge** | `--forge-primary`, `--forge-secondary` gradient |
| **Activity icons** | `--forge-primary` with color-mix |

#### Empty State Icons
- **Gradient circular background** — 72px with theme colors
- **White icons** — 32px centered in gradient circle
- **Used for** — "No posts yet" and "No pages yet" states

---

### 📁 Files Modified

```
includes/
├── database.php        # Styled error pages, dynamic installer links
├── post.php            # Comment cascade delete
├── comment.php         # deleteByPost() method

admin/
├── index.php           # Theme color variables, empty state redesign
├── posts.php           # Comment class include
├── comments.php        # Orphan cleanup UI and logic

themes/flavor/
├── single.php          # Frontend comments redesign
├── functions.php       # Updated flavor_render_comment()

install.php             # Step indicator redesign, removed debug output
```

---

## [0.2.3] - 2025-12-19

### 🎨 Elementor-Style Visual Editor

A major upgrade to the Anvil Live visual editor with comprehensive Elementor-style customization controls, a modern color picker, and professional dark theme UI.

---

### 🌙 Dark Theme Sidebar

Completely redesigned the Anvil Live sidebar with an Elementor-inspired dark theme for better contrast and visual hierarchy.

#### Sidebar Styling
- **Dark Background** — `#23272e` main background with `#1a1d21` borders
- **Light Text** — `#e0e0e0` text color for optimal readability on dark backgrounds
- **Purple Accents** — `#a78bfa` accent color for active states, buttons, and highlights
- **Tab Bar** — Dark `#1a1d21` background with bottom border separator

#### Input Styling
- **Dark Inputs** — `#2c313a` background with `#3f4451` borders
- **Focus States** — `#a78bfa` purple border with subtle glow on focus
- **Muted Placeholders** — `#6b7280` placeholder text color
- **Dark Dropdowns** — Select elements styled to match dark theme

#### Block Items
- **Hover Effects** — Purple border with `rgba(167, 139, 250, 0.15)` background on hover
- **Purple Icons** — Block category icons in accent purple
- **Consistent Cards** — `#2c313a` background with subtle borders

---

### 📑 Tabbed Settings Panel

Reorganized block settings into three intuitive tabs following Elementor's proven UX pattern.

#### Tab Structure
| Tab | Icon | Contents |
|-----|------|----------|
| **Content** | Document icon | Block-specific content attributes, accordion items editor |
| **Style** | Paint drop icon | Typography, colors, borders, shadows, backgrounds |
| **Advanced** | Gear icon | Spacing, sizing, responsive visibility, animations, transforms, custom attributes |

#### Tab Features
- **Icon + Label** — Each tab displays an SVG icon with text label
- **Active State** — Purple highlight with bottom border indicator
- **Smooth Transitions** — Fade between tab sections
- **Section Headers** — Collapsible sections with icons and uppercase labels
- **Empty States** — Helpful messages when no settings are available for a category

---

### ✍️ Typography Controls

Complete typography customization for any block, matching professional page builder capabilities.

#### Font Properties
| Property | Options | Description |
|----------|---------|-------------|
| **Font Size** | Number + unit (px/em/rem/%) | Control text size with flexible units |
| **Font Weight** | 300-800 (Light to Extra Bold) | 7 weight options including medium and semi-bold |
| **Line Height** | Text input | Set as number (1.5) or with units |
| **Letter Spacing** | Number (px) | Adjust character spacing |
| **Text Transform** | uppercase/lowercase/capitalize/none | Transform text case |
| **Font Style** | normal/italic | Set italic text |

#### UI Design
- **2-Column Grid** — Organized layout with size/weight, line-height/letter-spacing pairs
- **Unit Selectors** — Inline dropdown for font size units
- **Static Units** — px badge for letter spacing

---

### 🎨 Color Controls

Comprehensive color management with the new modern color picker.

#### Color Options
| Property | Description |
|----------|-------------|
| **Text Color** | Main content text color |
| **Background Color** | Block background (overridden by Background section if set) |
| **Link Color** | Anchor tag colors within the block |

#### Features
- **Color Picker Trigger** — Click to open modern color picker popup
- **Hex Text Input** — Manual hex/rgba input synced with picker
- **Transparency Support** — Full alpha channel support

---

### 🔲 Border Controls

Full border customization for blocks.

#### Border Properties
| Property | Options | Description |
|----------|---------|-------------|
| **Border Style** | none/solid/dashed/dotted/double | 5 border styles |
| **Border Width** | Number (px) | Border thickness in pixels |
| **Border Color** | Color picker | Border color with transparency |
| **Border Radius** | Number (px) | Corner rounding in pixels |

---

### 🌫️ Box Shadow Controls

Professional shadow presets with custom shadow builder.

#### Shadow Presets
| Preset | Shadow Value |
|--------|--------------|
| **Small** | `0 1px 2px 0 rgba(0,0,0,0.05)` |
| **Medium** | `0 4px 6px -1px rgba(0,0,0,0.1)` |
| **Large** | `0 10px 15px -3px rgba(0,0,0,0.1)` |
| **Extra Large** | `0 20px 25px -5px rgba(0,0,0,0.1)` |
| **Custom** | Opens custom shadow builder |

#### Custom Shadow Builder
- **Horizontal Offset** — X position in pixels
- **Vertical Offset** — Y position in pixels
- **Blur Radius** — Shadow blur amount
- **Spread Radius** — Shadow size expansion
- **Shadow Color** — Color with transparency support

---

### 🖼️ Background Controls

Advanced background options including solid colors, gradients, and images with overlays.

#### Background Types
| Type | Options |
|------|---------|
| **None** | Transparent background |
| **Color** | Solid color with transparency |
| **Gradient** | Linear or radial gradient with two colors |
| **Image** | Background image with position, size, repeat, and overlay |

#### Gradient Options
- **Color 1 & 2** — Start and end gradient colors
- **Gradient Type** — Linear or radial
- **Angle** — Direction in degrees (0-360) for linear gradients

#### Image Options
- **Image URL** — Background image source
- **Position** — 9 position options (center, corners, edges)
- **Size** — cover/contain/auto
- **Repeat** — no-repeat/repeat/repeat-x/repeat-y
- **Overlay Color** — Semi-transparent color overlay
- **Overlay Opacity** — 0-1 opacity value

---

### 📐 Spacing Controls (Margin & Padding)

Elementor-style margin and padding controls with linked values and unit selection.

#### Spacing Features
- **4-Value Input** — Top, Right, Bottom, Left inputs for each property
- **Unit Selection** — px/em/%/rem buttons with active state
- **Link Toggle** — Chain icon to link all 4 values
- **Live Preview** — Changes apply immediately in editor

#### Visual Design
- **2x2 Grid Layout** — Intuitive top/right/bottom/left arrangement
- **Labels Inside Grid** — TOP, RIGHT, BOTTOM, LEFT labels
- **Purple Active State** — Selected unit highlighted in accent color

---

### 📱 Responsive Visibility Controls

Control block visibility across device sizes.

#### Visibility Options
| Option | Breakpoint | Description |
|--------|------------|-------------|
| **Hide on Desktop** | ≥1025px | Block hidden on large screens |
| **Hide on Tablet** | 769px-1024px | Block hidden on medium screens |
| **Hide on Mobile** | ≤768px | Block hidden on small screens |

#### Implementation
- **Checkbox Toggles** — Easy on/off for each device type
- **Device Icons** — Visual icons for desktop/tablet/mobile
- **CSS Classes** — `.anvil-hide-desktop`, `.anvil-hide-tablet`, `.anvil-hide-mobile`
- **Media Query Based** — Proper responsive CSS implementation

---

### 📏 Sizing Controls

Control block dimensions with flexible sizing options.

#### Size Properties
| Property | Description |
|----------|-------------|
| **Width** | Block width (px, %, em, auto) |
| **Height** | Block height |
| **Max Width** | Maximum width constraint |
| **Max Height** | Maximum height constraint |
| **Min Width** | Minimum width constraint |
| **Min Height** | Minimum height constraint |
| **Overflow** | visible/hidden/scroll/auto |

---

### ⚡ Motion Effects (Animations)

Professional entrance animations and hover effects.

#### Entrance Animations (17 options)
| Category | Animations |
|----------|------------|
| **Fade** | fadeIn, fadeInUp, fadeInDown, fadeInLeft, fadeInRight |
| **Zoom** | zoomIn, zoomInUp, zoomInDown |
| **Slide** | slideInUp, slideInDown, slideInLeft, slideInRight |
| **Bounce** | bounceIn, bounceInUp |
| **Rotate** | rotateIn, flipInX, flipInY |

#### Animation Timing
- **Duration** — Animation length in milliseconds
- **Delay** — Delay before animation starts

#### Hover Effects (9 options)
| Effect | Description |
|--------|-------------|
| **Grow** | Scale up on hover |
| **Shrink** | Scale down on hover |
| **Pulse** | Pulsing scale animation |
| **Float** | Lift upward on hover |
| **Sink** | Push downward on hover |
| **Rotate** | Slight rotation on hover |
| **Shake** | Horizontal shake effect |
| **Wobble** | Playful wobble animation |
| **Buzz** | Vibration effect |

#### Transition Duration
- **Transition Speed** — Control hover transition timing

---

### 🔄 Transform Controls

CSS transform properties for advanced positioning and effects.

#### Transform Properties
| Property | Description |
|----------|-------------|
| **Rotate** | Rotation in degrees |
| **Scale** | Size multiplier (1 = normal) |
| **Translate X** | Horizontal offset in pixels |
| **Translate Y** | Vertical offset in pixels |
| **Skew X** | Horizontal skew in degrees |
| **Skew Y** | Vertical skew in degrees |

---

### 🏷️ Custom Attributes

Add custom CSS identifiers and classes to blocks.

#### Attribute Options
| Attribute | Description |
|-----------|-------------|
| **CSS ID** | Unique HTML id attribute |
| **CSS Classes** | Space-separated class names |
| **Z-Index** | Stack order positioning |

---

### 🎨 Modern Color Picker

A completely new Elementor-style color picker replacing the browser's native color input.

#### Color Picker Features
- **Gradient Picker Area** — Click and drag to select saturation and brightness
- **Hue Slider** — Rainbow gradient to select color hue
- **Opacity Slider** — Transparency control with checkered background preview
- **Current/Previous Preview** — Split preview showing current color and previous (click to restore)

#### Color Input Modes
| Mode | Fields |
|------|--------|
| **HEX** | Hex code + Alpha percentage |
| **RGB** | Red, Green, Blue values + Alpha |
| **HSL** | Hue, Saturation, Lightness + Alpha |

#### Color Presets
- **40 Preset Colors** — 4 rows of common colors
- **Row 1** — Grayscale (black to white)
- **Row 2** — Primary colors (rainbow)
- **Row 3** — Light pastels
- **Row 4** — Medium tones

#### Actions
- **Clear Button** — Remove color (set to transparent)
- **Apply Button** — Close picker and confirm color
- **Click Outside** — Close picker

#### Technical Features
- **Automatic Positioning** — Flips up/left if near screen edge
- **Smooth Animation** — Popup slides in with fade
- **Color Syncing** — Text input and picker stay synchronized
- **Full Color Space** — Supports hex, rgb, rgba, hsl, hsla formats
- **Alpha Channel** — Full transparency support
- **No Dependencies** — Pure JavaScript, no jQuery required
- **jQuery Plugin** — Optional `$.fn.anvilColorPicker()` for jQuery users

---

### 🖥️ Frontend Rendering

All new style properties render correctly on the frontend.

#### PHP Implementation
- **`getBlockStyles()`** — Generates complete CSS from block attributes
- **`getBlockClasses()`** — Generates CSS classes including animations
- **`getBlockId()`** — Returns custom CSS ID attribute

#### Rendered Properties
- Margin, padding, typography, colors
- Borders, box shadows, backgrounds (color, gradient, image)
- Sizing, transforms, transitions
- Animation classes, responsive visibility classes
- Custom CSS ID, classes, and z-index

---

### 🧱 New Content Blocks

Added 6 new powerful content blocks for creating rich, engaging pages:

#### Accordion Block
- Collapsible FAQ-style sections with title + content
- Add/remove items directly in the editor
- Multiple style options: default, bordered, filled, minimal
- Option to allow multiple items open at once

#### Alert Block
- Info, success, warning, and error message boxes
- Optional title and dismissible button
- Beautiful icons matching each alert type
- Perfect for announcements, tips, and warnings

#### Card Block
- Image + title + description + button layout
- Hover effects with subtle lift animation
- Multiple styles: default (shadow), bordered, minimal
- Great for features, team members, services

#### Testimonial Block
- Customer quote with author photo, name, role, company
- Star rating display (0-5 stars)
- Quote icon decoration
- Placeholder avatar when no image provided

#### Icon Box Block
- 20 built-in icons (star, heart, check, zap, shield, etc.)
- Customizable icon color
- Left, center, or right alignment
- Perfect for feature highlights and services

#### Social Links Block
- 8 platforms: Facebook, Twitter, Instagram, LinkedIn, YouTube, GitHub, TikTok, Email
- Size options: small, medium, large
- Style options: default, bordered, filled, minimal
- Brand colors on hover

---

### 🐛 Bug Fixes

- **Double `>` in Block Wrapper** — Fixed extra `>` character appearing in front of all blocks in the visual editor
- **Block Rendering** — All blocks now render correctly without stray characters
- **Settings Panel Rendering** — Fixed missing settings for new block types
- **Accordion Items Editor** — Fixed accordion item add/remove functionality
- **Social Links URLs** — Fixed URL normalization for social media links

---

### 📁 New CSS (anvil-live.css additions)

```css
/* Dark theme sidebar: ~200 lines */
/* Tabbed settings panel: ~150 lines */
/* Modern color picker: ~280 lines */
/* Responsive visibility classes: ~15 lines */
/* Animation keyframes & classes: ~120 lines */
/* Hover effect classes: ~60 lines */
```

**Total CSS Added:** ~825 lines

---

### 📁 New JavaScript (anvil-live.js additions)

```javascript
/* Typography controls: ~80 lines */
/* Color controls: ~45 lines */
/* Border controls: ~50 lines */
/* Box shadow controls: ~60 lines */
/* Background controls: ~140 lines */
/* Sizing controls: ~55 lines */
/* Animation controls: ~90 lines */
/* Transform controls: ~55 lines */
/* Custom attributes controls: ~30 lines */
/* Responsive controls: ~35 lines */
/* Style control handlers: ~100 lines */
/* Modern color picker: ~550 lines */
/* Block styles generator: ~120 lines */
/* Block classes generator: ~25 lines */
```

**Total JavaScript Added:** ~1,435 lines

---

### 📁 Modified Files

```
includes/
├── anvil.php                           — Added getBlockStyles(), getBlockClasses(), getBlockId()
│                                         Updated renderBlock() to apply all styles
│                                         Added background, sizing, transform, animation support
└── anvil-live/
    ├── assets/
    │   ├── css/anvil-live.css         — Dark theme, color picker, animations (+825 lines)
    │   └── js/anvil-live.js           — All new controls & color picker (+1,435 lines)
    └── editor-ui.php                   — No changes needed (dynamic HTML in JS)

themes/flavor/
└── style.css                           — Styles for new block types
```

---

### 🎯 Usage Examples

#### Typography
```
Font Size: 24px
Font Weight: Bold (700)
Line Height: 1.6
Letter Spacing: 1px
Text Transform: uppercase
```

#### Background Gradient
```
Type: Gradient
Color 1: #6366f1
Color 2: #a855f7
Gradient Type: Linear
Angle: 135°
```

#### Box Shadow
```
Preset: Custom
X: 0px
Y: 10px
Blur: 25px
Spread: -5px
Color: rgba(99, 102, 241, 0.3)
```

#### Animation
```
Entrance: fadeInUp
Duration: 1000ms
Delay: 200ms
Hover Effect: grow
Transition: 300ms
```

---

### 📊 File Size Changes

| File | Before | After | Change |
|------|--------|-------|--------|
| `anvil-live.js` | 2,985 lines | 4,160 lines | +1,175 lines |
| `anvil-live.css` | 585 lines | 960 lines | +375 lines |
| `anvil.php` | ~450 lines | ~550 lines | +100 lines |

---

## [0.2.2] - 2025-12-16

### 🎨 Anvil Live — Visual Frontend Editor

A powerful Elementor-style visual page builder that lets you edit pages directly on the frontend with real-time preview.

#### Core Features
- **Frontend Editing** — Edit posts and pages directly on the live site
- **Drag & Drop Blocks** — Drag blocks from sidebar to canvas with visual drop indicators
- **Inline Text Editing** — Click any text block to edit directly with rich text toolbar
- **Real-time Preview** — See changes instantly as you edit
- **Device Preview** — Preview desktop (1200px), tablet (768px), and mobile (375px) layouts
- **Autosave** — Automatic saving every 30 seconds with unsaved changes warning

#### Visual Drag & Drop System
- **Drop Indicator Line** — Purple line with circular ends shows exact drop position
- **Drag Ghost** — Floating preview element follows cursor during drag
- **Block Reordering** — Drag blocks by handle (⋮⋮) to reorder
- **Column Drop Targets** — Drop blocks directly into columns with "Drop here" overlay

#### Rich Text Toolbar
- **Formatting** — Bold, Italic, Underline, Strikethrough
- **Links** — Insert and remove hyperlinks with popup dialog
- **Alignment** — Left, Center, Right text alignment
- **Clear Formatting** — Remove all formatting from selection

#### Columns Block — Full Implementation
- **2-6 Columns** — Configurable column count via settings panel
- **Nested Blocks** — Add any block type inside columns
- **Click to Add** — Click empty column to open block picker
- **Drag to Column** — Drag blocks directly into columns
- **Move Between Columns** — Drag blocks in/out of columns freely
- **Vertical Alignment** — Top, Center, Bottom alignment options
- **Responsive Stacking** — Columns stack vertically on mobile

### 🐛 Bug Fixes
- **Inline Editing** — Fixed `makeBlocksEditable()` not called on initial page load
- **Justify Buttons** — Fixed text alignment buttons not working
- **Column Insertion** — Fixed blocks not inserting into columns
- **Drag vs Click** — Fixed sidebar blocks triggering drag on simple click
- **Block Operations in Columns** — Fixed duplicate, delete, settings for nested blocks

---

## [0.2.1] - 2025-12-15

### 🔗 REST API
- Full CRUD Operations for posts, pages, media, users, taxonomies
- API Key Authentication with granular permissions
- Admin Interface at Admin → Tools → API Keys

### 🎨 Modern Installer
- Step-by-step installation wizard
- System requirements check
- Purple gradient design

### 🏠 Dashboard Redesign
- Hero section with time-based greeting
- Stats row with colored cards
- Quick action buttons

---

## [0.2.0] - 2025-12-15

### 🔨 Anvil Block Editor
- 15 Block Types with drag & drop
- Class-based block architecture
- Full undo/redo support (50 levels)

### 🎨 Flavor Theme
- Block showcase landing page
- Comprehensive block styling
- Theme settings support

---

## Version History

| Version | Date | Highlights |
|---------|------|------------|
| 0.2.3 | 2025-12-19 | Elementor-style settings (typography, colors, borders, shadows, backgrounds, sizing, animations, transforms), modern color picker, dark theme sidebar, tabbed settings panel, 6 new blocks |
| 0.2.2 | 2025-12-16 | Anvil Live visual frontend editor, drag-drop blocks, inline editing, columns support |
| 0.2.1 | 2025-12-15 | REST API with API key management, modern installer redesign, dashboard redesign |
| 0.2.0 | 2025-12-15 | Anvil block editor with 15 blocks, class-based architecture, Flavor theme |
| 0.1.8 | 2025-12-13 | Comments system with threading, moderation, guest commenting |
| 0.1.7 | 2025-12-12 | Bulk actions, Quick Edit inline editing |
| 0.1.6 | 2025-12-12 | Login screen editor, repeater/group fields, admin columns, scheduled publishing |
| 0.1.5 | 2025-12-11 | Duplicate post, taxonomies system, menu builder |
| 0.1.4 | 2025-12-09 | Menu builder system, themes page redesign |
| 0.1.3 | 2025-12-09 | Post revisions system |
| 0.1.2 | 2025-12-09 | Theme system, Media/Thumbnails modal redesign |
| 0.1.1 | 2025-12-08 | VoidForge rebrand, Custom fields, 80+ icons |
| 0.1.0 | 2025-12-08 | Initial release |

---

**VoidForge CMS** — Modern Content Management


## [0.2.2] - 2025-12-16

### 🎨 Anvil Live — Visual Frontend Editor

A powerful Elementor-style visual page builder that lets you edit pages directly on the frontend with real-time preview.

#### Core Features
- **Frontend Editing** — Edit posts and pages directly on the live site
- **Drag & Drop Blocks** — Drag blocks from sidebar to canvas with visual drop indicators
- **Inline Text Editing** — Click any text block to edit directly with rich text toolbar
- **Real-time Preview** — See changes instantly as you edit
- **Device Preview** — Preview desktop (1200px), tablet (768px), and mobile (375px) layouts
- **Autosave** — Automatic saving every 30 seconds with unsaved changes warning

#### Visual Drag & Drop System
- **Drop Indicator Line** — Purple line with circular ends shows exact drop position
- **Drag Ghost** — Floating preview element follows cursor during drag
- **Block Reordering** — Drag blocks by handle (⋮⋮) to reorder
- **Column Drop Targets** — Drop blocks directly into columns with "Drop here" overlay

#### Rich Text Toolbar
- **Formatting** — Bold, Italic, Underline, Strikethrough
- **Links** — Insert and remove hyperlinks with popup dialog
- **Alignment** — Left, Center, Right text alignment
- **Clear Formatting** — Remove all formatting from selection

#### Columns Block — Full Implementation
- **2-6 Columns** — Configurable column count via settings panel
- **Nested Blocks** — Add any block type inside columns
- **Click to Add** — Click empty column to open block picker
- **Drag to Column** — Drag blocks directly into columns
- **Move Between Columns** — Drag blocks in/out of columns freely
- **Vertical Alignment** — Top, Center, Bottom alignment options
- **Responsive Stacking** — Columns stack vertically on mobile

#### Block Operations
- **Add Blocks** — Click in sidebar or drag to canvas
- **Duplicate** — Clone any block including nested content
- **Delete** — Remove blocks with keyboard (Delete/Backspace) or button
- **Settings** — Configure block attributes in sidebar panel
- **Move** — Drag blocks to reorder or move into/out of columns

#### Keyboard Shortcuts
- `Ctrl+S` — Save content
- `Ctrl+Z` — Undo
- `Ctrl+Shift+Z` — Redo
- `Delete` — Delete selected block
- `Escape` — Deselect block, close modals

#### State Management
- **Undo/Redo Stack** — 50 levels of history
- **Dirty State Tracking** — Warning before leaving with unsaved changes
- **Block Synchronization** — Real-time sync between DOM and data model

#### REST API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/anvil-live/save` | Save post content and title |
| POST | `/api/v1/anvil-live/autosave` | Autosave draft content |
| GET | `/api/v1/anvil-live/preview` | Get preview data |

#### How to Access
1. Navigate to any post or page on the frontend
2. Add `?anvil-live=edit` to the URL
3. Or click "Edit with Anvil Live" button in admin bar

### 🐛 Bug Fixes
- **Inline Editing** — Fixed `makeBlocksEditable()` not called on initial page load
- **Justify Buttons** — Fixed text alignment buttons not working (custom implementation for contenteditable)
- **Column Insertion** — Fixed blocks not inserting into columns (column context management)
- **Drag vs Click** — Fixed sidebar blocks triggering drag on simple click
- **Block Operations in Columns** — Fixed duplicate, delete, settings for nested blocks

### 📁 New Files

```
includes/
├── anvil-live.php                    # Main Anvil Live class
└── anvil-live/
    ├── editor-ui.php                 # Sidebar, toolbar, modals HTML
    └── assets/
        ├── css/anvil-live.css        # Editor styles (517 lines)
        └── js/anvil-live.js          # Editor JavaScript (1900+ lines)
```

### 📝 Modified Files

- `includes/anvil/blocks/ColumnsBlock.php` — Added `data-column-index` attribute
- `index.php` — Integrated Anvil Live initialization

---

## [0.2.1] - 2025-12-15

### 🔗 REST API

A complete REST API for programmatic access to VoidForge content.

#### API Features
- **Full CRUD Operations** — Create, read, update, delete for posts, pages, media, users, and taxonomies
- **API Key Authentication** — Secure access via `X-API-Key` and `X-API-Secret` headers
- **Granular Permissions** — Control read/write/delete access per API key
- **Admin Interface** — New page at Admin → Tools → API Keys for key management
- **JSON Responses** — Standard REST responses with pagination and filtering

#### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/posts` | List posts with filtering |
| GET | `/api/v1/posts/{id}` | Get single post |
| POST | `/api/v1/posts` | Create post |
| PUT | `/api/v1/posts/{id}` | Update post |
| DELETE | `/api/v1/posts/{id}` | Delete post |
| GET | `/api/v1/pages` | List pages |
| GET | `/api/v1/media` | List media |
| POST | `/api/v1/media` | Upload media (base64) |
| GET | `/api/v1/users` | List users |
| GET | `/api/v1/taxonomies/{type}` | List taxonomy terms |

#### Query Parameters
- `per_page` — Items per page (default: 10, max: 100)
- `page` — Page number
- `status` — Filter by status (published, draft, etc.)
- `post_type` — Filter by post type
- `orderby` — Sort field (date, title, id)
- `order` — Sort direction (asc, desc)

### 🎨 Modern Installer

Completely redesigned installation wizard with step-by-step flow.

#### Installer Features
- **Step 1: Requirements** — System check with pass/fail badges for PHP version, extensions, and directory permissions
- **Step 2: Configuration** — Database settings, site info, and admin account setup in organized cards
- **Step 3: Complete** — Success confirmation with links to admin and frontend
- **Visual Design** — Purple gradient header, modern card layout, smooth animations

### 🏠 Dashboard Redesign

New modern admin dashboard with improved visual design.

#### Dashboard Features
- **Hero Section** — Purple gradient with time-based greeting and date display
- **Stats Row** — 6 colored number cards (Posts, Pages, Drafts, Media, Comments, Users)
- **Quick Actions** — 4 large gradient icon buttons (New Post, New Page, Upload, Settings)
- **Two-Column Layout** — Posts/Pages lists on left, Comments/Media/System info on right
- **Status Pills** — Color-coded badges for published, draft, and scheduled posts

### 🎯 Modal Confirmations

Replaced all JavaScript `alert()` and `confirm()` dialogs with elegant modal dialogs.

#### Modal Features
- **Confirmation Modal** — Clean dialog for trash/delete actions with item name display
- **Toast Notifications** — Slide-in notifications for validation feedback
- **Keyboard Support** — Escape key to cancel, click outside to close
- **Consistent Styling** — Matches admin design language

### 🐛 Bug Fixes

#### Critical Fixes
- **Nested Forms Bug** — Fixed post duplication when using bulk trash (forms were nested inside bulk form)
- **Database Connection Handling** — Properly redirects to installer when database doesn't exist
- **Constant Redefinition** — Fixed `CMS_VERSION` and `CMS_NAME` warnings during installation
- **Missing SESSION_NAME** — Added to generated config.php to prevent fatal errors
- **PHP 8.1 Compatibility** — Fixed null email causing deprecation warnings in dashboard

#### JavaScript Fixes
- **Variable Scoping** — Moved global variables to top of script block
- **Bulk Action Confirmation** — Added flag to prevent modal loop on form submit
- **Form ID Mismatch** — Fixed `bulkForm` vs `bulkActionsForm` reference

### 📁 New Files

```
admin/
└── api-keys.php              # API key management interface

includes/
└── rest-api.php              # REST API implementation
```

### 📝 Modified Files

- `install.php` — Complete redesign with modern UI, added `trashed_at` column to schema
- `admin/index.php` — Dashboard redesign with gradient hero and stat cards
- `admin/posts.php` — Modal confirmations, removed nested forms, toast notifications
- `includes/config.php` (generated) — Added `SESSION_NAME` constant

---

## [0.2.0] - 2025-12-15

### 🔨 Anvil Block Editor

A powerful new block-based content editor for posts, pages, and custom post types.

#### Core Features
- **15 Block Types** — Paragraph, Heading, List, Quote, Code, Image, Gallery, Video, Columns, Spacer, Separator, Button, HTML, Embed, Table
- **4 Block Categories** — Text, Media, Layout, Embeds with iconography
- **Drag & Drop** — Reorder blocks with smooth animations (SortableJS)
- **Block Library Panel** — Searchable block picker with category filtering
- **Settings Panel** — Inline block configuration without modals
- **Undo/Redo** — Full history support (50 levels) with Ctrl+Z/Ctrl+Y
- **Media Integration** — Seamless connection to VoidForge media library

#### Class-Based Block Architecture
- **Refactored from monolithic to modular** — Each block is now a separate PHP class
- **AnvilBlock Base Class** — Abstract base class with common functionality
- **15 Individual Block Classes** — Located in `/includes/anvil/blocks/`
- **Plugin API** — Register custom blocks via `Anvil::registerBlockClass()`

```php
// Register a custom block class
class AlertBlock extends AnvilBlock {
    public static function getType(): string { return 'alert'; }
    public static function getLabel(): string { return 'Alert'; }
    public static function getCategory(): string { return 'layout'; }
    public static function getIcon(): string { return 'alert-circle'; }
    
    public static function getAttributes(): array {
        return [
            'content' => ['type' => 'string', 'default' => ''],
            'type' => ['type' => 'string', 'default' => 'info']
        ];
    }
    
    public static function render(array $attrs): string {
        return '<div class="alert alert-' . esc($attrs['type']) . '">' . 
               esc($attrs['content']) . '</div>';
    }
}

Anvil::registerBlockClass(AlertBlock::class);
```

#### Block Types

| Block | Category | Description |
|-------|----------|-------------|
| `paragraph` | Text | Rich text paragraph with alignment and drop cap |
| `heading` | Text | H1-H6 headings with anchor support |
| `list` | Text | Ordered and unordered lists |
| `quote` | Text | Blockquote with citation |
| `code` | Text | Syntax-highlighted code block with language label |
| `table` | Text | Data tables with headers |
| `image` | Media | Single image with caption, link, and alignment |
| `gallery` | Media | Image gallery with columns (2-6) |
| `video` | Media | Video embed (YouTube, Vimeo, self-hosted) |
| `columns` | Layout | Multi-column layouts (2-4 columns) |
| `spacer` | Layout | Vertical spacing (10-200px) |
| `separator` | Layout | Horizontal divider (default, wide, dots) |
| `button` | Layout | CTA button (primary, secondary, outline) |
| `html` | Embed | Custom HTML code |
| `embed` | Embed | oEmbed for external content |

### 🎨 Flavor Theme

A new clean, modern theme designed specifically to showcase all Anvil block editor capabilities.

#### Theme Features
- **Block Showcase Landing Page** — Demonstrates all 15 block types with live examples
- **Comprehensive Block Styling** — CSS for every block type with proper spacing and typography
- **Google Fonts** — Inter (UI), Merriweather (body), JetBrains Mono (code)
- **Responsive Design** — Mobile-first with proper breakpoints
- **Theme Settings** — Accent color, content width, show/hide author & date

#### Theme Settings (via Admin → Theme Settings)
| Setting | Type | Description |
|---------|------|-------------|
| Accent Color | Color picker | Primary accent color (default: #6366f1) |
| Content Width | Select | Narrow (680px), Default (780px), Wide (920px) |
| Show Author | Toggle | Display author on posts |
| Show Date | Toggle | Display date on posts |
| Custom CSS | Textarea | Additional custom styles |

#### Template Files
- `home.php` — Block showcase landing page with hero, features, and examples
- `single.php` — Single post with reading time, prev/next navigation
- `page.php` — Static page template
- `index.php` — Archive/blog listing
- `header.php` — Site header with logo and navigation
- `footer.php` — Site footer
- `404.php` — Error page
- `functions.php` — Theme helper functions

### 🛠️ Theme Settings Improvements

- **Simplified Flavor Settings** — Clean form with only relevant options
- **Removed Legacy Settings** — No more hero/stats/features settings for Flavor
- **Theme-Specific Forms** — Different themes show different settings
- **Proper Default Values** — Settings now properly read from theme.json

### 📚 Documentation Updates

- **Plugin Development Guide** — Updated with class-based block registration
- **Theme Development Guide** — Updated with Flavor theme patterns

### 🐛 Bug Fixes

- Fixed `Theme::getSettings()` not existing — now uses `getOption('theme_settings_'.$theme)`
- Fixed `Menu::get()` not existing — now uses `Menu::getMenuByLocation()`
- Fixed `Post::getThumbnail()` not existing — now uses `Post::featuredImage()`
- Fixed `Post::adjacent()` not existing — now uses `Post::getAdjacent()`
- Fixed `Taxonomy::termLink()` not existing — now uses `Taxonomy::getTermUrl()`
- Fixed theme settings not applying to frontend

### 📁 New Files

```
includes/anvil/
├── AnvilBlock.php              # Abstract base class
└── blocks/
    ├── ParagraphBlock.php
    ├── HeadingBlock.php
    ├── ListBlock.php
    ├── QuoteBlock.php
    ├── CodeBlock.php
    ├── TableBlock.php
    ├── ImageBlock.php
    ├── GalleryBlock.php
    ├── VideoBlock.php
    ├── ColumnsBlock.php
    ├── SpacerBlock.php
    ├── SeparatorBlock.php
    ├── ButtonBlock.php
    ├── HtmlBlock.php
    └── EmbedBlock.php

themes/flavor/
├── theme.json                  # Theme metadata and settings schema
├── style.css                   # 17KB comprehensive stylesheet
├── functions.php               # Theme helper functions
├── home.php                    # Block showcase landing page
├── header.php
├── footer.php
├── single.php
├── page.php
├── index.php
├── 404.php
└── assets/
    ├── css/
    └── js/
```

### 📝 Modified Files

- `includes/anvil.php` — Refactored to use class-based blocks
- `includes/theme.php` — Default theme changed to 'flavor'
- `install.php` — Default theme set to 'flavor', version 0.2.0
- `admin/theme-settings.php` — Separate forms for different themes
- `README.md` — Updated features and directory structure

---

## [0.1.8] - 2025-12-13

### 🪝 Comprehensive Hooks & Filters System

A complete expansion of the plugin API with **90+ hooks and filters** for powerful theme and plugin development.

### 📚 Documentation Redesign

- **Light Mode Design** — Clean, professional documentation with white/gray background
- **Modern Typography** — Outfit font for readability, Source Code Pro for code
- **Syntax Highlighting** — Color-coded PHP, HTML, and CSS examples
- **Responsive Layout** — Sticky navigation, mobile hamburger menu
- **Correct Branding** — VoidForge logo with purple→cyan gradient

#### Breaking Changes
- **Renamed Functions** — `wp_send_json_success()` → `vf_send_json_success()`, `wp_send_json_error()` → `vf_send_json_error()` (legacy aliases kept for backward compatibility)
- **Renamed Hooks** — `wp_head` → `vf_head`, `wp_footer` → `vf_footer` in themes

#### Content Hooks (Posts/Pages)
| Hook | Type | Description |
|------|------|-------------|
| `pre_insert_post` | Filter | Modify/validate post data before creation |
| `post_inserted` | Action | Fires after post creation |
| `post_inserted_{type}` | Action | Post type-specific insert action |
| `pre_update_post` | Filter | Modify/validate update data |
| `post_updated` | Action | Fires after post update |
| `post_updated_{type}` | Action | Post type-specific update action |
| `post_status_changed` | Action | When post status changes |
| `post_status_{status}` | Action | When post transitions to specific status |
| `pre_delete_post` | Action | Before post deletion |
| `post_deleted` | Action | After permanent deletion |
| `post_trashed` | Action | When post moves to trash |
| `post_restored` | Action | When post restored from trash |
| `pre_get_posts` | Filter | Modify query args before execution |
| `the_posts` | Filter | Filter query results |
| `the_title` | Filter | Modify post title display |
| `the_excerpt` | Filter | Modify post excerpt |
| `the_content` | Filter | Modify post content (already existed) |
| `the_permalink` | Filter | Modify post permalinks |

#### Post Meta Hooks
| Hook | Type | Description |
|------|------|-------------|
| `pre_update_post_meta` | Filter | Modify meta value before save |
| `post_meta_updated` | Action | After meta saved |
| `pre_delete_post_meta` | Action | Before meta deleted |
| `post_meta_deleted` | Action | After meta deleted |

#### User & Authentication Hooks
| Hook | Type | Description |
|------|------|-------------|
| `pre_user_login` | Action | Before login attempt |
| `authenticate` | Filter | Custom authentication methods |
| `user_logged_in` | Action | After successful login |
| `user_login_failed` | Action | After failed login |
| `user_logged_out` | Action | When user logs out |
| `pre_insert_user` | Filter | Modify user data before creation |
| `user_inserted` | Action | After user creation |
| `pre_update_user` | Filter | Modify user update data |
| `user_updated` | Action | After user update |
| `user_role_changed` | Action | When user role changes |
| `pre_delete_user` | Action | Before user deletion |
| `user_deleted` | Action | After user deletion |

#### Media Hooks
| Hook | Type | Description |
|------|------|-------------|
| `pre_upload_media` | Filter | Modify upload data before processing |
| `media_uploaded` | Action | After file upload |
| `upload_allowed_types` | Filter | Modify allowed file types |
| `upload_max_size` | Filter | Modify max upload size |
| `thumbnail_sizes` | Filter | Add/modify thumbnail sizes |
| `media_folder_changed` | Action | When media moved to folder |
| `pre_delete_media` | Action | Before media deletion |
| `media_deleted` | Action | After media deletion |

#### Menu Hooks
| Hook | Type | Description |
|------|------|-------------|
| `menu_items` | Filter | Modify menu items before render |
| `menu_item_classes` | Filter | Modify menu item CSS classes |
| `pre_save_menu` | Filter | Modify menu data before save |
| `menu_saved` | Action | After menu saved |
| `menu_deleted` | Action | After menu deleted |

#### Taxonomy/Term Hooks
| Hook | Type | Description |
|------|------|-------------|
| `pre_insert_term` | Filter | Modify term data before creation |
| `term_inserted` | Action | After term creation |
| `pre_update_term` | Filter | Modify term update data |
| `term_updated` | Action | After term update |
| `pre_delete_term` | Action | Before term deletion |
| `term_deleted` | Action | After term deletion |
| `post_terms_set` | Action | When terms assigned to post |

#### Comment Hooks
| Hook | Type | Description |
|------|------|-------------|
| `pre_insert_comment` | Filter | Modify/reject comment before save |
| `comment_created` | Action | After comment created (already existed) |
| `comment_reply` | Action | When reply posted |
| `comment_updated` | Action | After comment update (already existed) |
| `comment_status_changed` | Action | When comment status changes |
| `comment_deleted` | Action | After comment deleted (already existed) |

#### Theme/Template Hooks
| Hook | Type | Description |
|------|------|-------------|
| `vf_head` | Action | Inside `<head>` tag |
| `vf_footer` | Action | Before `</body>` tag |
| `body_class` | Filter | Modify body CSS classes |
| `template_include` | Filter | Override template selection |
| `template_redirect` | Action | Before template loads |
| `get_header` | Action | When header loads (already existed) |
| `get_footer` | Action | When footer loads (already existed) |
| `get_sidebar` | Action | When sidebar loads (already existed) |

#### Options Hooks
| Hook | Type | Description |
|------|------|-------------|
| `pre_get_option_{name}` | Filter | Modify option before retrieval |
| `pre_update_option_{name}` | Filter | Modify option before save |
| `option_updated` | Action | After any option saved |

#### REST API Hooks
| Hook | Type | Description |
|------|------|-------------|
| `rest_api_init` | Action | When REST API initializes |
| `rest_pre_dispatch` | Filter | Before handling request |
| `rest_post_dispatch` | Filter | Modify response after handling |
| `rest_authentication_errors` | Filter | Custom authentication |

#### System Hooks
| Hook | Type | Description |
|------|------|-------------|
| `cron_schedules` | Filter | Add custom cron intervals |
| `vf_redirect` | Filter | Modify redirect URLs |
| `shutdown` | Action | When script ends |
| `init` | Action | After CMS initializes (already existed) |
| `plugins_loaded` | Action | After all plugins load (already existed) |

#### Admin Hooks
| Hook | Type | Description |
|------|------|-------------|
| `admin_init` | Action | When admin loads (before header) |
| `admin_menu` | Action | When building admin menu |
| `admin_head` | Action | Inside admin `<head>` tag |
| `admin_footer` | Action | Before admin `</body>` tag |
| `admin_notices` | Action | Display admin notices |
| `admin_enqueue_scripts` | Action | Enqueue page-specific assets |
| `dashboard_setup` | Action | Setup dashboard widgets |
| `edit_form_after_title` | Action | After post title field |
| `edit_form_after_editor` | Action | After post editor |
| `post_submitbox_actions` | Action | Inside publish box |

#### New Helper Functions
```php
// JSON responses (use these instead of wp_ versions)
vf_send_json_success($data, $code);
vf_send_json_error($data, $code);

// Content helpers
the_title($post);      // Returns filtered title
the_excerpt($post);    // Returns filtered excerpt
the_content($post);    // Returns filtered content
body_class($classes);  // Returns filtered body classes

// Redirect with filter
vf_redirect($url, $status);
```

### 💬 Comments System

A complete commenting system for posts and pages with moderation, threading, and guest commenting.

#### Core Features
- **Comment Display** — Threaded comments with configurable reply depth (1-10 levels)
- **Guest Commenting** — Visitors can comment with name/email or require registration
- **User Comments** — Logged-in users auto-fill their details
- **Gravatar Support** — Automatic avatars based on email address
- **Comment Count** — Displays in post header with link to comments section

#### Admin Management
- **Comments Dashboard** — New admin page at Content → Comments
- **Status Tabs** — Filter by All, Pending, Approved, Spam, Trash
- **Bulk Actions** — Approve, spam, trash, restore, or delete multiple comments
- **Inline Editing** — Edit comment content directly from the list
- **Pending Badge** — Sidebar shows count of comments awaiting moderation
- **Post Link** — Quick access to the post each comment belongs to

#### Moderation Options
- **No Moderation** — All comments publish immediately
- **Auto-approve Registered** — Logged-in users auto-approved, guests need approval
- **Manual Approval** — All comments require admin review

#### Comment Settings
Located in Settings → Comments tab:
- **Enable/Disable** — Global toggle for comment system
- **Post Types** — Choose which post types allow comments
- **Require Login** — Optional requirement for users to be logged in
- **Reply Depth** — Maximum nesting level (1-10)
- **Auto-close** — Close comments after X days (7, 14, 30, 60, 90, 365, or never)
- **Length Limits** — Minimum and maximum character counts
- **Auto-links** — Convert URLs to clickable links

#### Frontend Features
- **Comment Form** — Clean, responsive comment submission form
- **Reply Forms** — Inline reply forms that appear below comments
- **Validation** — Client and server-side validation with error messages
- **Success Messages** — Confirmation based on moderation settings
- **Comments Closed** — Graceful message when comments are disabled

#### Security
- **Nonce Protection** — CSRF protection for comment forms
- **IP Logging** — Author IP recorded for spam tracking
- **Content Sanitization** — HTML stripped, optional auto-linking
- **Validation** — Email format, length limits, required fields

### 📁 New Files

```
includes/
└── comment.php           # Comment class with CRUD, threading, moderation

admin/
└── comments.php          # Admin comment management page
```

### 📁 Modified Files

```
includes/
├── config.php            — Version updated to 0.1.8
├── functions.php         — Added nonce functions (createNonce, verifyNonce)
└── migrations.php        — Added comments table, comment_count column

admin/
├── settings.php          — Added Comments settings tab with 10+ options
└── includes/sidebar.php  — Added Comments link with pending badge

themes/default/
└── single.php            — Added comment display and submission form
```

### 🎯 Database Changes

New `comments` table with fields:
- id, post_id, parent_id, user_id
- author_name, author_email, author_url, author_ip
- content, status (pending/approved/spam/trash)
- created_at

New `comment_count` column on `posts` table for efficient counting.

### 🔒 Security Features

- Nonce tokens for form submission
- CSRF protection
- IP address logging
- HTML sanitization
- Rate limiting ready

---

## [0.1.7] - 2025-12-12

### ☑️ Bulk Actions

Perform actions on multiple posts at once from the post listing page.

#### Features
- **Checkbox Selection** — Select individual posts or use "Select All" to select all visible posts
- **Selection Counter** — Shows how many items are selected with live updates
- **Bulk Actions Bar** — Appears automatically when items are selected
- **Row Highlighting** — Selected rows are highlighted with a subtle purple tint

#### Available Actions (Normal View)
- **Publish** — Publish multiple drafts at once
- **Set to Draft** — Unpublish multiple posts at once
- **Move to Trash** — Trash multiple posts at once
- **Add Taxonomy** — Add categories/tags to multiple posts (per taxonomy)
- **Remove Taxonomy** — Remove categories/tags from multiple posts (per taxonomy)

#### Available Actions (Trash View)
- **Restore** — Restore multiple trashed items at once
- **Delete Permanently** — Permanently delete multiple items at once

#### Taxonomy Bulk Assignment
- Select a taxonomy action (Add/Remove Category, Tag, or custom taxonomy)
- Multi-select dropdown appears with available terms
- Apply to all selected posts at once

#### UX Details
- Confirmation dialogs for destructive actions (trash, delete)
- "Deselect All" button to quickly clear selection
- Validation prevents submitting without action or selection

### ⚡ Quick Edit

Edit post basics inline without leaving the posts list.

#### Features
- **Inline Editing Row** — Opens directly below the post row in the table
- **AJAX Save** — Changes save instantly without page reload
- **Live Updates** — Table row updates immediately with new values
- **One-at-a-Time** — Opening a new Quick Edit closes any open one

#### Editable Fields
- **Title** — Edit the post title
- **Slug** — Edit the URL slug
- **Status** — Change between Draft, Published, Scheduled
- **Date** — Change the post date with datetime picker
- **Taxonomies** — Checkbox list for all available taxonomies (categories, tags, custom)

#### UX Details
- **Keyboard Support** — Press Escape to close Quick Edit
- **Loading States** — Visual feedback during save
- **Gradient Button** — Quick Edit button stands out with purple gradient
- **Responsive Grid** — Fields arranged in responsive 4-column grid

### 📁 Modified Files

```
admin/
└── posts.php             — Complete rewrite with Bulk Actions & Quick Edit
includes/
└── config.php            — Version updated to 0.1.7
```

### 🎯 Usage

#### Bulk Actions
1. Navigate to any post list (Posts, Pages, or custom post types)
2. Check boxes next to posts you want to modify
3. The bulk actions bar appears automatically
4. Select an action from the dropdown
5. For taxonomy actions, select terms in the multi-select
6. Click "Apply"

#### Quick Edit
1. Navigate to any post list
2. Click the purple "Quick Edit" button on any row
3. Edit title, slug, status, date, or taxonomies
4. Click "Update" to save (or Escape/Cancel to discard)

---

## [0.1.6.2] - 2025-12-12

### 🔐 Login Screen Editor

A comprehensive visual editor for customizing the admin login page with live preview.

#### Core Features
- **Full-Screen Editor** — Standalone editor following the customize.php design pattern
- **Live Preview** — Real-time preview in iframe as you adjust settings
- **12 Visual Presets** — One-click application of pre-designed themes
- **80+ Settings** — Complete control over every aspect of the login page

#### Background Options
- **4 Background Types** — Solid color, gradient, image, or pattern
- **5 Pattern Styles** — Dots, grid, diagonal, crosses, waves
- **Pattern Customization** — Adjustable pattern color (rgba) and size (10-50px)
- **Image Backgrounds** — URL-based with optional dark overlay

#### Card Styling
- **Dimensions** — Width (300-600px), padding (20-60px), border radius
- **Border Control** — Color and width (0-5px)
- **Backdrop Blur** — Glassmorphism effect (0-30px)
- **Shadow** — Toggleable with customizable color

#### Typography
- **Title** — Text, color, size (16-48px), weight (400-700)
- **Subtitle** — Text, color, size (10-24px)
- **Labels** — Color, size (10-18px)
- **Placeholder Color** — Customizable input placeholder text

#### Form Controls
- **Input Styling** — Background, border, text color, radius, padding, focus color
- **Button Options** — Solid or gradient, customizable colors, angle (0-360°), shadow, full-width toggle
- **Button Label** — Customizable button text (default: "Sign In")
- **Form Labels** — Customize "Remember me", "Forgot password?", "Sign up" text

#### Animation
- **5 Animation Types** — None, fade, slide, scale, bounce
- **Duration Control** — 200-1500ms

#### Logo Options
- **Default Logo** — VoidForge branding with gradient text
- **Custom Logo** — URL-based image with adjustable width (80-300px)
- **No Logo** — Option to hide logo entirely
- **Bottom Margin** — 0-60px spacing below logo

#### Presets
12 professional presets with clean text-based buttons in 3-column grid:
- Default, Aurora, Minimal, Ocean, Nature, Rose
- Soft, Corporate, Sunset, Lavender, Slate, Fresh

### 📁 New Files

```
admin/
└── login-editor.php      # Login screen visual editor (1,000+ lines)
```

### 📁 Modified Files

```
admin/
├── login.php             — Updated to use all 80+ saved settings
└── includes/sidebar.php  — Added "Login Screen" link under Design section
```

### 🎯 Access

Navigate to **Design → Login Screen** in the admin sidebar to open the editor.

---

## [0.1.6.1] - 2025-12-12

### 🔁 Repeater & Group Fields

Powerful nested field types for complex content structures.

#### Repeater Fields
- **Multiple Rows** — Add unlimited rows of the same field structure
- **Sub Field Types** — Text, textarea, number, email, URL, date, datetime, color, select, checkbox, image, file, WYSIWYG, radio
- **Add/Remove Rows** — Dynamic row management with intuitive +/× buttons
- **Row Numbering** — Automatic numbering updates when rows are reordered
- **Scrollable Container** — Max height with scroll for many rows
- **JSON Storage** — Data stored as JSON array in database

#### Group Fields
- **Structured Data** — Combine multiple fields into a single logical unit
- **Same Sub Field Types** — All the same field types available as repeaters
- **Compact Display** — Sub fields displayed together in the sidebar
- **JSON Storage** — Data stored as JSON object in database

#### Use Cases
- **Repeaters** — Team members, testimonials, FAQ items, price tables, galleries, features list
- **Groups** — Address fields, social links, SEO settings, contact info, dimensions

#### Field Type Selector Improvements
- **Organized Categories** — Field types grouped by purpose (Basic, Date & Time, Choice, Media, Content, Layout)
- **Radio Buttons** — New radio button field type for single-choice options
- **Visual Distinction** — Layout types (repeater/group) have colored badges

#### Post Type Editor Support
- **Repeater & Group in Post Types** — Full support for defining repeater and group fields directly in post type editor
- **Sub-field Modal** — Add, edit, and remove sub-fields with a clean modal interface
- **Consistent Experience** — Same field types and options as custom field groups

#### Field Key Prefixing
- **Auto-prefixed Keys** — Field keys are automatically prefixed with post type slug
- **Example** — A `price` field on `product` post type becomes `product_price`
- **Prevents Conflicts** — Ensures unique field keys across different post types

#### Technical Details
- Sub fields defined in the field group editor with inline add UI
- Repeater data format: `[{sub_key: value, ...}, ...]`
- Group data format: `{sub_key: value, ...}`
- Proper input name generation: `cf_{posttype}_{field}_{row}_{subfield}` for repeaters
- Row count tracking via hidden input for proper save processing

#### Bug Fixes
- Fixed undefined `$postTaxonomies` error when creating new posts on custom post types
- Fixed missing `.modal-content` CSS class in post type editor

---

## [0.1.6] - 2025-12-12

### 📊 Admin Columns

Fully customizable column management for post listings, similar to Admin Columns plugin.

#### Features
- **Choose Columns** — Select which columns appear in each post type's listing
- **Column Types** — Built-in columns (ID, Title, Author, Status, Date, Slug, Featured Image, Word Count)
- **Taxonomy Columns** — Display categories, tags, and custom taxonomies as columns
- **Custom Field Columns** — Show any custom field as a column with smart formatting
- **Custom Labels** — Override default column labels with your own text
- **Adjustable Widths** — Set pixel, percentage, or auto width for each column
- **Drag-to-Resize** — Resize columns directly on the posts page by dragging column borders
- **Drag & Drop Ordering** — Reorder columns by dragging in the settings
- **Per Post Type** — Different configurations for posts, pages, and custom post types
- **Live Preview** — See how columns will look before saving
- **Enable/Disable** — Temporarily hide columns without removing them
- **Persistent Widths** — Column widths adjusted on the posts page are saved automatically

#### Column Rendering
- **Images** — Thumbnail preview for image fields and featured images  
- **Colors** — Color swatch for color picker fields
- **Checkboxes** — Checkmark or dash display
- **URLs** — Clickable truncated links
- **Long Text** — Truncated with ellipsis
- **Dates** — Formatted date display
- **Taxonomies** — Comma-separated term names

#### Technical Implementation
- Uses `<colgroup>` for independent column width control
- Table layout fixed ensures columns don't shift during resize
- Width changes applied via `<col>` elements, not `<th>` styles
- Smooth resize without layout recalculation jumps

#### Usage
1. Go to any post list (Posts, Pages, or custom post types)
2. Click the "Columns" button in the header
3. Add columns from the available list (includes all custom fields!)
4. Set custom labels, drag to reorder, set widths, enable/disable
5. Save and return to the post list
6. On the posts page, drag column borders to resize on-the-fly

### 🎨 Column Settings UI Redesign

Complete visual overhaul of the column management interface.

#### Panel Styling
- **Section Headers** — Colored accent bars (purple for Active, green for Available, orange for Preview)
- **Card Shadows** — Subtle box shadows for depth and separation
- **Gradient Backgrounds** — Subtle gradient overlays on headers
- **Sticky Sidebar** — Available columns panel stays visible while scrolling

#### Interactive Elements
- **Column Count Badge** — Shows number of enabled columns, updates in real-time
- **Column Item Hover** — Lift animation with colored border highlight
- **Type Badges** — Enhanced with borders and gradients for each type
- **Add Button** — Green gradient with hover lift effect
- **Remove Button** — Scales up on hover with red highlight

#### Preview Table
- **Thicker Header Border** — 2px bottom border for visual weight
- **Row Hover States** — Subtle purple tint on hover
- **Status Badges** — Gradient backgrounds with border accents
- **Info Tip** — Styled footer with info icon

#### Action Buttons
- **Larger Save Button** — More padding with shadow
- **Hover Effects** — Lift animation with enhanced shadow
- **Section Divider** — Border above action buttons

### 🗑️ Enhanced Trash System

Soft delete with 30-day retention and automatic cleanup.

#### Features
- **30-Day Retention** — Trashed items are kept for 30 days before automatic permanent deletion
- **Days Remaining Display** — Trash view shows how many days until each item is deleted (with red warning when ≤7 days)
- **Empty Trash Button** — One-click button to permanently delete all items in trash
- **Automatic Cleanup** — Old trashed items are cleaned up automatically on page load
- **Trashed Timestamp** — Records when items were moved to trash for accurate retention tracking

#### Usage
- Trash items as normal (they're now soft-deleted with a timestamp)
- View trash to see items and their days remaining
- Click "Empty Trash" to permanently delete all trashed items at once
- Items older than 30 days are automatically removed

### ⏰ Scheduled Publishing

Schedule posts to publish automatically at a future date and time.

#### Features
- **Schedule Toggle** — Checkbox in publish panel to enable scheduling
- **Date & Time Pickers** — Native date and time inputs for precise scheduling
- **Auto-Publish** — Scheduled posts automatically publish when their time arrives
- **Scheduled Status** — New purple "Scheduled" status badge with clock icon
- **Scheduled Filter** — Filter posts list to show only scheduled items
- **Flexible Options** — "Publish Now" button to immediately publish scheduled posts

#### Usage
1. In the post editor, check "Schedule for later" in the Publish panel
2. Set the date and time for publication
3. Click "Schedule" button
4. Post will automatically publish at the scheduled time

#### Technical Details
- Auto-publish runs on every page load (pseudo-cron)
- Scheduled posts show their publish date in the posts list
- Can reschedule or publish immediately at any time

### 🐛 Bug Fixes

- **Column Settings Fatal Error** — Fixed `Post::find()` being called with array instead of `Post::query()`
- **Column Resize Jumping** — Fixed columns shifting/jumping when clicking resize handle
- **Independent Column Widths** — Columns now resize independently without affecting others

---

## [0.1.5] - 2025-12-11

### 📋 Duplicate Post Feature

One-click post duplication with full content preservation.

#### Features
- **Clone Any Content** — Duplicate posts, pages, and custom post type entries
- **Complete Copy** — Copies title (with "Copy" suffix), content, excerpt, featured image
- **Custom Fields** — All meta data / custom field values are duplicated
- **Taxonomy Terms** — Categories, tags, and custom taxonomy assignments are preserved
- **Draft Status** — Duplicates are always created as drafts for review before publishing
- **Instant Edit** — Redirects to the new post editor immediately after duplication

#### Usage
- Click the copy icon button in the post list actions
- The duplicate opens in the editor ready to customize

### 🏷️ Taxonomies System

A complete taxonomy management system for organizing content with categories, tags, and custom taxonomies.

#### Core Features
- **Built-in Taxonomies** — Categories (hierarchical) and Tags (flat) for posts out of the box
- **Custom Taxonomies** — Create unlimited taxonomies for any post type
- **Hierarchical Support** — Parent/child relationships for category-like taxonomies
- **Flat Taxonomies** — Tag-like flat structure for simple grouping
- **Multi Post Type** — Assign taxonomies to multiple post types

#### Admin Interface
- **Taxonomies Page** — Manage all built-in and custom taxonomies
- **Terms Management** — Add, edit, delete terms with AJAX for smooth UX
- **Post Editor Integration** — Taxonomy selectors in post sidebar
- **Hierarchical Checkboxes** — Nested checkbox tree for categories
- **Tag-Style Pills** — Compact pill UI for flat taxonomies

#### Taxonomy Class API
- `Taxonomy::register()` — Register custom taxonomies
- `Taxonomy::getForPostType()` — Get taxonomies for a post type
- `Taxonomy::createTerm()` / `updateTerm()` / `deleteTerm()` — Term CRUD
- `Taxonomy::getTerms()` — Get all terms for a taxonomy
- `Taxonomy::getTermsTree()` — Get hierarchical term tree
- `Taxonomy::setPostTerms()` — Set terms for a post
- `Taxonomy::getPostTerms()` — Get terms assigned to a post
- `Taxonomy::getTermPosts()` — Get posts with a specific term

### 🧭 Menu Builder Improvements

#### Bug Fixes
- **AJAX Save/Delete** — Fixed Database method signatures causing save and delete errors
- **Post::permalink()** — Fixed undefined method error in `Menu::getItemUrl()`
- **Duplicate Prevention** — Menu items can no longer be added twice (except custom links)
- **Delete Modal** — Replaced JavaScript `confirm()` with styled modal dialog

#### Frontend Integration
- **Theme Support** — Both default and flavor themes now use the Menu system
- **Location Assignment** — Menus must be assigned to "Primary Navigation" location to display
- **Fallback** — Themes fall back to showing pages if no menu is assigned

#### UI Enhancements
- **Save Feedback** — Button shows "Saving..." with spinner, then success toast
- **Improved Toasts** — Larger, more visible notifications with gradient backgrounds
- **CPT Individual Posts** — Custom post types now show individual posts instead of archives

### 🎨 Admin Navigation Redesign

Compact, user-friendly sidebar navigation:

- **Smaller Sidebar** — Width reduced from 260px to 220px
- **Inline Icons** — Removed bulky icon boxes, icons now inline at 18-20px
- **Reduced Spacing** — More compact padding and gaps throughout
- **Cleaner Active State** — Simple colored icon instead of gradient box
- **Thinner Scrollbar** — 4px width with hover effects
- **Updated Spacing Settings** — Compact/Medium/Comfortable options refined

### 📁 New Files

```
includes/
└── taxonomy.php          # Taxonomy management class

admin/
├── taxonomies.php        # Taxonomies list page
├── taxonomy-edit.php     # Create/edit taxonomy
└── terms.php             # Terms management page
```

### 📁 Modified Files

```
includes/
├── config.php            — Version updated to 0.1.5
├── migrations.php        — Added taxonomy tables
├── menu.php              — Fixed Database method calls, permalink method
└── install.php           — Added taxonomy tables

admin/
├── menus.php             — Fixed AJAX, improved UI, delete modal
├── post-edit.php         — Added taxonomy selectors in sidebar
├── assets/css/admin.css  — Compact navigation styles
└── includes/sidebar.php  — Added Taxonomies link, smaller logo

index.php                 — Added Menu class for frontend

themes/
├── default/header.php    — Uses Menu system with fallback
└── flavor/functions.php  — flavor_nav_menu() uses Menu system
```

### 🎯 Theme Usage Example

```php
// Get categories for a post
$categories = Taxonomy::getPostTerms($post['id'], 'category');

// Display as links
foreach ($categories as $cat) {
    echo '<a href="' . Taxonomy::getTermUrl($cat) . '">' . esc($cat['name']) . '</a>';
}

// Display menu in theme (assign to "Primary Navigation" location in admin)
$menu = Menu::getMenuByLocation('primary');
if ($menu) {
    $items = Menu::getItems($menu['id']);
    foreach ($items as $item) {
        echo '<a href="' . Menu::getItemUrl($item) . '">' . esc($item['title']) . '</a>';
    }
}
```

---

## [0.1.4] - 2025-12-09

### 🧭 Menu Builder System

A complete drag-and-drop navigation menu management system.

#### Core Features
- **Visual Menu Builder** — Drag-and-drop interface for creating and organizing navigation menus
- **Nested Menu Items** — Full support for multi-level dropdown menus with unlimited depth
- **Multiple Menu Locations** — Assign menus to different theme locations (Primary, Footer, etc.)
- **Custom Links** — Add external URLs with custom link text
- **Content Integration** — Easily add Pages, Posts, and Custom Post Type archives to menus

#### Menu Item Options
- **Navigation Label** — Customize the display text for each menu item
- **Open in New Tab** — Option to open links in new tab/window
- **CSS Classes** — Add custom CSS classes for styling individual items
- **Real-time Saving** — Changes are saved automatically when reordering

#### Admin Interface
- **Two-Panel Layout** — Add items panel on left, menu structure on right
- **Collapsible Sections** — Pages, Posts, Post Type Archives, and Custom Links in expandable panels
- **Expandable Item Settings** — Click to expand each item and edit its properties
- **Live Reordering** — Drag items to reorder or nest them under other items
- **Toast Notifications** — Visual feedback for save, delete, and error actions

#### Theme Integration
- **Menu Class** — New `Menu` class for registering locations and displaying menus
- **Template Function** — Use `Menu::display('location')` in themes to output menus
- **Customizable Output** — Options for container, classes, depth limit, and more
- **Theme Locations** — Themes can register custom menu locations

#### Database
- **New Tables** — `menus` and `menu_items` tables for storing menu data
- **Efficient Structure** — Parent-child relationships with position ordering
- **Cascade Delete** — Deleting a menu removes all its items

### 🎨 UI Improvements
- **Themes Page Redesign** — Completely new layout with hero section and active theme showcase
- **Gradient Hero** — Purple gradient header with stats and action buttons
- **Active Theme Card** — Large preview with features list and quick actions
- **Hover Overlays** — Quick action buttons appear on theme card hover
- **Animated Cards** — Smooth lift and shadow effects on interaction

### 📁 New Files

```
includes/
└── menu.php              # Menu management class

admin/
└── menus.php             # Menu builder admin page
```

### 📁 Modified Files

```
includes/
├── config.php            — Version updated to 0.1.4
├── migrations.php        — Added menus and menu_items tables
└── functions.php         — Added 'menu' icon

admin/
├── themes.php            — Complete redesign with new layout
├── update.php            — Added menu tables to migrations
└── includes/sidebar.php  — Added Menus link in Design section

install.php               — Added menus and menu_items table creation
```

### 🎯 Theme Usage Example

```php
// In theme's functions.php - register a menu location
Menu::registerLocation('main-menu', 'Main Navigation');

// In theme template - display the menu
echo Menu::display('main-menu', [
    'container' => 'nav',
    'container_class' => 'main-navigation',
    'menu_class' => 'nav-menu',
    'submenu_class' => 'dropdown-menu',
]);
```

---

## [0.1.3] - 2025-12-09

### 📜 Post Revisions System

#### Core Features
- **Automatic Revisions** — Revisions are created automatically when updating any post, page, or custom post type
- **Configurable Limits** — Set maximum revisions per post type (0-100, or 0 to disable)
- **Revision Restore** — One-click restore to any previous revision with automatic backup of current state
- **Meta Data Preservation** — Custom field values are stored and restored with revisions

#### Compare Revisions Page
- **New Page: `compare-revisions.php`** — Dedicated page for comparing any two revisions
- **Inline Diff View** — Word-by-word diff highlighting additions (green) and deletions (red)
- **Side-by-Side View** — Toggle between inline and side-by-side comparison for content
- **Revision Selector** — Dropdown menus to select any two revisions or compare with current version
- **Visual Legend** — Clear indicators showing what additions and deletions look like
- **Restore Actions** — Restore either revision directly from the compare page

#### Settings Integration
- **Built-in Post Types** — Configure revision limits for Posts and Pages in Settings → Reading
- **Custom Post Types** — Each custom post type has its own max revisions setting in the post type editor
- **Default Limit** — 10 revisions per post type by default

#### Post Editor UI
- **Revisions Sidebar Card** — Shows revision count and list in the post editor
- **Revision List** — Displays up to 20 most recent revisions with timestamps and authors
- **Latest Indicator** — Visual indicator for the most recent revision
- **Restore Confirmation** — Confirmation dialog before restoring to prevent accidental changes
- **Compare Link** — "Compare Revisions" button when 2+ revisions exist

#### Database
- **New Table** — `post_revisions` table stores all revision data
- **Automatic Cleanup** — Old revisions beyond the limit are automatically deleted
- **Cascade Delete** — Revisions are deleted when a post is permanently deleted
- **Graceful Fallback** — System works even if revisions table doesn't exist yet

### 🐛 Bug Fixes
- **Publish Button** — Fixed issue where clicking "Publish" on new posts kept them in draft status
- **Status Buttons** — Replaced confusing status dropdown with clear "Save Draft" and "Publish" buttons
- **Field Key Prefix** — Custom field keys now auto-prefix with post type slug (e.g., `product_price`)
- **Missing Table Handling** — Post editor gracefully handles missing revisions table with helpful message

### 📁 New Files

```
admin/
└── compare-revisions.php    # Revision comparison page with diff view
```

### 📁 Modified Files

```
includes/
├── config.php          — Version updated to 0.1.3
├── migrations.php      — Added post_revisions table creation
└── post.php            — Added revision methods (createRevision, getRevisions, restoreRevision, etc.)

admin/
├── post-edit.php       — Added revision creation, revisions sidebar, restore functionality, compare link
├── post-type-edit.php  — Added max_revisions field, fixed field key auto-prefix
├── settings.php        — Added revision settings for Posts and Pages
├── update.php          — Added post_revisions table to migrations

install.php             — Added post_revisions table creation
```

---

## [0.1.2] - 2025-12-09

### 🎨 Theme System

#### Theme Settings
- **Per-Theme Customization** — Each theme can now have its own settings
- New admin page: `theme-settings.php` for managing theme options
- Settings categories: Colors, Hero Section, Features, Stats, CTA, Custom CSS
- Live preview with iframe-based real-time updates
- Settings persist per-theme in database

#### Multiple Themes
- **Default Theme** — Dark gradient design with animated background grid, glowing orbs, bento grid layout
- **Flavor Theme** — Light, minimal design with clean typography and soft shadows
- Each theme has unique landing page (`welcome.php`)
- Theme switching preserves individual theme settings

#### Theme Features
- Hero section with customizable title, subtitle, and buttons
- Feature cards (up to 6) with icons, titles, and descriptions
- Stats bar with customizable metrics
- CTA section with gradient background
- Custom CSS injection without file editing

### 🖼️ Media Library Redesign

#### Full-Screen Modal
- **New Modal Interface** — Replaced inline editing with full-screen modal
- Two-column layout: large preview area + editing sidebar
- Dark preview background for better image visibility
- Keyboard navigation: Arrow keys to browse, Escape to close

#### Navigation
- Previous/Next buttons with hover effects
- Counter badge showing current position (e.g., "3 / 24")
- Disabled states at boundaries
- Smooth transitions between images

#### Sidebar Design (Light Theme)
- Clean white background with subtle gray cards
- Section cards with icons: Information, Edit Details, File URL
- Purple gradient accent bar in header
- Larger, more accessible form inputs (0.875rem padding)
- 440px width for comfortable editing

#### Grid/List Views
- Toggle between visual grid and detailed list view
- Persistent view preference
- Responsive grid layout

### 📸 Thumbnails Page Redesign

#### Full-Screen Modal
- **Converted from Slide Panel** — Now uses full-screen modal like Media Library
- Two-column layout matching Media Library design
- Dark preview area with navigation controls
- Keyboard navigation support

#### Sidebar Design (Light Theme)
- 460px width for thumbnail size list
- Meta card showing filename and dimensions
- Scrollable thumbnail sizes section (max-height: 450px)
- Each size shows: name, dimensions, status badge, URL with copy button

#### Thumbnail Size Items
- White cards with subtle borders
- Status badges: green "OK" or red "MISSING"
- Gradient copy buttons with success state
- Hover effects on cards

### 🔌 Plugin System Enhancements

#### Comprehensive Documentation
- **72KB HTML Documentation** — Complete plugin development guide
- Located at `/docs/plugin-development.html`
- Covers: Hooks, Shortcodes, Settings API, AJAX, REST API, Widgets, Cron, Database
- Code examples for every feature
- Styled with VoidForge branding

#### Plugin Features
- Shortcode system with nested support
- Settings API with persistent storage
- AJAX handler registration
- Asset enqueueing (scripts/styles)
- Admin notices system
- Widget registration
- REST API extensions
- Scheduled tasks (cron)
- Database table helpers

#### Included Plugins
- **Starter Shortcodes** — 15+ ready-to-use shortcodes
- **Social Share** — Social sharing with settings page

### 🐛 Bug Fixes

#### Critical Fixes
- **Modal Function Conflict** — Fixed global `openModal()` collision between admin.js and media.php
- Renamed to `openMediaModal()` and `openThumbModal()` for unique namespacing
- Fixed click events not firing on media gallery items

#### JavaScript Improvements
- ES5 syntax for maximum browser compatibility
- Traditional for loops with closures for event handlers
- All functions defined before use
- Removed debug console.log statements

### 🎯 UI/UX Improvements

#### Accessibility
- Light theme sidebars (not dark) for better readability
- Larger touch targets (40px+ buttons)
- Higher contrast text (#1e293b on white)
- Larger font sizes throughout (0.875rem - 0.9375rem base)

#### Responsive Design
- Breakpoint at 1024px for modal layouts
- Stacked layout on smaller screens
- Sidebar becomes scrollable bottom panel on mobile
- Navigation buttons resize appropriately

#### Copy URL Feature
- Gradient purple copy buttons
- Success state with green color and checkmark
- Auto-reset after 1.5 seconds

### 📁 New Files

```
docs/
└── plugin-development.html    # 72KB plugin dev documentation

themes/
├── default/
│   └── welcome.php           # Dark gradient landing page
└── flavor/
    ├── index.php             # Theme entry point
    ├── header.php
    ├── footer.php
    ├── home.php
    ├── single.php
    ├── page.php
    ├── archive.php
    ├── welcome.php           # Light minimal landing page
    └── 404.php

admin/
├── themes.php                # Theme management
└── theme-settings.php        # Per-theme customization
```

### 📝 Modified Files

- `includes/config.php` — Version updated to 0.1.2
- `includes/functions.php` — Added `getThemeSettings()`, `saveThemeSettings()`
- `admin/media.php` — Complete modal redesign, light sidebar
- `admin/thumbnails.php` — Converted to modal, light sidebar
- `admin/includes/sidebar.php` — Added Themes menu section
- `README.md` — Updated for 0.1.2 features

---

## [0.1.1] - 2025-12-08

### 🎨 Major Rebrand
- **Renamed from Forge CMS to VoidForge CMS** — Complete rebrand with new identity
- New logo design featuring the distinctive "V" icon
- Updated color scheme with indigo/purple gradient (#6366f1 → #8b5cf6)
- New favicon (SVG format)
- Updated all references throughout the codebase

### ✨ New Features

#### Custom Fields System
- **New Custom Field Groups** — Create reusable field groups
- Assign field groups to any post type or users
- 14 field types supported
- Field groups automatically appear in post editor when assigned
- New admin pages: `custom-fields.php` and `custom-field-edit.php`

#### Admin Theme Enhancements
- **Granular Font Size Controls** — Separate settings for sidebar, header, and content
- Font sizes: Small (12px), Medium (14px), Large (16px)
- **Custom Color Scheme Management** — Save up to 5 custom color schemes
- Delete confirmation modal for color schemes

#### Icon Library Expansion
- **80+ Admin Icons** — Expanded from 16 to 80+ icons
- Icons organized by category

#### Landing Page
- **Stunning New Welcome Page** — Complete overhaul
- Dark theme with animated background grid
- Glowing orb effects and gradient text
- Feature showcase with 6 feature cards
- Stats bar and bento grid layout

### 🔧 Improvements

#### UI/UX Consistency
- **Unified Structure Pages** — Post Types and Custom Fields share CSS classes
- Removed inline `<style>` blocks from structure pages
- Consistent button styling across all pages

#### Delete Confirmations
- All delete actions now use proper modal dialogs
- No more JavaScript `confirm()` alerts

### 🐛 Bug Fixes
- **Plugin Class Error** — Added missing `require_once` for Plugin class
- **Update System Network Errors** — Fixed curl configuration
- **Date Format Display** — Fixed corrupted date format
- **Homepage Setting** — Fresh installs no longer set a homepage by default
- **Custom Fields Integration** — Fixed `get_post_type_fields()` to include field groups

---

## [0.1.0] - 2025-12-08

### Initial Release
- Core CMS functionality
- Custom post types with custom fields
- Media library with folder organization
- User management with roles (Admin, Editor, Subscriber)
- Plugin system with WordPress-style hooks/filters
- Theme system with PHP templates
- Admin dashboard with stats
- WYSIWYG content editor
- Auto-update system with backups
- Security features (CSRF, XSS protection, secure sessions)
- Live CSS customizer for admin and frontend
- Thumbnail generation system
- Search functionality
- Homepage selection

---

## Version History

| Version | Date | Highlights |
|---------|------|------------|
| 0.3.1 | 2025-12-28 | Home page redesign (light mode), complete comments overhaul, media library fixes, time-ago timestamps |
| 0.3.0 | 2025-12-27 | Complete SEO tools: meta tags, Open Graph, Twitter Cards, JSON-LD schema, XML sitemap, robots.txt, SEO analysis with scoring |
| 0.2.5 | 2025-12-23 | Anvil plugin architecture, frontend block CSS for all 21 blocks, testimonial style variants, accordion HTML5 fixes, CSS loading via theme |
| 0.2.4.1 | 2025-12-21 | Frontend admin bar, site identity settings, Flavor theme redesign (home, header, footer, 404), code showcase, comparison table |
| 0.2.4 | 2025-12-20 | Anvil Live enhancements |
| 0.2.3.1 | 2025-12-19 | Orphaned comments cleanup, frontend comments redesign, styled error pages, installer step redesign, dashboard theme colors |
| 0.2.3 | 2025-12-19 | Elementor-style visual editor with typography, colors, borders, shadows, backgrounds, animations, transforms |
| 0.2.2 | 2025-12-16 | Anvil Live visual frontend editor, drag-drop blocks, inline editing, columns support |
| 0.2.1 | 2025-12-15 | REST API with API key management, modern installer redesign, dashboard redesign, modal confirmations |
| 0.2.0 | 2025-12-15 | Anvil block editor with 15 blocks, class-based architecture, Flavor theme with block showcase |
| 0.1.8 | 2025-12-13 | Comments system with threading, moderation, guest commenting, admin management |
| 0.1.7 | 2025-12-12 | Bulk actions (trash, publish, draft, taxonomy assignment), Quick Edit (inline editing with AJAX) |
| 0.1.6.2 | 2025-12-12 | Login screen editor with 80+ settings, 12 presets, pattern backgrounds, live preview |
| 0.1.6.1 | 2025-12-12 | Repeater & group fields, sub-field modal, field key prefixing, radio buttons |
| 0.1.6 | 2025-12-12 | Admin columns manager, column settings UI redesign, enhanced trash (30-day retention), scheduled publishing, column resize fix |
| 0.1.5 | 2025-12-11 | Duplicate post, taxonomies system, menu builder fixes, compact admin navigation |
| 0.1.4 | 2025-12-09 | Menu builder system, themes page redesign |
| 0.1.3 | 2025-12-09 | Post revisions system, publish button fix, field key prefix |
| 0.1.2 | 2025-12-09 | Theme system, Media/Thumbnails modal redesign, Plugin docs |
| 0.1.1 | 2025-12-08 | VoidForge rebrand, Custom fields, 80+ icons |
| 0.1.0 | 2025-12-08 | Initial release |

---

**VoidForge CMS** — Modern Content Management
