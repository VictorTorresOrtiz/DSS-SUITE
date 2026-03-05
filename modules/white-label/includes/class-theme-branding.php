<?php
if (!defined('ABSPATH')) {
    exit;
}

class DSS_Theme_Branding
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('wp_prepare_themes_for_js', array($this, 'apply_branding_to_js'));
    }

    public function enqueue_media_scripts($hook)
    {
        if ('appearance_page_custom-theme-branding' !== $hook) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('dss-admin-js', DSS_WHITE_LABEL_PLUGIN_URL . 'admin/js/dss-admin.js', array('jquery'), DSS_WHITE_LABEL_VERSION, true);
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'dss-suite',
            'Marca del Tema',
            'Marca del Tema',
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
    }

    public function settings_page_html()
    {
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
}
