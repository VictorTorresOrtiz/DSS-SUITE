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
<div class="dss-welcome">
    <h3>
        <?php echo esc_html($greeting . ', ' . $user->display_name); ?>
    </h3>
    <p class="dss-welcome-subtitle">Panel de administración de <?php echo esc_html(get_bloginfo('name')); ?></p>

    <div class="dss-quick-links">
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" class="dss-quick-link">
            <span class="dashicons dashicons-products"></span>
            Productos
        </a>
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_order')); ?>" class="dss-quick-link">
            <span class="dashicons dashicons-cart"></span>
            Pedidos
        </a>
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>" class="dss-quick-link">
            <span class="dashicons dashicons-admin-page"></span>
            Páginas
        </a>
        <a href="<?php echo esc_url(admin_url('upload.php')); ?>" class="dss-quick-link">
            <span class="dashicons dashicons-admin-media"></span>
            Medios
        </a>
    </div>
</div>