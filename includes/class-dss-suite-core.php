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
            'icon' => 'dashicons-layout',
        ),
        'seo-manager' => array(
            'name' => 'SEO Manager',
            'description' => 'Cambia etiquetas HTML y añade clases personalizadas.',
            'file' => 'seo-manager/seo-manager.php',
            'icon' => 'dashicons-search',
        ),
        'white-label' => array(
            'name' => 'Widget & Theme controller',
            'description' => 'Añade nuevos widgets, renombra items y personaliza la apariencia del tema.',
            'file' => 'white-label/white-label.php',
            'icon' => 'dashicons-art',
        ),
        'cpt-sorter' => array(
            'name' => 'Content Sorter',
            'description' => 'Ordenamiento manual (Drag & Drop) para cualquier CPT y taxonomía.',
            'file' => 'cpt-sorter/function.php',
            'icon' => 'dashicons-sort',
        ),
        'chatbox' => array(
            'name' => 'Chatbox de Soporte',
            'description' => 'Añade un chatbox moderno en el área de administración para consultas de clientes.',
            'file' => 'chatbox/chatbox.php',
            'icon' => 'dashicons-format-chat',
            'premium' => true,
        ),
        'public-chat' => array(
            'name' => 'Chat Público Beta',
            'description' => 'Chatbot flotante para la parte pública con soporte de fotos y prompts personalizados.',
            'file' => 'public-chat/public-chat.php',
            'icon' => 'dashicons-admin-comments',
            'premium' => true,
        ),
        'room-designer' => array(
            'name' => 'Room Designer',
            'description' => 'El cliente sube una foto de su habitación y la IA coloca los muebles de tu tienda.',
            'file' => 'public-chat/addons/room-designer/room-designer.php',
            'requires' => 'public-chat',
            'icon' => 'dashicons-admin-home',
        ),
        'course-advisor' => array(
            'name' => 'Course Advisor',
            'description' => 'Asesor IA para webs de formaciones. Recomienda cursos segun objetivos y nivel del visitante.',
            'file' => 'public-chat/addons/course-advisor/course-advisor.php',
            'requires' => 'public-chat',
            'icon' => 'dashicons-welcome-learn-more',
        ),
        'duplicate-finder' => array(
            'name' => 'Duplicate Finder',
            'description' => 'Encuentra y gestiona productos duplicados en WooCommerce.',
            'file' => 'duplicate-finder/function.php',
            'icon' => 'dashicons-controls-repeat',
        ),
        'dss-connector' => array(
            'name' => 'DSS Connector',
            'description' => 'API remota para DSS Gestion via admin-ajax.php. Permite gestionar el sitio sin SSH.',
            'file' => 'dss-connector/dss-connector.php',
            'icon' => 'dashicons-cloud',
            'beta' => true,
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
            DSS_Notifications::get_instance()->add_persistent('Los módulos de la suite se han actualizado correctamente.', 'success', 'Ajustes Guardados');
        }

        if (isset($_GET['unlocked'])) {
            DSS_Notifications::get_instance()->add('El panel de DSS Suite se ha desbloqueado correctamente.', 'success', 'Acceso Concedido');
        }

        $core_modules = array();
        $addon_modules = array();
        $active_count = 0;
        foreach ($this->modules as $slug => $module) {
            $is_active = isset($active_modules[$slug]) && $active_modules[$slug] === '1';
            if ($is_active) $active_count++;
            if (!empty($module['requires'])) {
                $addon_modules[$slug] = $module;
            } else {
                $core_modules[$slug] = $module;
            }
        }

        ?>
        <div class="wrap dss-panel">
            <div class="dss-panel-header">
                <div class="dss-panel-header-left">
                    <h1><span class="dashicons dashicons-admin-generic" style="font-size:28px;width:28px;height:28px;color:#2271b1;"></span> DSS Suite</h1>
                    <span class="dss-badge">v<?php echo esc_html(DSS_SUITE_VERSION); ?></span>
                </div>
                <div class="dss-panel-stats">
                    <div class="dss-stat">
                        <span class="dss-stat-number"><?php echo $active_count; ?></span>
                        <span class="dss-stat-label">Activos</span>
                    </div>
                    <div class="dss-stat">
                        <span class="dss-stat-number"><?php echo count($this->modules); ?></span>
                        <span class="dss-stat-label">Total</span>
                    </div>
                </div>
            </div>

            <form action="options.php" method="post">
                <?php settings_fields('dss_suite_options_group'); ?>

                <div class="dss-section">
                    <div class="dss-section-header">
                        <h2><span class="dashicons dashicons-screenoptions"></span> Módulos</h2>
                        <p><?php echo count($core_modules); ?> módulos disponibles</p>
                    </div>
                    <div class="dss-cards-grid">
                        <?php foreach ($core_modules as $slug => $module):
                            $is_checked = isset($active_modules[$slug]) && $active_modules[$slug] === '1';
                            $icon = $module['icon'] ?? 'dashicons-admin-plugins';
                            $is_premium = !empty($module['premium']);
                            $is_beta = !empty($module['beta']);
                        ?>
                        <div class="dss-module-card <?php echo $is_checked ? 'active' : ''; ?>">
                            <div class="dss-module-card-header">
                                <span class="dashicons <?php echo esc_attr($icon); ?> dss-module-icon"></span>
                                <label class="dss-switch">
                                    <input type="checkbox" name="dss_suite_active_modules[<?php echo esc_attr($slug); ?>]"
                                        value="1" <?php checked($is_checked, true); ?>>
                                    <span class="dss-slider"></span>
                                </label>
                            </div>
                            <div class="dss-module-card-body">
                                <h3>
                                    <?php echo esc_html($module['name']); ?>
                                    <?php if ($is_premium): ?>
                                        <span class="dss-premium-badge">Premium</span>
                                    <?php endif; ?>
                                    <?php if ($is_beta): ?>
                                        <span class="dss-beta-badge">Beta</span>
                                    <?php endif; ?>
                                </h3>
                                <p><?php echo esc_html($module['description']); ?></p>
                            </div>
                            <div class="dss-module-card-footer">
                                <span class="dss-module-status <?php echo $is_checked ? 'on' : 'off'; ?>">
                                    <span class="dss-status-dot"></span>
                                    <?php echo $is_checked ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!empty($addon_modules)): ?>
                <div class="dss-section">
                    <div class="dss-section-header">
                        <h2><span class="dashicons dashicons-admin-plugins"></span> Addons</h2>
                        <p>Extensiones que amplían los módulos principales</p>
                    </div>
                    <div class="dss-cards-grid">
                        <?php foreach ($addon_modules as $slug => $module):
                            $is_checked = isset($active_modules[$slug]) && $active_modules[$slug] === '1';
                            $req = $module['requires'];
                            $dep_active = isset($active_modules[$req]) && $active_modules[$req] === '1';
                            $dep_missing = !$dep_active;
                            $icon = $module['icon'] ?? 'dashicons-admin-plugins';
                            $is_beta = !empty($module['beta']);
                        ?>
                        <div class="dss-module-card dss-addon-card <?php echo $is_checked ? 'active' : ''; ?> <?php echo $dep_missing ? 'disabled' : ''; ?>">
                            <div class="dss-module-card-header">
                                <span class="dashicons <?php echo esc_attr($icon); ?> dss-module-icon"></span>
                                <label class="dss-switch">
                                    <input type="checkbox" name="dss_suite_active_modules[<?php echo esc_attr($slug); ?>]"
                                        value="1" <?php checked($is_checked, true); ?> <?php echo $dep_missing ? 'disabled' : ''; ?>>
                                    <span class="dss-slider"></span>
                                </label>
                            </div>
                            <div class="dss-module-card-body">
                                <h3>
                                    <?php echo esc_html($module['name']); ?>
                                    <?php if ($is_beta): ?>
                                        <span class="dss-beta-badge">Beta</span>
                                    <?php endif; ?>
                                </h3>
                                <p><?php echo esc_html($module['description']); ?></p>
                            </div>
                            <div class="dss-module-card-footer">
                                <?php if ($dep_missing): ?>
                                    <span class="dss-module-dep-warning">
                                        <span class="dashicons dashicons-warning"></span>
                                        Requiere <?php echo esc_html($this->modules[$req]['name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="dss-module-status <?php echo $is_checked ? 'on' : 'off'; ?>">
                                        <span class="dss-status-dot"></span>
                                        <?php echo $is_checked ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="dss-panel-submit">
                    <?php submit_button('Guardar Cambios', 'primary large', 'submit', false); ?>
                </div>
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
            /* ── Panel Layout ── */
            .dss-panel { max-width: 1100px; margin: 20px 0; }

            .dss-panel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 30px;
                flex-wrap: wrap;
                gap: 15px;
            }
            .dss-panel-header-left {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .dss-panel-header-left h1 {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0;
                font-size: 26px;
                color: #1e293b;
            }
            .dss-badge {
                background: #e0f2fe;
                color: #0369a1;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: 0.5px;
            }
            .dss-panel-stats {
                display: flex;
                gap: 20px;
            }
            .dss-stat {
                text-align: center;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 10px 20px;
            }
            .dss-stat-number {
                display: block;
                font-size: 22px;
                font-weight: 700;
                color: #2271b1;
            }
            .dss-stat-label {
                font-size: 11px;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-weight: 600;
            }

            /* ── Sections ── */
            .dss-section { margin-bottom: 35px; }
            .dss-section-header {
                display: flex;
                align-items: baseline;
                gap: 12px;
                margin-bottom: 18px;
                border-bottom: 2px solid #e2e8f0;
                padding-bottom: 12px;
            }
            .dss-section-header h2 {
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
                font-size: 18px;
                color: #1e293b;
            }
            .dss-section-header h2 .dashicons {
                color: #2271b1;
                font-size: 20px;
                width: 20px;
                height: 20px;
            }
            .dss-section-header p {
                margin: 0;
                color: #94a3b8;
                font-size: 13px;
            }

            /* ── Cards Grid ── */
            .dss-cards-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 18px;
            }

            /* ── Module Card ── */
            .dss-module-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                overflow: hidden;
                transition: all 0.25s ease;
                display: flex;
                flex-direction: column;
            }
            .dss-module-card:hover {
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
                transform: translateY(-10px);
            }
            .dss-module-card.active {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .dss-module-card.disabled {
                opacity: 0.55;
            }
            .dss-module-card.disabled:hover {
                transform: none;
                box-shadow: none;
            }

            .dss-module-card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 20px 0;
            }
            .dss-module-icon {
                font-size: 28px;
                width: 28px;
                height: 28px;
                color: #94a3b8;
                transition: color 0.2s;
            }
            .dss-module-card.active .dss-module-icon {
                color: #2271b1;
            }

            .dss-module-card-body {
                padding: 12px 20px 0;
                flex: 1;
            }
            .dss-module-card-body h3 {
                margin: 0 0 6px;
                font-size: 15px;
                color: #1e293b;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .dss-module-card-body p {
                margin: 0;
                font-size: 13px;
                color: #64748b;
                line-height: 1.5;
            }

            .dss-premium-badge {
                background: linear-gradient(135deg, #f59e0b, #d97706);
                color: #fff;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .dss-module-card-footer {
                padding: 14px 20px;
                margin-top: 12px;
                border-top: 1px solid #f1f5f9;
            }

            /* ── Status ── */
            .dss-module-status {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }
            .dss-module-status.on { color: #16a34a; }
            .dss-module-status.off { color: #94a3b8; }
            .dss-status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                display: inline-block;
            }
            .dss-module-status.on .dss-status-dot { background: #22c55e; box-shadow: 0 0 6px rgba(34,197,94,0.4); }
            .dss-module-status.off .dss-status-dot { background: #cbd5e1; }

            .dss-module-dep-warning {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 12px;
                color: #dc2626;
                font-weight: 500;
            }
            .dss-module-dep-warning .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                color: #dc2626;
            }

            /* ── Addon Card ── */
            .dss-addon-card { border-left: 3px solid #2271b1; }
            .dss-addon-card.disabled { border-left-color: #cbd5e1; }

            /* ── Switch ── */
            .dss-switch {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
                flex-shrink: 0;
            }
            .dss-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .dss-slider {
                position: absolute;
                cursor: pointer;
                top: 0; left: 0; right: 0; bottom: 0;
                background-color: #cbd5e1;
                transition: .3s;
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
                transition: .3s;
                border-radius: 50%;
                box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            }
            input:checked + .dss-slider { background-color: #2271b1; }
            input:checked + .dss-slider:before { transform: translateX(20px); }
            input:disabled + .dss-slider { opacity: 0.4; cursor: not-allowed; }

            /* ── Submit ── */
            .dss-panel-submit {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 20px 25px;
                display: flex;
                align-items: center;
            }
            .dss-panel-submit .button-primary {
                font-size: 14px !important;
                padding: 8px 30px !important;
                height: auto !important;
            }

            .dss-beta-badge {
                background: #fef3c7;
                color: #b45309;
                padding: 2px 8px;
                border: 1px solid #fcd34d;
                border-radius: 4px;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            /* ── Responsive ── */
            @media (max-width: 782px) {
                .dss-cards-grid { grid-template-columns: 1fr; }
                .dss-panel-header { flex-direction: column; align-items: flex-start; }
            }
        </style>
        <?php
    }
}
