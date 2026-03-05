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
            'name' => 'Content Sorter',
            'description' => 'Ordenamiento manual (Drag & Drop) para cualquier CPT y taxonomía.',
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
        ),
        'room-designer' => array(
            'name' => 'Addon: Room Designer',
            'description' => 'El cliente sube una foto de su habitación y la IA coloca los muebles de tu tienda.',
            'file' => 'public-chat/addons/room-designer/room-designer.php',
            'requires' => 'public-chat',
        ),
    );

    /**
     * Constructor.
     */
    public function __construct()
    {
        require_once DSS_SUITE_PLUGIN_DIR . 'includes/class-dss-notifications.php';
        DSS_Notifications::get_instance();

        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_panel_unlock'));
        add_action('admin_head', array($this, 'suppress_default_notices'));
        $this->load_modules();
    }

    /**
     * Suppress default WP admin notices on DSS Suite pages.
     */
    public function suppress_default_notices()
    {
        $screen = get_current_screen();
        if ($screen && (strpos($screen->id, 'dss-suite') !== false || strpos($screen->id, 'custom-theme-branding') !== false)) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
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
                $transient_key = 'dss_auth_' . get_current_user_id();
                set_transient($transient_key, true, 8 * HOUR_IN_SECONDS);
                wp_safe_redirect(add_query_arg('unlocked', '1', wp_get_referer()));
                exit;
            } else {
                add_settings_error('dss_suite_messages', 'dss_auth_error', 'Contraseña incorrecta. Acceso denegado.', 'error');
            }
        }
    }

    /**
     * Check if the current user is authorized to view protected pages.
     */
    private function is_panel_authorized()
    {
        $transient_key = 'dss_auth_' . get_current_user_id();
        return (bool) get_transient($transient_key);
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
                <p style="color: #64748b; margin-bottom: 30px;">Introduce la clave de licencia para gestionar ajustes
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

        // Submenu: IA y Licencia (only if Chatbox module is active)
        $active_modules = get_option('dss_suite_active_modules', array());
        if (!empty($active_modules['chatbox']) && $active_modules['chatbox'] === '1') {
            add_submenu_page(
                'dss-suite',
                'IA y Licencia',
                'IA y Licencia',
                'manage_options',
                'dss-suite-ai',
                array($this, 'render_ai_settings_page')
            );
        }
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
                // Check dependency
                if (!empty($module['requires'])) {
                    $req = $module['requires'];
                    if (!isset($active_modules[$req]) || $active_modules[$req] !== '1') {
                        continue;
                    }
                }
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
            // Eliminamos add_settings_error para que no aparezca el aviso nativo
            DSS_Notifications::get_instance()->add_persistent('Los módulos de la suite se han actualizado correctamente.', 'success', 'Ajustes Guardados');
        }

        if (isset($_GET['unlocked'])) {
            DSS_Notifications::get_instance()->add('¡Bienvenido! El panel de DSS Suite se ha desbloqueado correctamente.', 'success', 'Acceso Concedido');
        }

        // Ya no llamamos a settings_errors('dss_suite_messages') para evitar el aviso por defecto
        ?>
        <div class="wrap">
            <h1>DSS Suite - Panel de Control</h1>
            <p>Activa o desactiva los módulos que deseas utilizar en este sitio.</p>

            <form action="options.php" method="post">
                <?php settings_fields('dss_suite_options_group'); ?>

                <?php
                $core_modules = array();
                $addon_modules = array();
                foreach ($this->modules as $slug => $module) {
                    if (!empty($module['requires'])) {
                        $addon_modules[$slug] = $module;
                    } else {
                        $core_modules[$slug] = $module;
                    }
                }
                ?>

                <h2 style="margin-top:10px;">Módulos</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Estado</th>
                            <th>Módulo</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($core_modules as $slug => $module): ?>
                            <?php $is_checked = isset($active_modules[$slug]) && $active_modules[$slug] === '1'; ?>
                            <tr>
                                <td>
                                    <label class="dss-switch">
                                        <input type="checkbox" name="dss_suite_active_modules[<?php echo esc_attr($slug); ?>]"
                                            value="1" <?php checked($is_checked, true); ?>>
                                        <span class="dss-slider"></span>
                                    </label>
                                </td>
                                <td><strong><?php echo esc_html($module['name']); ?></strong></td>
                                <td><?php echo esc_html($module['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (!empty($addon_modules)): ?>
                <h2 style="margin-top:30px;">Addons</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Estado</th>
                            <th>Addon</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($addon_modules as $slug => $module): ?>
                            <?php
                            $is_checked = isset($active_modules[$slug]) && $active_modules[$slug] === '1';
                            $req = $module['requires'];
                            $dep_active = isset($active_modules[$req]) && $active_modules[$req] === '1';
                            $dep_missing = !$dep_active;
                            ?>
                            <tr class="dss-addon-row">
                                <td>
                                    <label class="dss-switch">
                                        <input type="checkbox" name="dss_suite_active_modules[<?php echo esc_attr($slug); ?>]"
                                            value="1" <?php checked($is_checked, true); ?> <?php echo $dep_missing ? 'disabled' : ''; ?>>
                                        <span class="dss-slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($module['name']); ?></strong>
                                    <?php if ($dep_missing): ?>
                                        <br><small style="color:#ef4444;">Requiere: <?php echo esc_html($this->modules[$req]['name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($module['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

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
                <?php settings_fields('dss_suite_ai_options_group'); ?>

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
            .dss-switch {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
            }

            .dss-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .dss-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #cbd5e1;
                transition: .4s;
                border-radius: 24px;
            }

            .dss-slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }

            input:checked+.dss-slider {
                background-color: #2271b1;
            }

            input:checked+.dss-slider:before {
                transform: translateX(20px);
            }

            .dss-addon-row td {
                background: #f8fafc;
            }
            .dss-addon-row td:first-child {
                border-left: 3px solid #2271b1;
            }
            .dss-addon-row td strong {
                padding-left: 20px;
                display: inline-block;
            }
        </style>
        <?php
    }
}
