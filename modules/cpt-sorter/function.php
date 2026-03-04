<?php
/*
 * Module: CPT Sorter
 * Description: Habilita ordenamiento manual (Drag & Drop) para 'cpt_portfolio' y lo aplica automáticamente en el frontend.
 */

if (!defined('ABSPATH'))
    exit;

// 1. Asegurar soporte para 'page-attributes' (menu_order)
add_action('init', 'cpt_portfolio_add_support', 99);
function cpt_portfolio_add_support()
{
    add_post_type_support('cpt_portfolio', 'page-attributes');
}

// 2. Modificar la consulta (Query) tanto en Admin como en Frontend
add_action('pre_get_posts', 'cpt_portfolio_modify_query_order');
function cpt_portfolio_modify_query_order($query)
{

    // Obtenemos el post type actual de la consulta
    // A veces es un array, a veces un string
    $current_post_type = $query->get('post_type');

    // Verificación robusta: ¿Estamos consultando 'cpt_portfolio'?
    $is_cpt_portfolio = ($current_post_type === 'cpt_portfolio') || (is_array($current_post_type) && in_array('cpt_portfolio', $current_post_type));

    if (!$is_cpt_portfolio) {
        return;
    }

    // CASO A: Estamos en el panel de Admin (lista de entradas)
    global $pagenow;
    if (is_admin() && $pagenow == 'edit.php' && $query->is_main_query()) {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }

    // CASO B: Estamos en el Frontend (web pública)
    // Aplicamos a archivos, búsquedas, etc., pero NO en el admin
    if (!is_admin() && !$query->is_main_query()) {
        // Opcional: Si quieres que afecte a widgets/bloques secundarios, quita el ! $query->is_main_query()
        // Por seguridad, solemos aplicarlo a la query principal primero.
    }

    if (!is_admin()) {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }
}

// 3. Encolar Scripts JS para Drag & Drop (Solo en admin edit.php de cpt_portfolio)
add_action('admin_enqueue_scripts', 'cpt_portfolio_enqueue_scripts');
function cpt_portfolio_enqueue_scripts($hook)
{
    global $post_type;

    if ('edit.php' != $hook || $post_type != 'cpt_portfolio') {
        return;
    }

    wp_enqueue_script('jquery-ui-sortable');

    // Script JS inline
    $script = "
    jQuery(document).ready(function($) {
        $('table.wp-list-table tbody').sortable({
            'items': 'tr',
            'axis': 'y',
            'helper': function(e, ui) {
                ui.children().each(function() { $(this).width($(this).width()); });
                return ui;
            },
            'update': function(e, ui) {
                $.post( ajaxurl, {
                    action: 'save_cpt_portfolio_order',
                    order: $(this).sortable('serialize')
                });
            }
        });
    });
    ";

    wp_add_inline_script('jquery-ui-sortable', $script);
    wp_add_inline_style('wp-admin', 'table.wp-list-table tbody tr { cursor: move; }');
}

// 4. AJAX para guardar el orden en base de datos
add_action('wp_ajax_save_cpt_portfolio_order', 'cpt_portfolio_save_order');
function cpt_portfolio_save_order()
{
    if (!current_user_can('edit_posts'))
        wp_die();

    parse_str($_POST['order'], $data);

    if (is_array($data['post'])) {
        foreach ($data['post'] as $position => $post_id) {
            wp_update_post(array(
                'ID' => (int) $post_id,
                'menu_order' => $position
            ));
        }
    }
    wp_die();
}
