<?php
/**
 * Core class for DSS SUITE
 */

if (!defined('ABSPATH')) {
    exit;
}

class DSS_Suite_Core
{

    /**
     * List of available modules and their data.
     *
     * @var array
     */
    private $modules = array(
        'dashboard' => array(
            'name' => 'DSS Dashboard',
            'description' => 'Rediseño total del dashboard de WordPress.',
            'file' => 'dashboard/admin-musik.php',
        ),
        'seo-manager' => array(
            'name' => 'SEO Manager',
            'description' => 'Cambia etiquetas HTML y añade clases personalizadas.',
            'file' => 'seo-manager/seo-manager.php',
        ),
        'white-label' => array(
            'name' => 'Widget & Theme controller',
            'description' => 'Añade nuevos widgets, renombra items y personaliza la apariencia del tema.',
            'file' => 'white-label/white-label.php',
        ),
        'cpt-sorter' => array(
            'name' => 'CPT Sorter',
            'description' => 'Habilita ordenamiento manual (Drag & Drop) para los cpt de themerex u otros temas.',
            'file' => 'cpt-sorter/function.php',
        ),
        'chatbox' => array(
            'name' => 'Chatbox de Soporte (Premium)',
            'description' => 'Añade un chatbox moderno en el área de administración para consultas de clientes.',
            'file' => 'chatbox/chatbox.php',
        ),
        'public-chat' => array(
            'name' => 'Chat Público Beta (Premium)',
            'description' => 'Chatbot flotante para la parte pública con soporte de fotos y prompts personalizados.',
            'file' => 'public-chat/public-chat.php',
        )
    );

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_panel_unlock'));
        $this->load_modules();
    }

    /**
     * Handle the Master Key unlock process.
     */
    public function handle_panel_unlock()
    {
        if (isset($_POST['dss_master_key_submit'])) {
            check_admin_referer('dss_unlock_action', 'dss_unlock_nonce');

            if (!current_user_can('manage_options')) {
                return;
            }

            $input_key = isset($_POST['dss_master_key']) ? sanitize_text_field($_POST['dss_master_key']) : '';

            // Requerimos que la clave esté definida en wp-config.php por seguridad
            if (!defined('DSS_MASTER_KEY')) {
                add_settings_error('dss_suite_messages', 'dss_auth_error', 'Error de Seguridad: La Master Key no está configurada en el servidor (wp-config.php). Contacte con el soporte técnico.', 'error');
                return;
            }

            if ($input_key === DSS_MASTER_KEY) {
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['dss_suite_authorized'] = true;
                wp_safe_redirect(add_query_arg('unlocked', '1', wp_get_referer()));
                exit;
            } else {
                add_settings_error('dss_suite_messages', 'dss_auth_error', 'Contraseña incorrecta. Acceso denegado.', 'error');
            }
        }
    }

    /**
     * Check if the current session is authorized to view protected pages.
     */
    private function is_panel_authorized()
    {
        if (!session_id()) {
            session_start();
        }
        return isset($_SESSION['dss_suite_authorized']) && $_SESSION['dss_suite_authorized'] === true;
    }

    /**
     * Render the Password Protection Screen.
     */
    private function render_lock_screen()
    {
        ?>
        <div class="wrap">
            <div
                style="max-width: 400px; margin: 100px auto; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; border: 1px solid #e2e8f0;">
                <span class="dashicons dashicons-lock"
                    style="font-size: 48px; width: 48px; height: 48px; color: #2271b1; margin-bottom: 20px;"></span>
                <h1 style="font-size: 24px; margin-bottom: 10px;">Acceso Protegido</h1>
                <p style="color: #64748b; margin-bottom: 30px;">Introduce la Master Key de DSS Suite para gestionar ajustes
                    críticos.</p>

                <?php settings_errors('dss_suite_messages'); ?>

                <form method="post" action="">
                    <?php wp_nonce_field('dss_unlock_action', 'dss_unlock_nonce'); ?>
                    <input type="password" name="dss_master_key" placeholder="Contraseña Privada" required
                        style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 20px; font-size: 16px; text-align: center;">
                    <button type="submit" name="dss_master_key_submit" class="button button-primary button-large"
                        style="width: 100%; height: 45px; font-weight: 600;">
                        Desbloquear Panel
                    </button>
                </form>
                <p style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
                    Esta sección está restringida incluso para administradores.
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Register the main DSS Suite menu in the WordPress admin.
     */
    public function register_admin_menu()
    {
        // Main Parent Menu
        add_menu_page(
            'DSS Suite',
            'DSS Suite',
            'manage_options',
            'dss-suite',
            array($this, 'render_settings_page'),
            'dashicons-admin-generic',
            65
        );

        // Submenu: Panel de Control (Module Activation)
        add_submenu_page(
            'dss-suite',
            'Panel de Control',
            'Panel de Control',
            'manage_options',
            'dss-suite',
            array($this, 'render_settings_page')
        );

        // Submenu: IA y Licencia (Global Settings)
        add_submenu_page(
            'dss-suite',
            'IA y Licencia',
            'IA y Licencia',
            'manage_options',
            'dss-suite-ai',
            array($this, 'render_ai_settings_page')
        );
    }

    /**
     * Register the plugin settings.
     */
    public function register_settings()
    {
        // Group for Module Activation
        register_setting('dss_suite_options_group', 'dss_suite_active_modules');

        // Group for AI & License
        register_setting('dss_suite_ai_options_group', 'dss_suite_gemini_api_key');
        register_setting('dss_suite_ai_options_group', 'dss_suite_invoice_number');
    }

    /**
     * Load only the modules that are enabled in the settings.
     */
    private function load_modules()
    {
        $active_modules = get_option('dss_suite_active_modules', array());

        foreach ($this->modules as $slug => $module) {
            // Check if module is enabled (or if nothing is configured yet, we can default to disabled or enabled depending on preference)
            // Let's default to enabled if the array is empty meaning first run? Actually, safer to default to disabled to prevent sudden changes, or strictly check array.
            if (isset($active_modules[$slug]) && $active_modules[$slug] === '1') {
                $module_file = DSS_SUITE_PLUGIN_DIR . 'modules/' . $module['file'];
                if (file_exists($module_file)) {
                    require_once $module_file;
                }
            }
        }
    }

    /**
     * Render the Settings Page in the Admin area.
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!$this->is_panel_authorized()) {
            $this->render_lock_screen();
            return;
        }

        $active_modules = get_option('dss_suite_active_modules', array());

        if (isset($_GET['settings-updated'])) {
            add_settings_error('dss_suite_messages', 'dss_suite_message', __('Settings Saved', 'dss-suite'), 'updated');
        }

        if (isset($_GET['unlocked'])) {
            add_settings_error('dss_suite_messages', 'dss_auth_success', 'Panel desbloqueado correctamente.', 'updated');
        }

        settings_errors('dss_suite_messages');
        ?>
        <div class="wrap">
            <h1>DSS Suite - Panel de Control</h1>
            <p>Activa o desactiva los módulos que deseas utilizar en este sitio.</p>

            <form action="options.php" method="post">
                <?php settings_fields('dss_suite_options_group'); ?>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Estado</th>
                            <th>Módulo</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->modules as $slug => $module): ?>
                            <?php $is_checked = isset($active_modules[$slug]) && $active_modules[$slug] === '1'; ?>
                            <tr>
                                <td>
                                    <label class="switch dss-switch">
                                        <input type="checkbox" name="dss_suite_active_modules[<?php echo esc_attr($slug); ?>]"
                                            value="1" <?php checked($is_checked, true); ?>>
                                    </label>
                                </td>
                                <td><strong><?php echo esc_html($module['name']); ?></strong></td>
                                <td><?php echo esc_html($module['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button('Guardar Cambios'); ?>
            </form>
        </div>
        <?php
        $this->render_admin_styles();
    }

    /**
     * Render the AI & License Settings Page.
     */
    public function render_ai_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!$this->is_panel_authorized()) {
            $this->render_lock_screen();
            return;
        }
        ?>
        <div class="wrap">
            <h1>DSS Suite - IA y Licencia</h1>
            <p>Configuración global de inteligencia artificial y credenciales premium.</p>

            <form action="options.php" method="post">
                <?php settings_fields('dss_suite_options_group'); ?>

                <div
                    style="margin-top: 20px; padding: 25px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <h2 style="margin-top:0;">Configuración de Gemini</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="dss_suite_gemini_api_key">API Key</label></th>
                            <td>
                                <input type="password" name="dss_suite_gemini_api_key" id="dss_suite_gemini_api_key"
                                    value="<?php echo esc_attr(get_option('dss_suite_gemini_api_key')); ?>"
                                    class="regular-text">
                                <p class="description">Obtén tu clave en <a href="https://aistudio.google.com/"
                                        target="_blank">Google AI Studio</a>.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dss_suite_invoice_number">Número de Factura</label></th>
                            <td>
                                <input type="text" name="dss_suite_invoice_number" id="dss_suite_invoice_number"
                                    value="<?php echo esc_attr(get_option('dss_suite_invoice_number')); ?>"
                                    class="regular-text">
                                <p class="description">Asocia tu licencia para soporte técnico avanzado.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Guardar Ajustes'); ?>
            </form>
        </div>
        <?php
        $this->render_admin_styles();
    }

    /**
     * Helper to render common admin styles.
     */
    private function render_admin_styles()
    {
        ?>
        <style>
            .dss-switch input[type=checkbox] {
                width: 40px;
                height: 20px;
                appearance: none;
                background: #ccc;
                outline: none;
                border-radius: 20px;
                box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
                transition: 0.5s;
                position: relative;
                cursor: pointer;
            }

            .dss-switch input:checked[type=checkbox] {
                background: #2271b1;
            }

            .dss-switch input[type=checkbox]:before {
                content: '';
                position: absolute;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                top: 0;
                left: 0;
                background: #fff;
                transform: scale(0.9);
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                transition: 0.5s;
            }

            .dss-switch input:checked[type=checkbox]:before {
                left: 20px;
            }
        </style>
        <?php
    }
}
