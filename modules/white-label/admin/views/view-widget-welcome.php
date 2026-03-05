<?php
if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
$hour = (int) current_time('G');

if ($hour < 12) {
    $greeting = 'Buenos días';
} elseif ($hour < 20) {
    $greeting = 'Buenas tardes';
} else {
    $greeting = 'Buenas noches';
}
?>
<div class="dss-welcome-container">
    <div class="dss-welcome-header">
        <h3><?php echo esc_html($greeting . ', ' . $user->display_name); ?></h3>
        <p class="dss-welcome-subtitle">Enlaces rápidos de gestión para
            <strong><?php echo esc_html(get_bloginfo('name')); ?></strong></p>
    </div>

    <div class="dss-quick-links">
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" class="dss-quick-link">
            <span class="dashicons dashicons-products"></span>
            <span>Productos</span>
        </a>
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>" class="dss-quick-link">
            <span class="dashicons dashicons-cart"></span>
            <span>Pedidos</span>
        </a>
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>" class="dss-quick-link">
            <span class="dashicons dashicons-admin-page"></span>
            <span>Páginas</span>
        </a>
        <a href="<?php echo esc_url(admin_url('upload.php')); ?>" class="dss-quick-link">
            <span class="dashicons dashicons-admin-media"></span>
            <span>Medios</span>
        </a>
    </div>
</div>