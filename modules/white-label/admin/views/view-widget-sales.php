<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="dss-sales">
    <div class="dss-sales-grid">
        <div class="dss-sales-card">
            <span class="dss-sales-label">Hoy</span>
            <span class="dss-sales-amount">
                <?php echo wc_price($today_sales['total']); ?>
            </span>
            <span class="dss-sales-orders">
                <?php echo (int) $today_sales['count']; ?> pedidos
            </span>
        </div>
        <div class="dss-sales-card">
            <span class="dss-sales-label">Esta semana</span>
            <span class="dss-sales-amount">
                <?php echo wc_price($week_sales['total']); ?>
            </span>
            <span class="dss-sales-orders">
                <?php echo (int) $week_sales['count']; ?> pedidos
            </span>
        </div>
        <div class="dss-sales-card">
            <span class="dss-sales-label">Este mes</span>
            <span class="dss-sales-amount">
                <?php echo wc_price($month_sales['total']); ?>
            </span>
            <span class="dss-sales-orders">
                <?php echo (int) $month_sales['count']; ?> pedidos
            </span>
        </div>
        <div class="dss-sales-card dss-sales-card--pending">
            <span class="dss-sales-label">Pendientes</span>
            <span class="dss-sales-amount dss-sales-amount--count">
                <?php echo (int) $pending_orders; ?>
            </span>
            <span class="dss-sales-orders">en proceso</span>
        </div>
    </div>
    <div class="dss-sales-footer">
        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-reports')); ?>">Ver informes completos &rarr;</a>
    </div>
</div>