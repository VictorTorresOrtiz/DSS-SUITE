<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="dss-heavy-queries">

    <?php if (!$savequeries_enabled): ?>
        <div class="notice notice-warning inline" style="margin: 0 0 15px 0; border-radius: 8px;">
            <p><strong>Monitorización desactivada:</strong> El seguimiento de consultas consume recursos. Actívelo sólo para
                diagnóstico temporal.</p>
        </div>
        <div class="dss-hq-actions">
            <button class="button button-primary dss-toggle-savequeries" data-enable="true"
                style="height:36px; border-radius:8px;">Activar Monitorización</button>
        </div>
    <?php else: ?>
        <?php if (!empty($heavy_queries)): ?>
            <p class="dss-help-text">Consultas > 0.005s detectadas en esta carga:</p>
            <ul class="dss-list">
                <?php foreach ($heavy_queries as $q): ?>
                    <li style="flex-direction: column; align-items: flex-start; gap: 8px; padding: 12px 0;">
                        <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                            <span class="dss-badge dss-badge-warning">
                                <?php echo number_format($q['time'], 4); ?> sec
                            </span>
                            <span style="font-size: 11px; color: var(--dss-text-muted); font-family: monospace;"
                                title="<?php echo esc_attr($q['trace']); ?>">
                                <?php
                                $trace_parts = explode(',', $q['trace']);
                                echo esc_html(trim(end($trace_parts)));
                                ?>
                            </span>
                        </div>
                        <div
                            style="width: 100%; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid var(--dss-border); overflow-x: auto;">
                            <code
                                style="font-size: 11px; color: #d63638; white-space: pre-wrap; word-break: break-all;"><?php echo esc_html($q['sql']); ?></code>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="notice notice-success inline" style="margin: 0 0 15px 0; border-radius: 8px;">
                <p><strong>Excelente:</strong> No se detectaron consultas lentas notables.</p>
            </div>
        <?php endif; ?>

        <div class="dss-hq-actions" style="margin-top: 20px; border-top: 1px solid var(--dss-border); padding-top: 15px;">
            <button class="button dss-toggle-savequeries" data-enable="false"
                style="color:#d63638; border-color:#d63638; height:32px; border-radius:6px;">Desactivar</button>
        </div>
    <?php endif; ?>

</div>

<script>
    jQuery(document).ready(function ($) {
        $('.dss-toggle-savequeries').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var enable = $btn.data('enable');
            var originalText = $btn.text();
            $btn.text('...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dss_toggle_savequeries',
                    enable: enable,
                    nonce: '<?php echo wp_create_nonce("dss_toggle_savequeries"); ?>'
                },
                success: function (response) {
                    if (response.success) { location.reload(); }
                    else { alert('Error: ' + response.data.message); $btn.text(originalText).prop('disabled', false); }
                },
                error: function () {
                    alert('Error en la solicitud.');
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>