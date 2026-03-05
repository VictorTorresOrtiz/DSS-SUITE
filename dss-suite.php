<?php
/**
 * Plugin Name: DSS SUITE
 * Plugin URI:  https://dssnetwork.es
 * Description: Suite completa de plugins utilizados por la arquitectura de DSS NETWORK.
 * Version:     3.0
 * Author:      Víctor Torres Ortiz (DSS NETWORK)
 * Author URI:  https://dssnetwork.es
 * Text Domain: dss-suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'DSS_SUITE_VERSION', '2.0.1' );
define( 'DSS_SUITE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSS_SUITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Core Manager
require_once DSS_SUITE_PLUGIN_DIR . 'includes/class-dss-suite-core.php';

// Initialize the suite
function dss_suite_init() {
	new DSS_Suite_Core();
}
add_action( 'plugins_loaded', 'dss_suite_init' );
