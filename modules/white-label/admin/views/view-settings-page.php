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
        <h1><span class="dashicons dashicons-admin-appearance"></span> Personalización del Tema</h1>
        <p>Define la identidad visual de WordPress para tus clientes con un toque premium y profesional.</p>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields('ctb_settings_group'); ?>

        <div class="dss-card">
            <div class="dss-card-header">
                <h2><span class="dashicons dashicons-art"></span> Identidad del Tema</h2>
            </div>
            <div class="dss-card-body">
                <p class="dss-help-text" style="margin-bottom: 25px;">Esta información sobreescribirá los datos del tema
                    actual en <strong>Apariencia > Temas</strong>.</p>

                <div class="dss-form-group">
                    <label>Nombre del Tema</label>
                    <input type="text" name="ctb_theme_name"
                        value="<?php echo esc_attr(get_option('ctb_theme_name')); ?>"
                        placeholder="Ej: DSS Premium Theme">
                </div>

                <div class="dss-form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label>Autor del Tema</label>
                        <input type="text" name="ctb_theme_author"
                            value="<?php echo esc_attr(get_option('ctb_theme_author')); ?>"
                            placeholder="Ej: Tu Agencia">
                    </div>
                    <div>
                        <label>URL del Autor</label>
                        <input type="url" name="ctb_theme_uri"
                            value="<?php echo esc_attr(get_option('ctb_theme_uri')); ?>"
                            placeholder="https://tuweb.com">
                    </div>
                </div>

                <div class="dss-form-group">
                    <label>Descripción del Tema</label>
                    <textarea name="ctb_theme_desc" rows="4"
                        placeholder="Una breve descripción para tus clientes..."><?php echo esc_textarea(get_option('ctb_theme_desc')); ?></textarea>
                </div>

                <div class="dss-form-group">
                    <label>Miniatura (Screenshot)</label>
                    <div class="dss-media-config">
                        <div class="dss-image-preview-wrapper">
                            <img id="ctb_preview" src="<?php echo esc_attr($screenshot); ?>"
                                style="<?php echo $screenshot ? '' : 'display:none;'; ?>">
                            <?php if (!$screenshot): ?>
                                <span class="dashicons dashicons-format-image"
                                    style="font-size: 32px; width: 32px; height: 32px; color: #cbd5e1;"></span>
                            <?php endif; ?>
                        </div>
                        <div class="dss-media-controls">
                            <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                                <input type="text" name="ctb_theme_screenshot" id="ctb_theme_screenshot"
                                    value="<?php echo esc_attr($screenshot); ?>" style="flex-grow: 1;">
                                <button type="button" class="button ctb_upload_btn" data-target="ctb_theme_screenshot"
                                    data-preview="ctb_preview">Elegir Imagen</button>
                            </div>
                            <p class="dss-help-text">Tamaño recomendado: 1200x900px. Se mostrará en la lista de temas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dss-card">
            <div class="dss-card-header">
                <h2><span class="dashicons dashicons-admin-generic"></span> Branding del Administrador</h2>
            </div>
            <div class="dss-card-body">
                <div class="dss-form-group">
                    <label>Logo de la Barra Superior</label>
                    <div class="dss-media-config">
                        <div class="dss-image-preview-wrapper" style="width: 60px; height: 60px;">
                            <img id="ctb_admin_bar_preview" src="<?php echo esc_attr($admin_bar_logo); ?>"
                                style="<?php echo $admin_bar_logo ? '' : 'display:none;'; ?>">
                            <?php if (!$admin_bar_logo): ?>
                                <span class="dashicons dashicons-admin-site-alt3"
                                    style="font-size: 24px; width: 24px; height: 24px; color: #cbd5e1;"></span>
                            <?php endif; ?>
                        </div>
                        <div class="dss-media-controls">
                            <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                                <input type="text" name="ctb_admin_bar_logo" id="ctb_admin_bar_logo"
                                    value="<?php echo esc_attr($admin_bar_logo); ?>" style="flex-grow: 1;">
                                <button type="button" class="button ctb_upload_btn" data-target="ctb_admin_bar_logo"
                                    data-preview="ctb_admin_bar_preview">Elegir Logo</button>
                            </div>
                            <p class="dss-help-text">Reemplaza el icono de WordPress en la parte superior izquierda.
                                Sugerencia: 20x20px (SVG o PNG transparente).</p>
                        </div>
                    </div>
                </div>

                <div class="dss-form-group">
                    <label>Texto del Pie de Página (Footer)</label>
                    <input type="text" name="ctb_footer_text"
                        value="<?php echo esc_attr(get_option('ctb_footer_text')); ?>" class="regular-text"
                        placeholder="Ej: Desarrollado por Tu Agencia">
                    <p class="dss-help-text">Personaliza el mensaje que aparece en la parte inferior de todas las
                        páginas del panel.</p>
                </div>
            </div>
        </div>

        <div class="dss-card">
            <div class="dss-card-header">
                <h2><span class="dashicons dashicons-shield-alt"></span> Control Avanzado</h2>
            </div>
            <div class="dss-card-body">
                <div class="dss-form-group" style="margin-bottom: 30px;">
                    <label class="dss-switch-container">
                        <span class="dss-switch">
                            <input type="checkbox" name="ctb_hide_updates" value="1" <?php checked(get_option('ctb_hide_updates'), '1'); ?>>
                            <span class="dss-slider"></span>
                        </span>
                        <span class="dss-switch-label">Ocultar todas las notificaciones de actualización (Core, Plugins
                            y Temas)</span>
                    </label>
                    <p class="dss-help-text" style="margin-left: 56px;">Activa esta opción si quieres evitar que tus
                        clientes vean avisos de actualizaciones pendientes.</p>
                </div>

                <div class="dss-form-group">
                    <label class="dss-switch-container">
                        <span class="dss-switch">
                            <input type="checkbox" name="ctb_hide_notices" value="1" <?php checked(get_option('ctb_hide_notices'), '1'); ?>>
                            <span class="dss-slider"></span>
                        </span>
                        <span class="dss-switch-label">Ocultar todas las notificaciones del panel (Admin Notices)</span>
                    </label>
                    <p class="dss-help-text" style="margin-left: 56px;">Activa esta opción para eliminar avisos, alertas
                        y banners de plugins o del propio WordPress en el escritorio.</p>
                </div>

                <div class="dss-form-group">
                    <label class="dss-switch-container">
                        <span class="dss-switch">
                            <input type="checkbox" name="ctb_allow_svg" value="1" <?php checked(get_option('ctb_allow_svg'), '1'); ?>>
                            <span class="dss-slider"></span>
                        </span>
                        <span class="dss-switch-label">Permitir subida de archivos SVG</span>
                    </label>
                    <p class="dss-help-text" style="margin-left: 56px;">Habilita la subida de imágenes en formato SVG y SVGZ a la biblioteca de medios.</p>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <?php submit_button('Guardar Configuración de Marca', 'primary', 'submit', true, array('style' => 'height: 48px; padding: 0 40px; font-weight: 600; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);')); ?>
        </div>
    </form>
</div>