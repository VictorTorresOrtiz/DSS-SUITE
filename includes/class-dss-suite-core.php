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
            'file' => 'seo-manager/tagmanager.php',
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
            'name' => 'Chatbox de Soporte',
            'description' => 'Añade un chatbox moderno en el área de administración para consultas de clientes.',
            'file' => 'chatbox/chatbox.php',
        )
    );

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Init settings page
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Load active modules
        $this->load_active_modules();
    }

    /**
     * Register the main DSS Suite menu in the WordPress admin.
     */
    public function register_admin_menu()
    {
        add_menu_page(
            'DSS Suite',
            'DSS Suite',
            'manage_options',
            'dss-suite',
            array($this, 'render_settings_page'),
            'dashicons-admin-generic', // Optional icon
            65
        );
    }

    /**
     * Register the plugin settings.
     */
    public function register_settings()
    {
        register_setting('dss_suite_options_group', 'dss_suite_active_modules');
    }

    /**
     * Load only the modules that are enabled in the settings.
     */
    private function load_active_modules()
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

        $active_modules = get_option('dss_suite_active_modules', array());

        // Show success message if saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error('dss_suite_messages', 'dss_suite_message', __('Settings Saved', 'dss-suite'), 'updated');
        }

        settings_errors('dss_suite_messages');
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?> - Centro de Control
            </h1>
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
                                <td><strong>
                                        <?php echo esc_html($module['name']); ?>
                                    </strong></td>
                                <td>
                                    <?php echo esc_html($module['description']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button('Guardar Cambios'); ?>
            </form>
        </div>

        <!-- Optional: Add some inline styles for a nicer toggle or UI -->
        <style>
            .wrap h1 {
                margin-bottom: 20px;
            }

            .wp-list-table th,
            .wp-list-table td {
                vertical-align: middle;
            }

            /* Basic CSS for a toggle switch */
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
                /* WP Blue */
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
