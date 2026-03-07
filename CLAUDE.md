# DSS SUITE - Project Context

## Overview

**DSS SUITE** is a modular WordPress plugin developed by Victor Torres Ortiz (DSS NETWORK).
It centralizes SEO tools, branding, AI-powered support, dashboard customization, and WooCommerce utilities under a single architecture.

- **Main file:** `dss-suite.php` (v3.2 in plugin header, `DSS_SUITE_VERSION` = `2.0.1`)
- **Website:** https://dssnetwork.es
- **Text domain:** `dss-suite`
- **License:** MIT
- **Git branch strategy:** `dev` for development, `main` for production

## Architecture: Core + Modules

The plugin uses a **lazy-loading modular system**. The core orchestrates module activation; inactive modules consume zero resources.

### Initialization Flow

1. `dss-suite.php` fires on `plugins_loaded`
2. Instantiates `DSS_Suite_Core` (in `includes/class-dss-suite-core.php`)
3. Core loads `DSS_Notifications` singleton
4. Registers admin menu (position 65, slug `dss-suite`)
5. Calls `load_modules()` — only loads modules enabled in `get_option('dss_suite_active_modules')`

### Module Registry

Modules are declared in `DSS_Suite_Core::$modules` array. Each entry has:
- `name`, `description`, `file` (relative to `modules/`)
- Optional `requires` for addon dependencies
- Optional `icon` (dashicons class), `premium` (bool), `beta` (bool)

### Current Modules

| Slug | Name | Type | File |
|------|------|------|------|
| `dashboard` | DSS Dashboard | Class-based (external) | `dashboard/admin-musik.php` |
| `seo-manager` | SEO Manager | Class-based | `seo-manager/seo-manager.php` |
| `white-label` | Widget & Theme Controller | Class-based | `white-label/white-label.php` |
| `cpt-sorter` | Content Sorter | Function-based | `cpt-sorter/function.php` |
| `chatbox` | Chatbox de Soporte | Class-based | `chatbox/chatbox.php` |
| `public-chat` | Chat Publico Beta | Class-based | `public-chat/public-chat.php` |
| `room-designer` | Room Designer (addon) | Class-based | `public-chat/addons/room-designer/room-designer.php` |
| `course-advisor` | Course Advisor (addon) | Class-based | `public-chat/addons/course-advisor/course-advisor.php` |
| `duplicate-finder` | Duplicate Finder | Function-based | `duplicate-finder/function.php` |
| `dss-connector` | DSS Connector (beta) | Class-based | `dss-connector/dss-connector.php` |

## Directory Structure

```
DSS-SUITE/
  dss-suite.php                          # Entry point
  CLAUDE.md                              # This file
  README.md
  LICENSE
  assets/                                # Global assets (notifications CSS/JS)
    css/dss-notifications.css
    js/dss-notifications.js
  includes/
    class-dss-suite-core.php             # Core orchestration
    class-dss-notifications.php          # Notification singleton
  modules/
    dashboard/                           # WP dashboard redesign
    seo-manager/                         # H1-H6 tag changer + SEO audit
    white-label/                         # Branding, widgets, theme control
    cpt-sorter/                          # Drag & drop ordering for CPTs
    chatbox/                             # Admin AI chat (Gemini)
    public-chat/                         # Frontend AI chatbot (Gemini)
      addons/room-designer/              # AI room designer addon
      addons/course-advisor/             # AI course advisor addon
    duplicate-finder/                    # WooCommerce duplicate product finder
    dss-connector/                       # Remote API for DSS Gestion
      includes/class-dss-connector-admin.php  # API key management UI
      includes/class-dss-connector-api.php    # AJAX endpoint handler
```

## Coding Patterns & Conventions

### Two Module Patterns

