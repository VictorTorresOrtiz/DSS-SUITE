<?php
/*
 * Module: Content Sorter
 * Description: Ordenamiento manual (Drag & Drop) para CPTs y Taxonomías.
 */

if (!defined('ABSPATH'))
    exit;

define('DSS_SORTER_DIR', plugin_dir_path(__FILE__));
define('DSS_SORTER_URL', plugin_dir_url(__FILE__));

// ──────────────────────────────────────
// Admin Menu
// ──────────────────────────────────────
add_action('admin_menu', function () {
    add_submenu_page(
        'dss-suite',
        'Content Sorter',
        'Content Sorter',
        'manage_options',
        'dss-content-sorter',
        'dss_sorter_render_page'
    );
});

// ──────────────────────────────────────
// Settings Page
// ──────────────────────────────────────
function dss_sorter_render_page()
{
    if (!current_user_can('manage_options'))
        return;

    wp_enqueue_style('dss-sorter-admin', DSS_SORTER_URL . 'assets/css/sorter-admin.css', array('dashicons'), DSS_SUITE_VERSION);
    wp_enqueue_script('dss-sorter-admin', DSS_SORTER_URL . 'assets/js/sorter-admin.js', array('jquery', 'jquery-ui-sortable'), DSS_SUITE_VERSION, true);
    wp_localize_script('dss-sorter-admin', 'dssSorter', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dss_sorter_nonce'),
    ));

    $post_types = get_post_types(array('public' => true), 'objects');
    $taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');

    ?>
    <div class="wrap dss-sorter-container">
        <div class="dss-sorter-header">
            <h1><span class="dashicons dashicons-sort" style="font-size:28px;width:28px;height:28px;color:#2271b1;"></span> Content Sorter</h1>
            <span class="dss-badge">Drag & Drop</span>
        </div>

        <p class="dss-sorter-desc">
            Selecciona un tipo de contenido o taxonomía y ordena sus elementos arrastrando y soltando.
        </p>

        <div class="dss-sorter-selector">
            <div class="dss-sorter-group">
                <label>Tipo de contenido</label>
                <select id="dss-sorter-source">
                    <option value="">— Seleccionar —</option>
                    <optgroup label="Entradas (CPT)">
                        <?php foreach ($post_types as $pt): ?>
                            <?php if (in_array($pt->name, array('attachment')))
                                continue; ?>
                            <option value="cpt:<?php echo esc_attr($pt->name); ?>">
                                <?php echo esc_html($pt->labels->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Taxonomías">
                        <?php foreach ($taxonomies as $tax): ?>
                            <option value="tax:<?php echo esc_attr($tax->name); ?>">
                                <?php echo esc_html($tax->labels->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
        </div>

        <div class="dss-sorter-card" id="dss-sorter-panel" style="display:none;">
            <div class="dss-sorter-toolbar">
                <span class="dss-sorter-count" id="dss-sorter-count"></span>
                <span class="dss-sorter-hint"><span class="dashicons dashicons-move"></span> Arrastra para reordenar</span>
            </div>
            <ul class="dss-sortable-list" id="dss-sortable-list"></ul>
            <div class="dss-sorter-actions">
                <button type="button" class="button button-primary button-large" id="dss-sorter-save" disabled>
                    <span class="dashicons dashicons-saved" style="vertical-align:middle;"></span> Guardar Orden
                </button>
                <span class="dss-sorter-save-status" id="dss-sorter-status"></span>
            </div>
        </div>
    </div>
    <?php
}

// ──────────────────────────────────────
// AJAX: Load items
// ──────────────────────────────────────
add_action('wp_ajax_dss_sorter_load', 'dss_sorter_ajax_load');
function dss_sorter_ajax_load()
{
    check_ajax_referer('dss_sorter_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Sin permisos.');

    $source = sanitize_text_field($_POST['source'] ?? '');
    if (empty($source))
        wp_send_json_error('Fuente no especificada.');

    list($type, $slug) = explode(':', $source, 2);
    $items = array();

    if ($type === 'cpt') {
        $posts = get_posts(array(
            'post_type' => $slug,
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ));
        foreach ($posts as $p) {
            $items[] = array(
                'id' => $p->ID,
                'title' => $p->post_title ?: '(Sin título)',
                'order' => $p->menu_order,
            );
        }
    } elseif ($type === 'tax') {
        $terms = get_terms(array(
            'taxonomy' => $slug,
            'hide_empty' => false,
            'orderby' => 'meta_value_num',
            'meta_key' => 'dss_term_order',
            'order' => 'ASC',
        ));

        // If no terms have the meta yet, fall back to name order
        if (is_wp_error($terms)) {
            $terms = array();
        }

        $has_order = false;
        foreach ($terms as $t) {
            $order = (int) get_term_meta($t->term_id, 'dss_term_order', true);
            if ($order > 0)
                $has_order = true;
            $items[] = array(
                'id' => $t->term_id,
                'title' => $t->name,
                'count' => $t->count,
                'order' => $order,
            );
        }

        if (!$has_order) {
            // Sort alphabetically as default
            usort($items, function ($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            });
        }
    } else {
        wp_send_json_error('Tipo no válido.');
    }

    wp_send_json_success(array(
        'type' => $type,
        'slug' => $slug,
        'items' => $items,
    ));
}

// ──────────────────────────────────────
// AJAX: Save order
// ──────────────────────────────────────
add_action('wp_ajax_dss_sorter_save', 'dss_sorter_ajax_save');
function dss_sorter_ajax_save()
{
    check_ajax_referer('dss_sorter_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Sin permisos.');

    $source = sanitize_text_field($_POST['source'] ?? '');
    $order = isset($_POST['order']) && is_array($_POST['order']) ? array_map('intval', $_POST['order']) : array();

    if (empty($source) || empty($order))
        wp_send_json_error('Datos incompletos.');

    list($type, $slug) = explode(':', $source, 2);

    if ($type === 'cpt') {
        foreach ($order as $position => $post_id) {
            wp_update_post(array(
                'ID' => $post_id,
                'menu_order' => $position,
            ));
        }
    } elseif ($type === 'tax') {
        foreach ($order as $position => $term_id) {
            update_term_meta($term_id, 'dss_term_order', $position);
        }
    } else {
        wp_send_json_error('Tipo no válido.');
    }

    if (class_exists('DSS_Notifications')) {
        DSS_Notifications::get_instance()->add('Orden guardado correctamente.', 'success', 'Content Sorter');
    }

    wp_send_json_success();
}

// ──────────────────────────────────────
// Frontend: Apply CPT order
// ──────────────────────────────────────
add_action('pre_get_posts', 'dss_sorter_apply_cpt_order');
function dss_sorter_apply_cpt_order($query)
{
    if (is_admin())
        return;

    $post_type = $query->get('post_type');
    if (empty($post_type))
        return;

    $sortable_types = get_post_types(array('public' => true));
    unset($sortable_types['attachment']);

    $is_sortable = is_string($post_type) ? isset($sortable_types[$post_type]) : (is_array($post_type) && !empty(array_intersect($post_type, array_keys($sortable_types))));

    if (!$is_sortable)
        return;

    // Only apply if there are posts with menu_order > 0 for this type
    if (!$query->get('orderby')) {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }
}

// ──────────────────────────────────────
// Frontend: Apply Taxonomy term order
// ──────────────────────────────────────
add_filter('get_terms_defaults', 'dss_sorter_apply_tax_order', 10, 2);
function dss_sorter_apply_tax_order($defaults, $taxonomies)
{
    if (is_admin())
        return $defaults;

    $public_taxonomies = get_taxonomies(array('public' => true));

    foreach ($taxonomies as $tax) {
        if (isset($public_taxonomies[$tax])) {
            $defaults['orderby'] = 'meta_value_num';
            $defaults['meta_key'] = 'dss_term_order';
            $defaults['order'] = 'ASC';
            break;
        }
    }

    return $defaults;
}

// ──────────────────────────────────────
// Admin list: Apply CPT order in edit.php
// ──────────────────────────────────────
add_action('pre_get_posts', 'dss_sorter_admin_cpt_order');
function dss_sorter_admin_cpt_order($query)
{
    if (!is_admin() || !$query->is_main_query())
        return;

    global $pagenow;
    if ($pagenow !== 'edit.php')
        return;

    $post_type = $query->get('post_type');
    if (empty($post_type))
        return;

    $query->set('orderby', 'menu_order');
    $query->set('order', 'ASC');
}
