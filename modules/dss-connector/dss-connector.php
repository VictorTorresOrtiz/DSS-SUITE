<?php
/**
 * DSS Connector Module
 *
 * Expone una API via admin-ajax.php para que DSS Gestion
 * pueda gestionar el sitio WordPress remotamente.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DSS_CONNECTOR_VERSION', '1.0.0' );
define( 'DSS_CONNECTOR_DIR', plugin_dir_path( __FILE__ ) );

require_once DSS_CONNECTOR_DIR . 'includes/class-dss-connector-admin.php';
require_once DSS_CONNECTOR_DIR . 'includes/class-dss-connector-api.php';

// Admin settings (API key management)
new DSS_Connector_Admin();

// API handler (admin-ajax endpoints)
new DSS_Connector_Api();
