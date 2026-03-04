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
            <div class="dss-overview-content">
                <p><strong><a href="<?php echo esc_url($top_product['url']); ?>">
                            <?php echo esc_html($top_product['name']); ?>
                        </a></strong></p>
                <p>Ventas Totales: <span class="dss-badge dss-badge-success">
                        <?php echo number_format_i18n($top_product['sales']); ?>
                    </span></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="dss-overview-section">
        <h4 class="dss-overview-title">
            <span class="dashicons dashicons-warning"></span> Stock Bajo
        </h4>
        <div class="dss-overview-content dss-low-stock-list">
            <?php if (!empty($low_stock_products)): ?>
                <ul>
                    <?php foreach ($low_stock_products as $item): ?>
                        <li>
                            <a href="<?php echo esc_url($item['url']); ?>">
                                <?php echo esc_html($item['name']); ?>
                            </a>
                            <span class="dss-badge dss-badge-warning">
                                <?php echo intval($item['stock']); ?> ud.
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="description">Todos los productos tienen buen nivel de stock.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="dss-overview-section">
        <h4 class="dss-overview-title">
            <span class="dashicons dashicons-cart"></span> Últimos Pedidos
        </h4>
        <div class="dss-overview-content dss-recent-orders">
            <?php if (!empty($recent_orders)): ?>
                <ul>
                    <?php foreach ($recent_orders as $order): ?>
                        <li>
                            <div class="dss-order-header">
                                <strong><a
                                        href="<?php echo esc_url($order['url']); ?>">#<?php echo esc_html($order['id']); ?></a>
                                    - <?php echo esc_html($order['customer']); ?></strong>
                                <span
                                    class="dss-badge dss-badge-status-<?php echo esc_attr(sanitize_title($order['status'])); ?>"><?php echo esc_html($order['status']); ?></span>
                            </div>
                            <p class="description">Total: <?php echo wp_kses_post($order['total']); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="description">No hay pedidos recientes.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
    .dss-store-overview {
        display: flex;
        flex-direction: column;
        gap: 15px;
        padding: 5px 0;
    }

    .dss-overview-section {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 12px 15px;
    }

    .dss-overview-title {
        margin: 0 0 10px 0;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
        color: #1d2327;
    }

    .dss-overview-title .dashicons {
        color: #2271b1;
    }

    .dss-overview-title .dashicons-warning {
        color: #d63638;
    }

    .dss-overview-content p {
        margin: 0 0 5px 0;
    }

    .dss-overview-content ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .dss-overview-content li {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .dss-overview-content li:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .dss-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .dss-badge-success {
        background: #edfaeb;
        color: #1e710b;
    }

    .dss-badge-warning {
        background: #fcf0e7;
        color: #d63638;
    }

    .dss-recent-orders li {
        display: block;
    }

    .dss-order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }

    .dss-order-header a {
        text-decoration: none;
        color: #2271b1;
    }

    .dss-badge-status-completado {
        background: #edfaeb;
        color: #1e710b;
    }

    .dss-badge-status-procesando {
        background: #f0f6fc;
        color: #0a4b78;
    }

    .dss-badge-status-pendiente {
        background: #fff8e5;
        color: #8a6d3b;
    }

    .dss-badge-status-cancelado {
        background: #fcf0e7;
        color: #d63638;
    }
</style>