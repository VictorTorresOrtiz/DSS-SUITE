<?php
if (!defined('ABSPATH')) {
    exit;
}

class FFL_Admin_Theme_Widgets
{

    public function __construct()
    {
        add_action('wp_dashboard_setup', array($this, 'setup_dashboard_widgets'), 999);
        add_action('admin_enqueue_scripts', array($this, 'dashboard_assets'));
    }

    public function dashboard_assets($hook)
    {
        if ('index.php' === $hook) {
            wp_enqueue_style('dss-admin-css', DSS_WHITE_LABEL_PLUGIN_URL . 'admin/css/dss-admin.css', array(), DSS_WHITE_LABEL_VERSION);
        }
    }

    public function setup_dashboard_widgets()
    {
        $this->remove_default_widgets();
        $this->add_custom_widgets();
    }

    public function remove_default_widgets()
    {
        global $wp_meta_boxes;

        // --- Core de WordPress ---
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
        remove_action('welcome_panel', 'wp_welcome_panel');

        // --- WooCommerce ---
        remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
        remove_meta_box('wc_admin_dashboard_setup', 'dashboard', 'normal');

        // --- Plugins ---
        remove_meta_box('rank_math_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('elementor_dashboard_overview', 'dashboard', 'normal');
        remove_meta_box('ogf_dashboard_widget', 'dashboard', 'normal');
        remove_meta_box('duplicator-pro-dashboard-widget', 'dashboard', 'normal');
    }

    public function add_custom_widgets()
    {
        $current_user = wp_get_current_user();
        $user_name = !empty($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name;

        wp_add_dashboard_widget(
            'dss_welcome_widget',
            'Bienvenido, ' . $user_name,
            array($this, 'render_welcome_widget')
        );

        if (class_exists('WooCommerce')) {
            wp_add_dashboard_widget(
                'dss_sales_widget',
                'Resumen de Ventas',
                array($this, 'render_sales_widget')
            );
        }

        global $wp_meta_boxes;
        $normal = isset($wp_meta_boxes['dashboard']['normal']['core']) ? $wp_meta_boxes['dashboard']['normal']['core'] : array();
        $side = isset($wp_meta_boxes['dashboard']['side']['core']) ? $wp_meta_boxes['dashboard']['side']['core'] : array();

        $ordered_normal = array();
        $ordered_side = array();

        if (isset($normal['dss_welcome_widget'])) {
            $ordered_normal['dss_welcome_widget'] = $normal['dss_welcome_widget'];
        }
        if (isset($normal['dss_sales_widget'])) {
            $ordered_side['dss_sales_widget'] = $normal['dss_sales_widget'];
        }

        $wp_meta_boxes['dashboard']['normal']['core'] = $ordered_normal;
        $wp_meta_boxes['dashboard']['side']['core'] = $ordered_side;
    }

    public function render_welcome_widget()
    {
        require DSS_WHITE_LABEL_PLUGIN_DIR . 'admin/views/view-widget-welcome.php';
    }

    public function render_sales_widget()
    {
        $today_sales = $this->get_sales_data('today');
        $week_sales = $this->get_sales_data('week');
        $month_sales = $this->get_sales_data('month');
        $pending_orders = $this->get_orders_count('processing');
        $on_hold_orders = $this->get_orders_count('on-hold');

        require DSS_WHITE_LABEL_PLUGIN_DIR . 'admin/views/view-widget-sales.php';
    }

    private function get_sales_data($period)
    {
        global $wpdb;

        switch ($period) {
            case 'today':
                $start = current_time('Y-m-d 00:00:00');
                $end = current_time('Y-m-d 23:59:59');
                break;
            case 'week':
                $start = date('Y-m-d 00:00:00', strtotime('monday this week', current_time('timestamp')));
                $end = current_time('Y-m-d 23:59:59');
                break;
            case 'month':
                $start = current_time('Y-m-01 00:00:00');
                $end = current_time('Y-m-d 23:59:59');
                break;
            default:
                $start = current_time('Y-m-d 00:00:00');
                $end = current_time('Y-m-d 23:59:59');
        }

        if (
            class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
        ) {
            $results = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
				 FROM {$wpdb->prefix}wc_orders
				 WHERE status IN ('wc-completed', 'wc-processing')
				 AND date_created_gmt BETWEEN %s AND %s",
                get_gmt_from_date($start),
                get_gmt_from_date($end)
            ));
        } else {
            $results = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) as count, COALESCE(SUM(pm.meta_value), 0) as total
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.post_type = 'shop_order'
				 AND p.post_status IN ('wc-completed', 'wc-processing')
				 AND pm.meta_key = '_order_total'
				 AND p.post_date BETWEEN %s AND %s",
                $start,
                $end
            ));
        }

        return array(
            'total' => $results ? (float) $results->total : 0,
            'count' => $results ? (int) $results->count : 0,
        );
    }

    private function get_orders_count($status)
    {
        if (function_exists('wc_orders_count')) {
            return wc_orders_count($status);
        }
        return 0;
    }
}
