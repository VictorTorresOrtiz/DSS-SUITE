<?php
/**
 * Module: Public Frontend Chatbot
 * Description: Standalone chatbot for the public website with custom settings and multimodal support.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('DSS_PUBLIC_CHAT_VERSION', '1.0.0');
define('DSS_PUBLIC_CHAT_DIR', plugin_dir_path(__FILE__));
define('DSS_PUBLIC_CHAT_URL', plugin_dir_url(__FILE__));

// Load dependencies
require_once DSS_PUBLIC_CHAT_DIR . 'includes/class-public-chat-admin.php';

// Initialize the module
function dss_public_chat_init()
{
    new DSS_Public_Chat_Admin();
}
add_action('init', 'dss_public_chat_init');
