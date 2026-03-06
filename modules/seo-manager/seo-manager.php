<?php
/**
 * Module: DSS SEO Manager
 * Description: Cambia etiquetas HTML y añade clases personalizadas desde el panel de ajustes.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('DSS_SEO_MANAGER_VERSION', '1.3.0');
define('DSS_SEO_MANAGER_DIR', plugin_dir_path(__FILE__));
define('DSS_SEO_MANAGER_URL', plugin_dir_url(__FILE__));

// Include admin class
require_once DSS_SEO_MANAGER_DIR . 'includes/class-seo-manager-admin.php';

// Initialize the module
// The class handles both admin and public hooks internally
new DSS_SEO_Manager_Admin();
