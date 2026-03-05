<?php
/**
 * Module: Chatbox de Soporte
 * Description: Añade un chatbox moderno en el área de administración para consultas de clientes.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('DSS_CHATBOX_VERSION', '1.0.0');
define('DSS_CHATBOX_DIR', plugin_dir_path(__FILE__));
define('DSS_CHATBOX_URL', plugin_dir_url(__FILE__));

// Load the admin class
require_once DSS_CHATBOX_DIR . 'includes/class-chatbox-admin.php';

// Initialize the chatbox
function dss_chatbox_init()
{
    if (is_admin()) {
        new DSS_Chatbox_Admin();
    }
}
add_action('init', 'dss_chatbox_init');

//TODO: Responsive en movil