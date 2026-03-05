<?php
if (!defined('ABSPATH')) {
    exit;
}

$screenshot = get_option('ctb_theme_screenshot');
$admin_bar_logo = get_option('ctb_admin_bar_logo');
$hide_updates = get_option('ctb_hide_updates');
?>
<div class="wrap dss-white-label-wrap">
    <div class="dss-header-section">
        <h1>Theme Settings</h1>
        <p>Personaliza la identidad de WordPress para tus clientes con un toque premium.</p>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields('ctb_settings_group'); ?>

        <!-- Theme Identity Card -->
        <div class="dss-branding-card">
            <h2><span class="dashicons dashicons-admin-appearance"></span> Identidad del Tema</h2>
            <p class="description">Estos datos sobreescriben la información original del tema activo en
                <strong>Apariencia > Temas</strong>.</p>

            <table class="form-table">
                <tr>
                    <th scope="row">Nombre del Tema</th>
                    <td>
                        <input type="text" name="ctb_theme_name"
                            value="<?php echo esc_attr(get_option('ctb_theme_name')); ?>"
                            placeholder="Ej: Mi Tema Personalizado">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Autor del Tema</th>
                    <td>
                        <input type="text" name="ctb_theme_author"
                            value="<?php echo esc_attr(get_option('ctb_theme_author')); ?>"
                            placeholder="Ej: Tu Agencia">
                    </td>
                </tr>
                <tr>
                    <th scope="row">URL del Autor</th>
                    <td>
                        <input type="url" name="ctb_theme_uri"
                            value="<?php echo esc_attr(get_option('ctb_theme_uri')); ?>"
                            placeholder="https://tuweb.com">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Descripción</th>
                    <td>
                        <textarea name="ctb_theme_desc" rows="4"
                            placeholder="Una breve descripción para tus clientes..."><?php echo esc_textarea(get_option('ctb_theme_desc')); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Miniatura (Screenshot)</th>
                    <td>
                        <div class="dss-media-control">
                            <div class="dss-media-input-group">
                                <input type="text" name="ctb_theme_screenshot" id="ctb_theme_screenshot"
                                    value="<?php echo esc_attr($screenshot); ?>">
                                <button type="button" class="button ctb_upload_btn" data-target="ctb_theme_screenshot"
                                    data-preview="ctb_preview">Elegir Imagen</button>
                            </div>
                            <p class="description">Tamaño recomendado: 1200x900px.</p>
                            <div class="dss-preview-box">
                                <img id="ctb_preview" src="<?php echo esc_attr($screenshot); ?>"
                                    style="<?php echo $screenshot ? '' : 'display:none;'; ?>">
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Admin Interface Branding -->
        <div class="dss-branding-card">
            <h2><span class="dashicons dashicons-admin-generic"></span> Interfaz del Administrador</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Logo de Barra Superior</th>
                    <td>
                        <div class="dss-media-control">
                            <div class="dss-media-input-group">
                                <input type="text" name="ctb_admin_bar_logo" id="ctb_admin_bar_logo"
                                    value="<?php echo esc_attr($admin_bar_logo); ?>">
                                <button type="button" class="button ctb_upload_btn" data-target="ctb_admin_bar_logo"
                                    data-preview="ctb_admin_bar_preview">Elegir Logo</button>
                            </div>
                            <p class="description">Reemplaza el logo de WordPress en la esquina superior izquierda.
                                Sugerencia: 20x20px.</p>
                            <div class="dss-preview-box">
                                <img id="ctb_admin_bar_preview" src="<?php echo esc_attr($admin_bar_logo); ?>"
                                    style="max-width: 50px; <?php echo $admin_bar_logo ? '' : 'display:none;'; ?>">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Texto del Pie de Página</th>
                    <td>
                        <input type="text" name="ctb_footer_text"
                            value="<?php echo esc_attr(get_option('ctb_footer_text')); ?>" class="regular-text"
                            placeholder="Gracias por confiar en Nosotros.">
                        <p class="description">Personaliza el mensaje "Gracias por crear con WordPress".</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Advanced Controls -->
        <div class="dss-branding-card">
            <h2><span class="dashicons dashicons-shield-alt"></span> Control de Actualizaciones</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Ocultar Actualizaciones</th>
                    <td>
                        <label class="dss-switch-control">
                            <input type="checkbox" name="ctb_hide_updates" value="1" <?php checked($hide_updates, '1'); ?>>
                            <span>Activar (Evita avisos de WordPress, Plugins y Temas)</span>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button('Guardar Cambios de Marca'); ?>
    </form>
</div>

<style>
    /* Inline styles for the specific switch on this page if not global */
    .dss-switch-control input[type="checkbox"] {
        margin-right: 10px;
        transform: scale(1.2);
    }
</style>