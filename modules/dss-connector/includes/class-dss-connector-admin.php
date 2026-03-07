<?php
/**
 * DSS Connector - Admin Settings
 *
 * Genera y gestiona la API key para autenticar peticiones remotas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DSS_Connector_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Register submenu under DSS Suite.
	 */
	public function register_submenu() {
		add_submenu_page(
			'dss-suite',
			'DSS Connector',
			'Connector API',
			'manage_options',
			'dss-connector',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle generate/regenerate API key actions.
	 */
	public function handle_actions() {
		if ( ! isset( $_POST['dss_connector_action'] ) ) {
			return;
		}

		check_admin_referer( 'dss_connector_key_action', 'dss_connector_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_text_field( $_POST['dss_connector_action'] );

		if ( $action === 'generate' || $action === 'regenerate' ) {
			$key = 'dss_' . bin2hex( random_bytes( 32 ) );
			update_option( 'dss_connector_api_key', $key );
			add_settings_error( 'dss_connector', 'key_generated', 'API Key generada correctamente.', 'success' );
		} elseif ( $action === 'revoke' ) {
			delete_option( 'dss_connector_api_key' );
			add_settings_error( 'dss_connector', 'key_revoked', 'API Key revocada.', 'updated' );
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api_key  = get_option( 'dss_connector_api_key', '' );
		$site_url = admin_url( 'admin-ajax.php' );

		settings_errors( 'dss_connector' );
		?>
		<div class="wrap">
			<h1>DSS Connector - API</h1>
			<p>Este modulo permite que <strong>DSS Gestion</strong> se conecte a este sitio WordPress via HTTP en lugar de SSH.</p>

			<div style="margin-top: 20px; padding: 25px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
				<h2 style="margin-top: 0;">Endpoint</h2>
				<code style="display: block; padding: 10px; background: #f1f5f9; border-radius: 6px; font-size: 14px;"><?php echo esc_html( $site_url ); ?></code>

				<h2 style="margin-top: 25px;">API Key</h2>
				<?php if ( $api_key ) : ?>
					<div style="position: relative;">
						<input type="text" id="dss-api-key" value="<?php echo esc_attr( $api_key ); ?>" readonly
							style="width: 100%; padding: 10px; font-family: monospace; font-size: 14px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px;">
						<button type="button" onclick="navigator.clipboard.writeText(document.getElementById('dss-api-key').value); this.textContent='Copiado!'; setTimeout(() => this.textContent='Copiar', 2000);"
							class="button" style="position: absolute; right: 5px; top: 5px;">Copiar</button>
					</div>

					<div style="margin-top: 15px; display: flex; gap: 10px;">
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'dss_connector_key_action', 'dss_connector_nonce' ); ?>
							<input type="hidden" name="dss_connector_action" value="regenerate">
							<button type="submit" class="button button-secondary" onclick="return confirm('Se invalidara la clave actual. Continuar?');">
								Regenerar Key
							</button>
						</form>
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'dss_connector_key_action', 'dss_connector_nonce' ); ?>
							<input type="hidden" name="dss_connector_action" value="revoke">
							<button type="submit" class="button" style="color: #dc2626;" onclick="return confirm('Se revocara el acceso remoto. Continuar?');">
								Revocar Key
							</button>
						</form>
					</div>
				<?php else : ?>
					<p style="color: #64748b;">No hay API Key configurada. Genera una para permitir el acceso remoto.</p>
					<form method="post">
						<?php wp_nonce_field( 'dss_connector_key_action', 'dss_connector_nonce' ); ?>
						<input type="hidden" name="dss_connector_action" value="generate">
						<button type="submit" class="button button-primary button-large">Generar API Key</button>
					</form>
				<?php endif; ?>
			</div>

			<div style="margin-top: 20px; padding: 25px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
				<h2 style="margin-top: 0;">Configuracion en DSS Gestion</h2>
				<p>Al dar de alta o editar este sitio en DSS Gestion:</p>
				<ol>
					<li>Selecciona <strong>Metodo de conexion: Plugin API</strong></li>
					<li>Introduce la API Key generada arriba</li>
					<li>El endpoint se construye automaticamente desde el dominio del sitio</li>
				</ol>

				<h3>Acciones disponibles</h3>
				<table class="wp-list-table widefat fixed striped" style="max-width: 500px;">
					<thead><tr><th>Accion</th><th>Descripcion</th></tr></thead>
					<tbody>
						<tr><td><code>site_info</code></td><td>Info general del sitio</td></tr>
						<tr><td><code>plugin_list</code></td><td>Listar plugins</td></tr>
						<tr><td><code>plugin_update</code></td><td>Actualizar plugin</td></tr>
						<tr><td><code>plugin_update_all</code></td><td>Actualizar todos</td></tr>
						<tr><td><code>theme_list</code></td><td>Listar temas</td></tr>
						<tr><td><code>theme_update</code></td><td>Actualizar tema</td></tr>
						<tr><td><code>theme_update_all</code></td><td>Actualizar todos</td></tr>
						<tr><td><code>user_list</code></td><td>Listar usuarios</td></tr>
						<tr><td><code>user_create</code></td><td>Crear usuario</td></tr>
						<tr><td><code>user_delete</code></td><td>Eliminar usuario</td></tr>
						<tr><td><code>core_version</code></td><td>Version de WP</td></tr>
						<tr><td><code>core_check_update</code></td><td>Comprobar actualizacion</td></tr>
						<tr><td><code>core_update</code></td><td>Actualizar WordPress</td></tr>
						<tr><td><code>db_export</code></td><td>Exportar base de datos</td></tr>
						<tr><td><code>maintenance_toggle</code></td><td>Activar/desactivar mantenimiento</td></tr>
						<tr><td><code>cache_flush</code></td><td>Limpiar cache</td></tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
