<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="dss-heavy-queries">

    <?php if (!$savequeries_enabled): ?>
        <div class="notice notice-warning inline" style="margin: 0 0 10px 0;">
            <p><strong>Monitorización desactivada:</strong> El seguimiento de consultas consume recursos. Actívelo sólo para
                diagnóstico temporal.</p>
        </div>
        <div class="dss-hq-actions">
            <button class="button button-primary dss-toggle-savequeries" data-enable="true">Activar Monitorización</button>
        </div>
    <?php else: ?>
        <?php if (!empty($heavy_queries)): ?>
            <p>Mostrando las consultas que exceden <strong>0.005</strong> segundos durante la carga de esta página:</p>
            <ul class="dss-heavy-queries-list">
                <?php foreach ($heavy_queries as $q): ?>
                    <li>
                        <div class="dss-query-header">
                            <span class="dss-badge dss-badge-danger">
                                <?php echo number_format($q['time'], 4); ?> s
                            </span>
                            <span class="dss-query-caller" title="<?php echo esc_attr($q['trace']); ?>">
                                <?php
                                // Extract the last function making the call for brevity
                                $trace_parts = explode(',', $q['trace']);
                                echo esc_html(trim(end($trace_parts)));
                                ?>
                            </span>
                        </div>
                        <div class="dss-query-sql">
                            <code><?php echo esc_html($q['sql']); ?></code>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="notice notice-success inline" style="margin: 0 0 10px 0;">
                <p><strong>Excelente:</strong> La monitorización está activa y no se detectaron consultas destacablemente lentas
                    durante la carga de esta página.</p>
            </div>
        <?php endif; ?>

        <hr style="margin-top: 15px; border: 0; border-top: 1px solid #c3c4c7;">
        <div class="dss-hq-actions">
            <button class="button button-secondary dss-toggle-savequeries dss-btn-danger" data-enable="false">Desactivar
                Monitorización</button>
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

            $btn.text('Procesando...').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dss_toggle_savequeries',
                    enable: enable,
                    nonce: '<?php echo wp_create_nonce("dss_toggle_savequeries"); ?>'
                },
                success: function (response) {
                    if (response.success) {
                        location.reload(); // Reload to show the new state
                    } else {
                        alert('Error: ' + response.data.message);
                        $btn.text(originalText).prop('disabled', false);
                    }
                },
                error: function () {
                    alert('Ocurrió un error en la solicitud.');
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>

<style>
    .dss-heavy-queries {
        padding: 5px 0;
    }

    .dss-heavy-queries-list {
        margin: 10px 0 0 0;
        padding: 0;
        list-style: none;
    }

    .dss-heavy-queries-list li {
        background: #fff;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .dss-query-header {
        background: #f0f0f1;
        padding: 8px 12px;
        border-bottom: 1px solid #c3c4c7;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dss-query-caller {
        font-size: 12px;
        color: #50575e;
        font-family: monospace;
        max-width: 70%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: help;
    }

    .dss-query-sql {
        padding: 12px;
        background: #f6f7f7;
        overflow-x: auto;
    }

    .dss-query-sql code {
        background: transparent;
        padding: 0;
        font-size: 12px;
        white-space: pre-wrap;
        word-break: break-all;
        color: #d63638;
    }

    .dss-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .dss-badge-danger {
        background: #fcf0e7;
        color: #d63638;
    }

    .dss-hq-actions {
        margin-top: 10px;
        text-align: right;
    }

    .dss-toggle-savequeries.dss-btn-danger {
        color: #d63638;
        border-color: #d63638;
    }

    .dss-toggle-savequeries.dss-btn-danger:hover {
        background: #d63638;
        color: #fff;
    }
</style>