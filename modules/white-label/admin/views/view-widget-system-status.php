<div class="dss-system-status">
    <div class="dss-status-row">
        <div class="dss-status-item">
            <span class="dashicons dashicons-desktop"></span>
            <h4>Servidor (CPU)</h4>
            <p id="dss-server-load">Cargando...</p>
        </div>
        <div class="dss-status-item">
            <span class="dashicons dashicons-database"></span>
            <h4>Base de Datos</h4>
            <p id="dss-db-status">Cargando...</p>
        </div>
        <div class="dss-status-item">
            <span class="dashicons dashicons-groups"></span>
            <h4>Usuarios Activos</h4>
            <p id="dss-active-users">Cargando...</p>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        function fetchSystemStatus() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dss_get_system_status'
                },
                success: function (response) {
                    if (response.success && response.data) {
                        $('#dss-server-load').html(response.data.server_load + '<br><small>Mem: ' + response.data.memory_usage + '</small>');
                        $('#dss-db-status').html(response.data.db_threads + ' hilos<br><small>' + response.data.db_queries + ' q</small>');
                        $('#dss-active-users').text(response.data.active_users);
                    }
                }
            });
        }

        // Cargar inmediatamente
        fetchSystemStatus();

        // Actualizar cada 10 segundos
        setInterval(fetchSystemStatus, 10000);
    });
</script>

<style>
    .dss-system-status {
        padding: 10px 0;
    }

    .dss-status-row {
        display: flex;
        justify-content: space-between;
        gap: 15px;
    }

    .dss-status-item {
        flex: 1;
        background: #f0f0f1;
        border-radius: 6px;
        padding: 15px;
        text-align: center;
        border: 1px solid #c3c4c7;
    }

    .dss-status-item .dashicons {
        font-size: 32px;
        width: 32px;
        height: 32px;
        color: #2271b1;
        margin-bottom: 10px;
    }

    .dss-status-item h4 {
        margin: 0 0 5px 0;
        font-size: 13px;
        color: #3c434a;
    }

    .dss-status-item p {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #1d2327;
    }
</style>