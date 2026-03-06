<?php
if (!defined('ABSPATH')) {
    exit;
}

class DSS_Theme_Branding
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_branding_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('wp_prepare_themes_for_js', array($this, 'apply_branding_to_js'));

        // Admin Bar Branding
        add_action('admin_bar_menu', array($this, 'remove_wp_logo'), 0);
        add_action('wp_before_admin_bar_render', array($this, 'add_custom_logo'), 0);

        // Footer Branding
        add_filter('admin_footer_text', array($this, 'custom_admin_footer'));

        // Update Suppression
        if (get_option('ctb_hide_updates') === '1') {
            add_action('admin_init', array($this, 'suppress_updates'));
        }

        // Admin Notices Suppression
        if (get_option('ctb_hide_notices') === '1') {
            add_action('admin_head', array($this, 'suppress_admin_notices'), 1);
        }

        // SVG Upload Support
        if (get_option('ctb_allow_svg') === '1') {
            add_filter('upload_mimes', array($this, 'allow_svg_uploads'));
            add_filter('wp_check_filetype_and_ext', array($this, 'fix_svg_filetype'), 10, 4);
        }
    }

    public function enqueue_branding_assets($hook)
    {
        wp_enqueue_style('dss-white-label-admin', DSS_WHITE_LABEL_PLUGIN_URL . 'assets/css/white-label-admin.css', array(), DSS_WHITE_LABEL_VERSION);

        if ('dss-suite_page_custom-theme-branding' !== $hook) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('dss-admin-js', DSS_WHITE_LABEL_PLUGIN_URL . 'admin/js/dss-admin.js', array('jquery'), DSS_WHITE_LABEL_VERSION, true);
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'dss-suite',
            'Centro de control',
            'Centro de control',
            'manage_options',
            'custom-theme-branding',
            array($this, 'settings_page_html')
        );
    }

    public function register_settings()
    {
        register_setting('ctb_settings_group', 'ctb_theme_name');
        register_setting('ctb_settings_group', 'ctb_theme_author');
        register_setting('ctb_settings_group', 'ctb_theme_uri');
        register_setting('ctb_settings_group', 'ctb_theme_desc');
        register_setting('ctb_settings_group', 'ctb_theme_screenshot');
        register_setting('ctb_settings_group', 'ctb_admin_bar_logo');
        register_setting('ctb_settings_group', 'ctb_footer_text');
        register_setting('ctb_settings_group', 'ctb_hide_updates');
        register_setting('ctb_settings_group', 'ctb_hide_notices');
        register_setting('ctb_settings_group', 'ctb_allow_svg');
    }

    public function settings_page_html()
    {
        if (isset($_GET['settings-updated'])) {
            DSS_Notifications::get_instance()->add_persistent('La identidad visual del tema se ha actualizado correctamente.', 'success', 'Marca Guardada');
        }
        require_once DSS_WHITE_LABEL_PLUGIN_DIR . 'admin/views/view-settings-page.php';
    }

    public function apply_branding_to_js($themes)
    {
        $custom_name = get_option('ctb_theme_name');
        $custom_author = get_option('ctb_theme_author');
        $custom_uri = get_option('ctb_theme_uri');
        $custom_desc = get_option('ctb_theme_desc');
        $custom_img = get_option('ctb_theme_screenshot');

        $current_theme_slug = get_stylesheet();

        if (isset($themes[$current_theme_slug])) {
            if (!empty($custom_name)) {
                $themes[$current_theme_slug]['name'] = $custom_name;
            }
            if (!empty($custom_author)) {
                $themes[$current_theme_slug]['author'] = $custom_author;
            }
            if (!empty($custom_desc)) {
                $themes[$current_theme_slug]['description'] = $custom_desc;
            }
            if (!empty($custom_img)) {
                $themes[$current_theme_slug]['screenshot'] = array(esc_url($custom_img));
            }
            if (!empty($custom_uri)) {
                $themes[$current_theme_slug]['authorAndUri'] = '<a href="' . esc_url($custom_uri) . '">' . esc_html($custom_author) . '</a>';
            }
        }
        return $themes;
    }

    /**
     * Remove the default WordPress logo from admin bar.
     */
    public function remove_wp_logo($wp_admin_bar)
    {
        $wp_admin_bar->remove_node('wp-logo');
    }

    /**
     * Add custom logo or site name to admin bar.
     */
    public function add_custom_logo()
    {
        $logo_url = get_option('ctb_admin_bar_logo');
        if (!$logo_url) {
            return;
        }

        ?>
        <style>
            #wpadminbar #wp-admin-bar-site-name>.ab-item:before {
                content: '';
                background-image: url('<?php echo esc_url($logo_url); ?>');
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                display: inline-block;
                width: 20px;
                height: 20px;
                margin-right: 8px;
                vertical-align: middle;
            }
        </style>
        <?php
    }

    /**
     * Customize the admin footer text.
     */
    public function custom_admin_footer($text)
    {
        $custom_text = get_option('ctb_footer_text');
        if (!empty($custom_text)) {
            return wp_kses_post($custom_text);
        }
        return $text;
    }

    /**
     * Suppress core, theme, and plugin updates.
     */
    public function suppress_updates()
    {
        remove_action('admin_notices', 'update_nag', 3);
        remove_action('network_admin_notices', 'update_nag', 3);
        add_filter('pre_site_transient_update_core', '__return_null');
        add_filter('pre_site_transient_update_plugins', '__return_null');
        add_filter('pre_site_transient_update_themes', '__return_null');
    }

    /**
     * Suppress all admin notices.
     */
    public function suppress_admin_notices()
    {
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
        remove_all_actions('user_admin_notices');
    }

    /**
     * Allow SVG in WordPress media uploads.
     */
    public function allow_svg_uploads($mimes)
    {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        return $mimes;
    }

    /**
     * Fix filetype check for SVG (WordPress blocks it by default).
     */
    public function fix_svg_filetype($data, $file, $filename, $mimes)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, array('svg', 'svgz'))) {
            $data['ext']  = $ext;
            $data['type'] = 'image/svg+xml';
            $this->sanitize_svg($file);
        }
        return $data;
    }

    /**
     * Strip potentially dangerous elements from an SVG file.
     * Removes <script> tags and inline event handlers (onload, onclick, etc.).
     */
    private function sanitize_svg($file_path)
    {
        if (!file_exists($file_path) || !is_writable($file_path)) {
            return;
        }

        $content = file_get_contents($file_path);
        if (false === $content) {
            return;
        }

        // Remove <script> blocks
        $content = preg_replace('/<script[\s\S]*?<\/script>/i', '', $content);

        // Remove on* event attributes (onclick, onload, onerror, etc.)
        $content = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $content);

        // Remove javascript: hrefs and src values
        $content = preg_replace('/(?:href|src|xlink:href)\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '', $content);

        file_put_contents($file_path, $content);
    }
}
