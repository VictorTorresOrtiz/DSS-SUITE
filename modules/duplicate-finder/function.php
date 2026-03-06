<?php
/*
 * Module: Duplicate Finder
 * Description: Encuentra productos duplicados en WooCommerce por título o SKU.
 */

if (!defined('ABSPATH'))
    exit;

define('DSS_DUPFINDER_DIR', plugin_dir_path(__FILE__));
define('DSS_DUPFINDER_URL', plugin_dir_url(__FILE__));

// ──────────────────────────────────────
// Admin Menu
// ──────────────────────────────────────
add_action('admin_menu', function () {
    add_submenu_page(
        'dss-suite',
        'Duplicate Finder',
        'Duplicate Finder',
        'manage_options',
        'dss-duplicate-finder',
        'dss_dupfinder_render_page'
    );
});

// ──────────────────────────────────────
// Settings Page
// ──────────────────────────────────────
function dss_dupfinder_render_page()
{
    if (!current_user_can('manage_options'))
        return;

    wp_enqueue_style('dss-dupfinder-admin', DSS_DUPFINDER_URL . 'assets/css/duplicate-finder-admin.css', array('dashicons'), DSS_SUITE_VERSION);
    wp_enqueue_script('dss-dupfinder-admin', DSS_DUPFINDER_URL . 'assets/js/duplicate-finder-admin.js', array('jquery'), DSS_SUITE_VERSION, true);
    wp_localize_script('dss-dupfinder-admin', 'dssDupFinder', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dss_dupfinder_nonce'),
    ));

    ?>
    <div class="wrap dss-dupfinder-container">
        <div class="dss-dupfinder-header">
            <h1><span class="dashicons dashicons-controls-repeat" style="font-size:28px;width:28px;height:28px;color:#2271b1;"></span> Duplicate Finder</h1>
            <span class="dss-badge">WooCommerce</span>
        </div>

        <p class="dss-dupfinder-desc">
            Escanea tu catálogo de WooCommerce para encontrar productos duplicados por título o SKU.
        </p>

        <div class="dss-dupfinder-controls">
            <div class="dss-dupfinder-group">
                <label>Criterio de búsqueda</label>
                <select id="dss-dupfinder-criteria">
                    <option value="title">Título exacto</option>
                    <option value="sku">Mismo SKU</option>
                    <option value="both">Título o SKU</option>
                </select>
            </div>
            <div class="dss-dupfinder-group">
                <label>Estado del producto</label>
                <select id="dss-dupfinder-status">
                    <option value="any">Todos</option>
                    <option value="publish">Publicados</option>
                    <option value="draft">Borradores</option>
                </select>
            </div>
            <button type="button" class="button button-primary button-large" id="dss-dupfinder-scan">
                <span class="dashicons dashicons-search" style="vertical-align:middle;margin-top:-2px;"></span> Escanear Duplicados
            </button>
        </div>

        <div class="dss-dupfinder-card" id="dss-dupfinder-results" style="display:none;">
            <div class="dss-dupfinder-toolbar">
                <span class="dss-dupfinder-count" id="dss-dupfinder-count"></span>
                <div class="dss-dupfinder-toolbar-actions">
                    <button type="button" class="button dss-btn-rollback" id="dss-dupfinder-rollback" style="display:none;">
                        <span class="dashicons dashicons-undo" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-top:-2px;"></span> Rollback
                    </button>
                    <button type="button" class="button button-primary dss-btn-bulk-trash" id="dss-dupfinder-bulk-trash" style="display:none;">
                        <span class="dashicons dashicons-trash" style="font-size:16px;width:16px;height:16px;vertical-align:middle;margin-top:-2px;"></span> Borrar duplicados por idioma
                    </button>
                </div>
            </div>
            <div id="dss-dupfinder-list"></div>
            <div class="dss-dupfinder-pagination" id="dss-dupfinder-pagination" style="display:none;"></div>
        </div>

        <div class="dss-dupfinder-loading" id="dss-dupfinder-loading" style="display:none;">
            <span class="spinner is-active" style="float:none;margin:0 10px 0 0;"></span>
            Escaneando productos...
        </div>
    </div>
    <?php
}

