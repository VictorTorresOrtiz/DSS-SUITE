<?php
/**
 * DSS Connector - API Handler
 *
 * Procesa peticiones remotas via admin-ajax.php.
 * Autenticacion por API Key en header X-DSS-Key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DSS_Connector_Api {

	public function __construct() {
		// nopriv porque Laravel no tiene sesion de WP
		add_action( 'wp_ajax_nopriv_dss_connector', array( $this, 'handle_request' ) );
		add_action( 'wp_ajax_dss_connector', array( $this, 'handle_request' ) );
	}

	/**
	 * Authenticate the request via API key.
	 */
	private function authenticate(): bool {
		$api_key = get_option( 'dss_connector_api_key', '' );

		if ( empty( $api_key ) ) {
			return false;
		}

		// Check header first, then POST param as fallback
		$provided_key = '';

		if ( isset( $_SERVER['HTTP_X_DSS_KEY'] ) ) {
			$provided_key = sanitize_text_field( $_SERVER['HTTP_X_DSS_KEY'] );
		} elseif ( isset( $_POST['api_key'] ) ) {
			$provided_key = sanitize_text_field( $_POST['api_key'] );
		}

		return hash_equals( $api_key, $provided_key );
	}

	/**
	 * Main request handler - routes to the appropriate method.
	 */
	public function handle_request() {
		// Always return JSON
		header( 'Content-Type: application/json; charset=utf-8' );

		if ( ! $this->authenticate() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 401 );
		}

		$dss_action = isset( $_POST['dss_action'] ) ? sanitize_text_field( $_POST['dss_action'] ) : '';

		$actions = array(
			'site_info'          => 'action_site_info',
			'core_version'       => 'action_core_version',
			'core_check_update'  => 'action_core_check_update',
			'core_update'        => 'action_core_update',
			'plugin_list'        => 'action_plugin_list',
			'plugin_update'      => 'action_plugin_update',
			'plugin_update_all'  => 'action_plugin_update_all',
			'theme_list'         => 'action_theme_list',
			'theme_update'       => 'action_theme_update',
			'theme_update_all'   => 'action_theme_update_all',
			'user_list'          => 'action_user_list',
			'user_create'        => 'action_user_create',
			'user_delete'        => 'action_user_delete',
			'db_export'          => 'action_db_export',
			'maintenance_toggle' => 'action_maintenance_toggle',
			'cache_flush'        => 'action_cache_flush',
		);

		if ( ! isset( $actions[ $dss_action ] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid action: ' . $dss_action ) );
		}

		$method = $actions[ $dss_action ];
		$result = $this->$method();

		wp_send_json_success( $result );
	}

	// --------------------------------------------------
	// Core
	// --------------------------------------------------

	private function action_core_version(): array {
		global $wp_version;
		return array( 'version' => $wp_version );
	}

	private function action_core_check_update(): array {
		wp_version_check();
		$updates = get_core_updates();

		$available = false;
		$latest    = '';

		if ( is_array( $updates ) ) {
			foreach ( $updates as $update ) {
				if ( $update->response === 'upgrade' ) {
					$available = true;
					$latest    = $update->current;
					break;
				}
			}
		}

		return array(
			'update_available' => $available,
			'latest_version'   => $latest,
			'current_version'  => get_bloginfo( 'version' ),
		);
	}

	private function action_core_update(): array {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/update.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		wp_version_check();
		$updates = get_core_updates();

		if ( empty( $updates ) || $updates[0]->response === 'latest' ) {
			return array( 'message' => 'WordPress ya esta actualizado.' );
		}

		$upgrader = new Core_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( $updates[0] );

		if ( is_wp_error( $result ) ) {
			return array( 'error' => $result->get_error_message() );
		}

		return array(
			'message' => 'WordPress actualizado correctamente.',
			'version' => get_bloginfo( 'version' ),
		);
	}

	// --------------------------------------------------
	// Plugins
	// --------------------------------------------------

	private function action_plugin_list(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		$update_info = get_site_transient( 'update_plugins' );
		$updates     = isset( $update_info->response ) ? $update_info->response : array();

		$plugins = array();
		foreach ( $all_plugins as $file => $data ) {
			$plugins[] = array(
				'name'    => $data['Name'],
				'slug'    => dirname( $file ) !== '.' ? dirname( $file ) : basename( $file, '.php' ),
				'file'    => $file,
				'version' => $data['Version'],
				'status'  => in_array( $file, $active_plugins, true ) ? 'active' : 'inactive',
				'update'  => isset( $updates[ $file ] ) ? 'available' : 'none',
				'update_version' => isset( $updates[ $file ] ) ? $updates[ $file ]->new_version : null,
			);
		}

		return array( 'plugins' => $plugins );
	}

	private function action_plugin_update(): array {
		$plugin_file = isset( $_POST['plugin'] ) ? sanitize_text_field( $_POST['plugin'] ) : '';

		if ( empty( $plugin_file ) ) {
			return array( 'error' => 'Plugin file not specified.' );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( $plugin_file );

		if ( is_wp_error( $result ) ) {
			return array( 'error' => $result->get_error_message() );
		}

		return array( 'message' => 'Plugin actualizado: ' . $plugin_file );
	}

	private function action_plugin_update_all(): array {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$update_info = get_site_transient( 'update_plugins' );
		$to_update   = isset( $update_info->response ) ? array_keys( $update_info->response ) : array();

		if ( empty( $to_update ) ) {
			return array( 'message' => 'Todos los plugins estan actualizados.', 'updated' => 0 );
		}

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->bulk_upgrade( $to_update );

		$updated = 0;
		$errors  = array();
		if ( is_array( $result ) ) {
			foreach ( $result as $plugin => $res ) {
				if ( $res === true || ( is_object( $res ) && ! is_wp_error( $res ) ) ) {
					$updated++;
				} elseif ( is_wp_error( $res ) ) {
					$errors[] = $plugin . ': ' . $res->get_error_message();
				}
			}
		}

		return array(
			'message' => "Actualizados {$updated} de " . count( $to_update ) . ' plugins.',
			'updated' => $updated,
			'errors'  => $errors,
		);
	}

	// --------------------------------------------------
	// Themes
	// --------------------------------------------------

	private function action_theme_list(): array {
		$all_themes   = wp_get_themes();
		$active_theme = get_stylesheet();

		$update_info = get_site_transient( 'update_themes' );
		$updates     = isset( $update_info->response ) ? $update_info->response : array();

		$themes = array();
		foreach ( $all_themes as $slug => $theme ) {
			$themes[] = array(
				'name'    => $theme->get( 'Name' ),
				'slug'    => $slug,
				'version' => $theme->get( 'Version' ),
				'status'  => ( $slug === $active_theme ) ? 'active' : 'inactive',
				'update'  => isset( $updates[ $slug ] ) ? 'available' : 'none',
				'update_version' => isset( $updates[ $slug ] ) ? $updates[ $slug ]['new_version'] : null,
			);
		}

		return array( 'themes' => $themes );
	}

	private function action_theme_update(): array {
		$theme_slug = isset( $_POST['theme'] ) ? sanitize_text_field( $_POST['theme'] ) : '';

		if ( empty( $theme_slug ) ) {
			return array( 'error' => 'Theme slug not specified.' );
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( $theme_slug );

		if ( is_wp_error( $result ) ) {
			return array( 'error' => $result->get_error_message() );
		}

		return array( 'message' => 'Tema actualizado: ' . $theme_slug );
	}

	private function action_theme_update_all(): array {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$update_info = get_site_transient( 'update_themes' );
		$to_update   = isset( $update_info->response ) ? array_keys( $update_info->response ) : array();

		if ( empty( $to_update ) ) {
			return array( 'message' => 'Todos los temas estan actualizados.', 'updated' => 0 );
		}

		$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->bulk_upgrade( $to_update );

		$updated = 0;
		if ( is_array( $result ) ) {
			foreach ( $result as $res ) {
				if ( $res === true || ( is_object( $res ) && ! is_wp_error( $res ) ) ) {
					$updated++;
				}
			}
		}

		return array(
			'message' => "Actualizados {$updated} de " . count( $to_update ) . ' temas.',
			'updated' => $updated,
		);
	}

	// --------------------------------------------------
	// Users
	// --------------------------------------------------

	private function action_user_list(): array {
		$wp_users = get_users( array( 'number' => 100 ) );
		$users    = array();

		foreach ( $wp_users as $user ) {
			$users[] = array(
				'ID'           => $user->ID,
				'user_login'   => $user->user_login,
				'user_email'   => $user->user_email,
				'display_name' => $user->display_name,
				'roles'        => implode( ', ', $user->roles ),
			);
		}

		return array( 'users' => $users );
	}

	private function action_user_create(): array {
		$username = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$role     = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : 'subscriber';

		if ( empty( $username ) || empty( $email ) ) {
			return array( 'error' => 'Username and email are required.' );
		}

		$password = wp_generate_password( 16, true, true );
		$user_id  = wp_insert_user( array(
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $password,
			'role'       => $role,
		) );

		if ( is_wp_error( $user_id ) ) {
			return array( 'error' => $user_id->get_error_message() );
		}

		return array(
			'message'  => 'Usuario creado correctamente.',
			'user_id'  => $user_id,
			'password' => $password,
		);
	}

	private function action_user_delete(): array {
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( empty( $user_id ) ) {
			return array( 'error' => 'User ID is required.' );
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';

		$result = wp_delete_user( $user_id );

		if ( ! $result ) {
			return array( 'error' => 'No se pudo eliminar el usuario.' );
		}

		return array( 'message' => 'Usuario eliminado correctamente.' );
	}

	// --------------------------------------------------
	// Database
	// --------------------------------------------------

	private function action_db_export(): array {
		global $wpdb;

		$upload_dir = wp_upload_dir();
		$filename   = 'dss-backup-' . gmdate( 'Y-m-d-His' ) . '.sql';
		$filepath   = $upload_dir['basedir'] . '/' . $filename;

		$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );

		if ( empty( $tables ) ) {
			return array( 'error' => 'No se encontraron tablas.' );
		}

		$sql = "-- DSS Connector DB Export\n";
		$sql .= "-- Date: " . gmdate( 'Y-m-d H:i:s' ) . "\n";
		$sql .= "-- Database: " . DB_NAME . "\n\n";

		foreach ( $tables as $table ) {
			// Table structure
			$create = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
			if ( $create ) {
				$sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
				$sql .= $create[1] . ";\n\n";
			}

			// Table data
			$rows = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A );
			if ( $rows ) {
				foreach ( $rows as $row ) {
					$values = array_map( function( $v ) use ( $wpdb ) {
						if ( $v === null ) {
							return 'NULL';
						}
						return "'" . esc_sql( $v ) . "'";
					}, array_values( $row ) );

					$sql .= "INSERT INTO `{$table}` VALUES (" . implode( ', ', $values ) . ");\n";
				}
				$sql .= "\n";
			}
		}

		$written = file_put_contents( $filepath, $sql );

		if ( $written === false ) {
			return array( 'error' => 'No se pudo escribir el archivo de backup.' );
		}

		return array(
			'message'  => 'Base de datos exportada correctamente.',
			'filename' => $filename,
			'path'     => $filepath,
			'size'     => size_format( $written ),
			'url'      => $upload_dir['baseurl'] . '/' . $filename,
		);
	}

	// --------------------------------------------------
	// Maintenance & Cache
	// --------------------------------------------------

	private function action_maintenance_toggle(): array {
		$enable = isset( $_POST['enable'] ) ? filter_var( $_POST['enable'], FILTER_VALIDATE_BOOLEAN ) : false;
		$file   = ABSPATH . '.maintenance';

		if ( $enable ) {
			$content = '<?php $upgrading = ' . time() . '; ?>';
			file_put_contents( $file, $content );
			return array( 'message' => 'Modo mantenimiento activado.', 'maintenance' => true );
		} else {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
			return array( 'message' => 'Modo mantenimiento desactivado.', 'maintenance' => false );
		}
	}

	private function action_cache_flush(): array {
		wp_cache_flush();

		// Clear transients
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'" );

		return array( 'message' => 'Cache limpiada correctamente.' );
	}

	// --------------------------------------------------
	// Site Info (combined)
	// --------------------------------------------------

	private function action_site_info(): array {
		global $wp_version;

		// Core update check
		$core_update = $this->action_core_check_update();

		// Plugin stats
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();
		$update_plugins = get_site_transient( 'update_plugins' );
		$plugin_updates = isset( $update_plugins->response ) ? count( $update_plugins->response ) : 0;

		// Theme stats
		$all_themes = wp_get_themes();
		$update_themes = get_site_transient( 'update_themes' );
		$theme_updates = isset( $update_themes->response ) ? count( $update_themes->response ) : 0;

		return array(
			'wp_version'            => $wp_version,
			'php_version'           => phpversion(),
			'site_url'              => get_site_url(),
			'home_url'              => get_home_url(),
			'site_name'             => get_bloginfo( 'name' ),
			'core_update_available' => $core_update['update_available'],
			'plugins_total'         => count( $all_plugins ),
			'plugins_updates'       => $plugin_updates,
			'themes_total'          => count( $all_themes ),
			'themes_updates'        => $theme_updates,
			'is_multisite'          => is_multisite(),
			'ssl_active'            => is_ssl(),
			'dss_connector_version' => DSS_CONNECTOR_VERSION,
		);
	}
}
