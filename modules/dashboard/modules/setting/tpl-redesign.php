<?php
/**
 * Nueva plantilla unificada para el rediseño de DSS Dashboard
 */
?>
<div class="wrap" id="dss-dashboard-root">
    <div class="dss-header-flex">
        <h1>
            <?php echo esc_html($this->page_title); ?>
        </h1>
        <div class="dss-header-actions">
            <!-- Posibles acciones globales -->
        </div>
    </div>

    <form method="post" id="dss-main-form" action="<?php echo esc_url($this->plugin_post); ?>">
        <?php settings_fields($this->setting_name . '_group'); ?>

        <div class="dss-dashboard-container">
            <!-- Sidebar de Navegación -->
            <aside class="dss-dashboard-sidebar">
                <nav class="dss-sidebar-nav">
                    <div class="dss-nav-item">
                        <a href="#section-colors" class="dss-nav-link active" data-section="colors">
                            <i class="dashicons dashicons-art"></i>
                            <span>
                                <?php esc_html_e('Colores y Estilo', 'ffl_admin_theme'); ?>
                            </span>
                        </a>
                    </div>
                    <div class="dss-nav-item">
                        <a href="#section-nav" class="dss-nav-link" data-section="nav">
                            <i class="dashicons dashicons-menu"></i>
                            <span>
                                <?php esc_html_e('Menú y Barra', 'ffl_admin_theme'); ?>
                            </span>
                        </a>
                    </div>
                    <div class="dss-nav-item">
                        <a href="#section-login" class="dss-nav-link" data-section="login">
                            <i class="dashicons dashicons-admin-users"></i>
                            <span>
                                <?php esc_html_e('Login Page', 'ffl_admin_theme'); ?>
                            </span>
                        </a>
                    </div>
                    <div class="dss-nav-item">
                        <a href="#section-footer" class="dss-nav-link" data-section="footer">
                            <i class="dashicons dashicons-editor-insertmore"></i>
                            <span>
                                <?php esc_html_e('Pie de Página', 'ffl_admin_theme'); ?>
                            </span>
                        </a>
                    </div>
                </nav>

                <div class="dss-sidebar-footer">
                    <button type="submit" class="dss-save-button">
                        <i class="dashicons dashicons-saved"></i>
                        <?php esc_html_e('Guardar Cambios', 'ffl_admin_theme'); ?>
                    </button>

                    <div style="margin-top: 15px; display: flex; gap: 5px;">
                        <button type="button" class="button button-secondary dss-btn-import" style="flex: 1;">
                            <?php esc_html_e('Importar', 'ffl_admin_theme'); ?>
                        </button>
                        <button type="button" class="button button-secondary dss-btn-export" style="flex: 1;">
                            <?php esc_html_e('Exportar', 'ffl_admin_theme'); ?>
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Contenido Principal -->
            <main class="dss-dashboard-content">

                <!-- Sección: Colores -->
                <section id="section-colors" class="dss-content-section active">
                    <div class="dss-card">
                        <div class="dss-card-header">
                            <h3>
                                <?php esc_html_e('Esquema de Colores', 'ffl_admin_theme'); ?>
                            </h3>
                        </div>
                        <div class="dss-card-body">
                            <?php do_action('admin_screen_col_1'); ?>
                        </div>
                    </div>
                </section>

                <!-- Sección: Navegación -->
                <section id="section-nav" class="dss-content-section">
                    <div class="dss-card">
                        <div class="dss-card-header">
                            <h3>
                                <?php esc_html_e('Configuración del Menú', 'ffl_admin_theme'); ?>
                            </h3>
                        </div>
                        <div class="dss-card-body">
                            <?php do_action('admin_screen_col_2'); ?>
                        </div>
                    </div>
                </section>

                <!-- Sección: Login -->
                <section id="section-login" class="dss-content-section">
                    <div class="dss-card">
                        <div class="dss-card-header"><h3><?php esc_html_e('Personalización de Login', 'ffl_admin_theme'); ?></h3></div>
                        <div class="dss-card-body">
                            <?php do_action('admin_screen_login_section'); ?>
                        </div>
                    </div>
                </section>

                <!-- Sección: Footer -->
                <section id="section-footer" class="dss-content-section">
                    <div class="dss-card">
                        <div class="dss-card-header">
                            <h3>
                                <?php esc_html_e('Pie de Página y CSS Extra', 'ffl_admin_theme'); ?>
                            </h3>
                        </div>
                        <div class="dss-card-body">
                            <!-- Inyectaremos el footer aquí via hook o captura de buffer -->
                            <?php do_action('admin_screen_footer_section'); ?>
                        </div>
                    </div>
                </section>

            </main>
        </div>
    </form>

    <!-- Modal de Importar (Oculto) -->
    <div id="dss-import-dialog" style="display:none;">
        <form method="post" enctype="multipart/form-data">
            <p>
                <?php esc_html_e('Selecciona el archivo JSON de configuración:', 'ffl_admin_theme'); ?>
            </p>
            <input type="file" name="import_file" required>
            <input type="hidden" name="setting_action" value="import_setting" />
            <?php wp_nonce_field('setting_import_nonce', 'setting_import_nonce'); ?>
            <?php submit_button(esc_html('Confirmar Importación', 'ffl_admin_theme')); ?>
        </form>
    </div>

    <!-- Script Inline para manejo de pestañas -->
    <script>
        jQuery(document).ready(function ($) {
            $('.dss-nav-link').on('click', function (e) {
                e.preventDefault();
                var target = $(this).data('section');

                // Actualizar Links
                $('.dss-nav-link').removeClass('active');
                $(this).addClass('active');

                // Actualizar Secciones
                $('.dss-content-section').removeClass('active');
                $('#section-' + target).addClass('active');

                // Persistir pestaña en URL (opcional)
                window.location.hash = target;
            });

            // Manejo de Importar
            $('.dss-btn-import').on('click', function () {
                // Un simple toggle por ahora o usar Thickbox de WP si está disponible
                alert('Funcionalidad de importación: Selecciona el archivo al final de la página (Legacy) o espera a la actualización del modal.');
            });
        });
    </script>
</div>

<style>
    /* Estilos breves de transición para la cabecera */
    .dss-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    #dss-dashboard-root .description {
        font-style: italic;
        color: #666;
    }
</style>