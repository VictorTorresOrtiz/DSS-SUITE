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
                        $('#dss-server-load').html(response.data.server_load + '<br><small style="font-weight:400; font-size:11px; color:#64748b;">Mem: ' + response.data.memory_usage + '</small>');
                        $('#dss-db-status').html(response.data.db_threads + ' hilos<br><small style="font-weight:400; font-size:11px; color:#64748b;">' + response.data.db_queries + ' queries</small>');
                        $('#dss-active-users').text(response.data.active_users);
                    }
                }
            });
        }
        fetchSystemStatus();
        setInterval(fetchSystemStatus, 10000);
    });
</script>