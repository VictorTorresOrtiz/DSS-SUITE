<?php
if (!defined('ABSPATH')) {
    exit;
}

$screenshot = get_option('ctb_theme_screenshot');
?>
<div class="wrap">
    <h1>White label</h1>
    <p>Los cambios se verán reflejados en la sección de <strong>Apariencia > Temas</strong>.</p>
    <form method="post" action="options.php">
        <?php settings_fields('ctb_settings_group'); ?>
        <table class="form-table">
            <tr>
                <th>Nombre del Tema</th>
                <td><input type="text" name="ctb_theme_name"
                        value="<?php echo esc_attr(get_option('ctb_theme_name')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th>Autor</th>
                <td><input type="text" name="ctb_theme_author"
                        value="<?php echo esc_attr(get_option('ctb_theme_author')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th>URL del Autor</th>
                <td><input type="url" name="ctb_theme_uri"
                        value="<?php echo esc_attr(get_option('ctb_theme_uri')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th>Descripción</th>
                <td><textarea name="ctb_theme_desc" class="regular-text"
                        rows="4"><?php echo esc_textarea(get_option('ctb_theme_desc')); ?></textarea></td>
            </tr>
            <tr>
                <th>Miniatura del Tema (Beta)</th>
                <td>
                    <input type="text" name="ctb_theme_screenshot" id="ctb_theme_screenshot"
                        value="<?php echo esc_attr($screenshot); ?>" class="regular-text">
                    <button type="button" class="button" id="ctb_upload_btn">Elegir Imagen</button>
                    <p class="description">Tamaño recomendado: 1200x900px.</p>
                    <div style="margin-top:10px;">
                        <img id="ctb_preview" src="<?php echo esc_attr($screenshot); ?>"
                            style="max-width:300px; border:1px solid #ccc; <?php echo $screenshot ? '' : 'display:none;'; ?>">
                    </div>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>