// ──────────────────────────────────────
// AJAX: Scan for duplicates
// ──────────────────────────────────────
add_action('wp_ajax_dss_dupfinder_scan', 'dss_dupfinder_ajax_scan');
function dss_dupfinder_ajax_scan()
{
    check_ajax_referer('dss_dupfinder_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Sin permisos.');

    if (!class_exists('WooCommerce'))
        wp_send_json_error('WooCommerce no está activo.');

    $criteria = sanitize_text_field($_POST['criteria'] ?? 'title');
    $status = sanitize_text_field($_POST['status'] ?? 'any');

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => $status === 'any' ? array('publish', 'draft', 'pending', 'private') : $status,
        'fields' => 'ids',
    );

    $product_ids = get_posts($args);

    if (empty($product_ids))
        wp_send_json_success(array('groups' => array(), 'total' => 0));

    $has_polylang = function_exists('pll_get_post_language');

    $products_data = array();
    foreach ($product_ids as $pid) {
        $sku = get_post_meta($pid, '_sku', true);
        $title = get_the_title($pid);
        $lang = $has_polylang ? pll_get_post_language($pid, 'slug') : '';
        $lang_name = $has_polylang ? pll_get_post_language($pid, 'name') : '';
        $products_data[] = array(
            'id' => $pid,
            'title' => $title,
            'sku' => $sku ?: '',
            'lang' => $lang ?: '',
            'lang_name' => $lang_name ?: '',
            'status' => get_post_status($pid),
            'price' => get_post_meta($pid, '_price', true),
            'stock' => get_post_meta($pid, '_stock_status', true),
            'edit_url' => get_edit_post_link($pid, 'raw'),
            'view_url' => get_permalink($pid),
            'thumbnail' => get_the_post_thumbnail_url($pid, 'thumbnail') ?: '',
        );
    }

    $groups = array();

    if ($criteria === 'title' || $criteria === 'both') {
        $by_title = array();
        foreach ($products_data as $p) {
            $key = mb_strtolower(trim($p['title']));
            if ($key === '')
                continue;
            $by_title[$key][] = $p;
        }
        foreach ($by_title as $key => $items) {
            if (count($items) > 1) {
                $groups['title:' . $key] = array(
                    'reason' => 'Título duplicado',
                    'match' => $items[0]['title'],
                    'items' => $items,
                );
            }
        }
    }

    if ($criteria === 'sku' || $criteria === 'both') {
        $by_sku = array();
        foreach ($products_data as $p) {
            if (empty($p['sku']))
                continue;
            $key = mb_strtolower(trim($p['sku']));
            $by_sku[$key][] = $p;
        }
        foreach ($by_sku as $key => $items) {
            if (count($items) > 1) {
                $group_key = 'sku:' . $key;
                if (!isset($groups[$group_key])) {
                    $groups[$group_key] = array(
                        'reason' => 'SKU duplicado',
                        'match' => $items[0]['sku'],
                        'items' => $items,
                    );
                }
            }
        }
    }

    $total_duplicates = 0;
    foreach ($groups as $g) {
        $total_duplicates += count($g['items']);
    }

    wp_send_json_success(array(
        'groups' => array_values($groups),
        'total' => $total_duplicates,
        'group_count' => count($groups),
    ));
}

// ──────────────────────────────────────
// AJAX: Trash a product
// ──────────────────────────────────────
add_action('wp_ajax_dss_dupfinder_trash', 'dss_dupfinder_ajax_trash');
function dss_dupfinder_ajax_trash()
{
    check_ajax_referer('dss_dupfinder_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Sin permisos.');

    $post_id = intval($_POST['post_id'] ?? 0);
    if ($post_id <= 0)
        wp_send_json_error('ID no válido.');

    if (get_post_type($post_id) !== 'product')
        wp_send_json_error('No es un producto.');

    $result = wp_trash_post($post_id);
    if (!$result)
        wp_send_json_error('No se pudo mover a la papelera.');

    wp_send_json_success(array('id' => $post_id));
}

// ──────────────────────────────────────
// AJAX: Bulk trash duplicates by language
// Keeps the oldest product per language in each group, trashes the rest.
// ──────────────────────────────────────
add_action('wp_ajax_dss_dupfinder_bulk_trash', 'dss_dupfinder_ajax_bulk_trash');
function dss_dupfinder_ajax_bulk_trash()
{
    check_ajax_referer('dss_dupfinder_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Sin permisos.');

    $groups = isset($_POST['groups']) ? $_POST['groups'] : array();
    if (empty($groups) || !is_array($groups))
        wp_send_json_error('No hay grupos para procesar.');

    $trashed_ids = array();

    foreach ($groups as $group) {
        if (empty($group['items']) || !is_array($group['items']))
            continue;

        // Group items by language
        $by_lang = array();
        foreach ($group['items'] as $item) {
            $lang = isset($item['lang']) ? sanitize_text_field($item['lang']) : '';
            $lang_key = $lang ?: '__no_lang__';
            $by_lang[$lang_key][] = intval($item['id']);
        }

        // For each language subgroup, keep the first (oldest ID = lowest) and trash the rest
        foreach ($by_lang as $lang_key => $ids) {
            if (count($ids) <= 1)
                continue;

            sort($ids); // lowest ID first = oldest
            $keep = array_shift($ids);

            foreach ($ids as $trash_id) {
                if (get_post_type($trash_id) === 'product' && get_post_status($trash_id) !== 'trash') {
                    $result = wp_trash_post($trash_id);
                    if ($result) {
                        $trashed_ids[] = $trash_id;
                    }
                }
            }
        }
    }

    // Store trashed IDs for rollback (per user, 24h expiry)
    if (!empty($trashed_ids)) {
        $transient_key = 'dss_dupfinder_rollback_' . get_current_user_id();
        set_transient($transient_key, $trashed_ids, 24 * HOUR_IN_SECONDS);
    }

    wp_send_json_success(array(
        'trashed' => $trashed_ids,
        'count' => count($trashed_ids),
    ));
}

// ──────────────────────────────────────
// AJAX: Rollback last bulk trash
// ──────────────────────────────────────
add_action('wp_ajax_dss_dupfinder_rollback', 'dss_dupfinder_ajax_rollback');
function dss_dupfinder_ajax_rollback()
{
    check_ajax_referer('dss_dupfinder_nonce', 'nonce');
    if (!current_user_can('manage_options'))
        wp_send_json_error('Sin permisos.');

    $transient_key = 'dss_dupfinder_rollback_' . get_current_user_id();
    $trashed_ids = get_transient($transient_key);

    if (empty($trashed_ids) || !is_array($trashed_ids))
        wp_send_json_error('No hay operación reciente para deshacer.');

    $restored = array();
    foreach ($trashed_ids as $post_id) {
        $post_id = intval($post_id);
        if ($post_id > 0 && get_post_status($post_id) === 'trash') {
            wp_untrash_post($post_id);
            $restored[] = $post_id;
        }
    }

    delete_transient($transient_key);

    wp_send_json_success(array(
        'restored' => $restored,
        'count' => count($restored),
    ));
}