**Function-based** (simple modules like `cpt-sorter`, `duplicate-finder`):
- Single `function.php` with everything
- Constants: `DSS_<MODULE>_DIR`, `DSS_<MODULE>_URL`
- Menu via anonymous `add_action('admin_menu', function() { add_submenu_page('dss-suite', ...) })`
- AJAX handlers as standalone functions

**Class-based** (complex modules like `seo-manager`, `public-chat`, `dss-connector`):
- Loader file + `includes/class-*-admin.php`
- Constants + version define
- Constructor registers hooks
- Class naming: `DSS_<Module>_Admin`

### Cache Busting (IMPORTANT)

Always increment the module version constant (`DSS_<MODULE>_VERSION`) when modifying CSS or JS files. WordPress caches assets by version string (`?ver=X.X.X`), so without a version bump the browser will serve stale files. This applies to both module-level versions and `DSS_SUITE_VERSION`.

### Asset Enqueuing

```php
wp_enqueue_style('dss-<module>-admin', DSS_<MODULE>_URL . 'assets/css/<file>.css', array('dashicons'), DSS_SUITE_VERSION);
wp_enqueue_script('dss-<module>-admin', DSS_<MODULE>_URL . 'assets/js/<file>.js', array('jquery'), DSS_SUITE_VERSION, true);
wp_localize_script('handle', 'varName', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce'   => wp_create_nonce('dss_<module>_nonce'),
));
```

### AJAX Pattern

```php
add_action('wp_ajax_dss_<action>', 'handler_function');

function handler_function() {
    check_ajax_referer('dss_<module>_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Sin permisos.');
    // ... sanitize inputs, process, respond
    wp_send_json_success($data);
}
```

### Security

- All admin pages require `manage_options` capability
- Nonce verification on every AJAX request and form submission
- Input sanitization: `sanitize_text_field()`, `sanitize_textarea_field()`, `intval()`
- Critical pages protected by Master Key (`DSS_MASTER_KEY` in wp-config.php, 8h transient)
- DSS Connector authenticates via API Key (`X-DSS-Key` header or POST param) with `hash_equals()`

### CSS Design System

- Primary blue: `#2271b1`
- Neutral grays: `#64748b`, `#f8fafc`, `#e2e8f0`, `#1e293b`
- Cards: `background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05)`
- Badge: `.dss-badge` — blue pill with uppercase text
- Premium badge: `.dss-premium-badge` — amber gradient pill
- Beta badge: `.dss-beta-badge` — amber outline pill
- BEM-like naming: `.dss-<module>-<element>`
- Language in Spanish for UI text

### Notifications

```php
DSS_Notifications::get_instance()->add_persistent('Message', 'success', 'Title');
DSS_Notifications::get_instance()->add('Message', 'success', 'Title', 5000);
```

## External Dependencies

- **WooCommerce** — Required by `duplicate-finder` and `room-designer`
- **Polylang** — Optional multilanguage plugin; `duplicate-finder` detects it via `pll_get_post_language()`
- **Gemini API** — Used by `chatbox`, `public-chat`, and `room-designer` (key stored in `dss_suite_gemini_api_key` option)

## Options (wp_options)

| Option Key | Purpose |
|-----------|---------|
| `dss_suite_active_modules` | Array of active module slugs (`slug => '1'`) |
| `dss_suite_gemini_api_key` | Global Gemini API key |
| `dss_suite_invoice_number` | License/invoice number |
| `tag_changer_rules` | SEO Manager rules |
| `dss_connector_api_key` | DSS Connector API key for remote auth |
| `dss_dupfinder_rollback_<user_id>` | Transient for duplicate finder rollback |

## How to Add a New Module

1. Create folder in `modules/<slug>/`
2. Create entry file (e.g., `function.php` or `<slug>.php`)
3. Register in `DSS_Suite_Core::$modules` array in `includes/class-dss-suite-core.php`
4. Use `add_submenu_page('dss-suite', ...)` for admin menu
5. Follow existing asset/AJAX/security patterns
6. Assets go in `modules/<slug>/assets/{css,js}/`
