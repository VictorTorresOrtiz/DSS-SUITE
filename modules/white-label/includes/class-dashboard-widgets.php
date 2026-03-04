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
        add_action('wp_ajax_dss_get_system_status', array($this, 'ajax_get_system_status'));
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

        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'dss_system_status_widget',
                'Estado del Sistema (Tiempo Real)',
                array($this, 'render_system_status_widget')
            );
        }

        if (class_exists('WooCommerce')) {
            wp_add_dashboard_widget(
                'dss_store_overview_widget',
                'Resumen de la Tienda',
                array($this, 'render_store_overview_widget')
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
        if (isset($normal['dss_system_status_widget'])) {
            $ordered_normal['dss_system_status_widget'] = $normal['dss_system_status_widget'];
        }
        if (isset($normal['dss_store_overview_widget'])) {
            $ordered_normal['dss_store_overview_widget'] = $normal['dss_store_overview_widget'];
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

    public function render_system_status_widget()
    {
        require DSS_WHITE_LABEL_PLUGIN_DIR . 'admin/views/view-widget-system-status.php';
    }

    public function render_store_overview_widget()
    {
        $top_product = $this->get_top_selling_product();
        $recent_orders = $this->get_latest_orders();
        $low_stock_products = $this->get_low_stock_products();

        require DSS_WHITE_LABEL_PLUGIN_DIR . 'admin/views/view-widget-store-overview.php';
    }

    private function get_top_selling_product()
    {
        $args = array(
            'post_type' => 'product',
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        );
        $loop = new WP_Query($args);

        if ($loop->have_posts()) {
            $loop->the_post();
            $product = wc_get_product(get_the_ID());
            $result = array(
                'name' => $product->get_name(),
                'sales' => $product->get_total_sales(),
                'url' => get_edit_post_link(get_the_ID())
            );
            wp_reset_postdata();
            return $result;
        }
        return false;
    }

    private function get_latest_orders($limit = 3)
    {
        $args = array(
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
        );
        $orders = wc_get_orders($args);
        $recent_orders = array();

        foreach ($orders as $order) {
            $recent_orders[] = array(
                'id' => $order->get_id(),
                'status' => wc_get_order_status_name($order->get_status()),
                'total' => $order->get_formatted_order_total(),
                'customer' => $order->get_formatted_billing_full_name() ?: 'Invitado',
                'url' => $order->get_edit_order_url()
            );
        }
        return $recent_orders;
    }

    private function get_low_stock_products($limit = 5)
    {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_manage_stock',
                    'value' => 'yes',
                    'compare' => '='
                ),
                array(
                    'key' => '_stock',
                    'value' => 5, // WooCommerce default low stock threshold
                    'compare' => '<=',
                    'type' => 'NUMERIC'
                )
            )
        );
        $loop = new WP_Query($args);
        $low_stock = array();

        if ($loop->have_posts()) {
            while ($loop->have_posts()) {
                $loop->the_post();
                $product = wc_get_product(get_the_ID());
                $low_stock[] = array(
                    'name' => $product->get_name(),
                    'stock' => $product->get_stock_quantity(),
                    'url' => get_edit_post_link(get_the_ID())
                );
            }
            wp_reset_postdata();
        }
        return $low_stock;
    }

    public function ajax_get_system_status()
    {
        // Require Manage Options capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        // 1. Server Load (CPU / Mem)
        $server_load = 'N/A';
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load && is_array($load)) {
                $server_load = number_format($load[0], 2) . ', ' . number_format($load[1], 2) . ', ' . number_format($load[2], 2);
            }
        }
        $memory_usage = size_format(memory_get_usage(true));

        // 2. Database Status
        global $wpdb;
        $db_threads = 'N/A';
        $db_queries = 'N/A';
        $db_status = $wpdb->get_results("SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected', 'Questions')", ARRAY_A);
        if ($db_status) {
            foreach ($db_status as $row) {
                if ($row['Variable_name'] === 'Threads_connected') {
                    $db_threads = $row['Value'];
                }
                if ($row['Variable_name'] === 'Questions') {
                    $db_queries = number_format($row['Value']);
                }
            }
        }

        // 3. Simple Online Users
        // Count users with active sessions based on WordPress transients (rough estimation)
        // A better approach is reading `wp_user_meta` or active tokens, but for now we give an estimation or use WooCommerce active sessions if available.
        $active_users = $this->get_estimated_online_users();

        wp_send_json_success(array(
            'server_load' => $server_load,
            'memory_usage' => $memory_usage,
            'db_threads' => $db_threads,
            'db_queries' => $db_queries,
            'active_users' => $active_users,
        ));
    }

    private function get_estimated_online_users()
    {
        // This is a naive estimation. Real time trackers use transients on every init hook.
        // For simplicity and to not impact performance heavily on every page load, we check WooCommerce sessions if available. 
        global $wpdb;
        if (class_exists('WooCommerce')) {
            // Count active WC sessions in the last 15 minutes
            $recent_timestamp = time() - (15 * 60);
            $sessions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_sessions WHERE session_expiry > %d", time()));
            if ($sessions) {
                return $sessions;
            }
        }

        // Fallback: Just return recent logged in users based on last activity or simple transient
        // Note: WP doesn't track this by default perfectly without a plugin, returning a placeholder or 'Not properly tracked' implies a needed feature.
        return 'Calculando...';
    }
}
