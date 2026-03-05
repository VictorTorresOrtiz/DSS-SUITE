<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="dss-store-overview">

    <?php if ($top_product): ?>
        <div class="dss-overview-section">
            <h4 class="dss-overview-title">
                <span class="dashicons dashicons-star-filled"></span> Producto Más Vendido
            </h4>
            <div class="dss-overview-content" style="display: flex; justify-content: space-between; align-items: center;">
                <p style="margin:0;"><strong><a href="<?php echo esc_url($top_product['url']); ?>"
                            style="text-decoration:none; color:var(--dss-primary);">
                            <?php echo esc_html($top_product['name']); ?>
                        </a></strong></p>
                <span class="dss-badge dss-badge-success">
                    <?php echo number_format_i18n($top_product['sales']); ?> ventas
                </span>
            </div>
        </div>
    <?php endif; ?>

    <div class="dss-overview-section">
        <h4 class="dss-overview-title">
            <span class="dashicons dashicons-warning"></span> Alerta de Stock Bajo
        </h4>
        <div class="dss-overview-content">
            <?php if (!empty($low_stock_products)): ?>
                <ul class="dss-list">
                    <?php foreach ($low_stock_products as $item): ?>
                        <li>
                            <a href="<?php echo esc_url($item['url']); ?>"
                                style="text-decoration:none; color:var(--dss-text-main); font-weight:600;">
                                <?php echo esc_html($item['name']); ?>
                            </a>
                            <span class="dss-badge dss-badge-warning">
                                <?php echo intval($item['stock']); ?> ud.
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="description">Todos los productos tienen niveles de stock saludables.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="dss-overview-section">
        <h4 class="dss-overview-title">
            <span class="dashicons dashicons-cart"></span> Pedidos Recientes
        </h4>
        <div class="dss-overview-content">
            <?php if (!empty($recent_orders)): ?>
                <ul class="dss-list">
                    <?php foreach ($recent_orders as $order): ?>
                        <li>
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <strong><a href="<?php echo esc_url($order['url']); ?>"
                                        style="text-decoration:none; color:var(--dss-text-main);">#<?php echo esc_html($order['id']); ?>
                                        - <?php echo esc_html($order['customer']); ?></a></strong>
                                <span style="font-size:12px; color:var(--dss-text-muted);">Total:
                                    <?php echo wp_kses_post($order['total']); ?></span>
                            </div>
                            <span class="dss-badge dss-badge-info"><?php echo esc_html($order['status']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="description">No hay actividad de pedidos reciente.</p>
            <?php endif; ?>
        </div>
    </div>

</div>