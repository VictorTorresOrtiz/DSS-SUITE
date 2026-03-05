<?php
/**
 * Module: DSS Themes (White Label)
 * Description: Añade nuevos widgets y renombra items del dashboard y personaliza la apariencia del tema.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DSS_WHITE_LABEL_VERSION', '1.3');
define('DSS_WHITE_LABEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DSS_WHITE_LABEL_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once DSS_WHITE_LABEL_PLUGIN_DIR . 'includes/class-theme-branding.php';
require_once DSS_WHITE_LABEL_PLUGIN_DIR . 'includes/class-dashboard-widgets.php';

function dss_white_label_init()
{
    new DSS_Theme_Branding();
    new FFL_Admin_Theme_Widgets();
}
add_action('init', 'dss_white_label_init');