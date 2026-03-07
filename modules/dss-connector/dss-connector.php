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

// Maintenance mode via option (no .maintenance file, never blocks admin-ajax)
if ( get_option( 'dss_connector_maintenance', false ) ) {
	add_action( 'template_redirect', function () {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_die(
			'<h1>Sitio en mantenimiento</h1><p>Estamos realizando tareas de mantenimiento. Vuelve pronto.</p>',
			'Mantenimiento',
			array( 'response' => 503 )
		);
	} );
}
