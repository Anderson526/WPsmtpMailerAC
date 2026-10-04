<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Página de administración: ajustes SMTP, registro de correos y envío de prueba.
 */
class ASM_Admin {

	const SLUG = 'anderc-smtp-mailer';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_anderc_asm_save', array( $this, 'save' ) );
		add_action( 'admin_post_anderc_asm_test', array( $this, 'send_test' ) );
		add_action( 'admin_post_anderc_asm_clear_logs', array( $this, 'clear_logs' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'AnderC SMTP', 'anderc-smtp-mailer' ),
			__( 'AnderC SMTP', 'anderc-smtp-mailer' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-email-alt',
			63
		);
	}

	public function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'anderc-asm-admin', ANDERC_ASM_URL . 'assets/css/admin.css', array(), ANDERC_ASM_VERSION );
	}

	public function save() {
		$this->guard( 'anderc_asm_save' );

		$settings = ASM_Plugin::get_settings();

		$settings['enabled']    = empty( $_POST['enabled'] ) ? 0 : 1;
		$settings['host']       = isset( $_POST['host'] ) ? sanitize_text_field( wp_unslash( $_POST['host'] ) ) : '';
		$settings['port']       = isset( $_POST['port'] ) ? absint( $_POST['port'] ) : 587;
		$settings['encryption'] = isset( $_POST['encryption'] ) && in_array( $_POST['encryption'], array( 'none', 'ssl', 'tls' ), true ) ? sanitize_key( $_POST['encryption'] ) : 'tls';
		$settings['auth']       = empty( $_POST['auth'] ) ? 0 : 1;
		$settings['username']   = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$settings['from_email'] = isset( $_POST['from_email'] ) ? sanitize_email( wp_unslash( $_POST['from_email'] ) ) : '';
		$settings['from_name']  = isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : '';
		$settings['force_from'] = empty( $_POST['force_from'] ) ? 0 : 1;
		$settings['log_emails'] = empty( $_POST['log_emails'] ) ? 0 : 1;

		// Solo sobrescribir la contraseña si se escribió una nueva.
		if ( ! empty( $_POST['password'] ) && ! defined( 'ANDERC_SMTP_PASS' ) ) {
			$settings['password'] = (string) wp_unslash( $_POST['password'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		ASM_Plugin::update_settings( $settings );
		$this->redirect( 'settings', 'saved' );
	}

	public function send_test() {
		$this->guard( 'anderc_asm_test' );

		$to = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';
		if ( ! is_email( $to ) ) {
			$this->redirect( 'test', 'invalid_email' );
		}

		$sent = wp_mail(
			$to,
			__( 'Correo de prueba — AnderC SMTP', 'anderc-smtp-mailer' ),
			__( "¡Hola!\n\nSi estás leyendo esto, la configuración SMTP de tu sitio funciona correctamente.\n\n— AnderC SMTP & Email Logger", 'anderc-smtp-mailer' )
		);

		$this->redirect( 'test', $sent ? 'test_sent' : 'test_failed' );
	}

	public function clear_logs() {
		$this->guard( 'anderc_asm_clear_logs' );
		ASM_Email_Logger::clear_logs();
		$this->redirect( 'logs', 'logs_cleared' );
	}

	private function guard( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'anderc-smtp-mailer' ) );
		}
		check_admin_referer( $nonce_action );
	}

	private function redirect( $tab, $msg ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => self::SLUG,
					'tab'  => $tab,
					'msg'  => $msg,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification
		$tab = in_array( $tab, array( 'settings', 'logs', 'test' ), true ) ? $tab : 'settings';
		?>
		<div class="wrap anderc-wrap">
			<h1><span class="anderc-badge">AnderC</span> <?php esc_html_e( 'SMTP & Email Logger', 'anderc-smtp-mailer' ); ?></h1>

			<?php $this->notices(); ?>

			<h2 class="nav-tab-wrapper">
				<?php
				$tabs = array(
					'settings' => __( 'Ajustes SMTP', 'anderc-smtp-mailer' ),
					'logs'     => __( 'Registro de correos', 'anderc-smtp-mailer' ),
					'test'     => __( 'Enviar prueba', 'anderc-smtp-mailer' ),
				);
				foreach ( $tabs as $key => $label ) :
					?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::SLUG, 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>" class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</h2>

			<?php
			if ( 'logs' === $tab ) {
				$this->render_logs();
			} elseif ( 'test' === $tab ) {
				$this->render_test();
			} else {
				$this->render_settings();
			}
			?>
		</div>
		<?php
	}

	private function notices() {
		$msg = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$map = array(
			'saved'         => array( 'success', __( 'Ajustes guardados correctamente.', 'anderc-smtp-mailer' ) ),
			'test_sent'     => array( 'success', __( 'Correo de prueba enviado. Revisa la bandeja de entrada (y la carpeta de spam).', 'anderc-smtp-mailer' ) ),
			'test_failed'   => array( 'error', __( 'El correo de prueba falló. Revisa el registro de correos para ver el error.', 'anderc-smtp-mailer' ) ),
			'invalid_email' => array( 'error', __( 'La dirección de correo no es válida.', 'anderc-smtp-mailer' ) ),
			'logs_cleared'  => array( 'success', __( 'Registro de correos vaciado.', 'anderc-smtp-mailer' ) ),
		);
		if ( isset( $map[ $msg ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
		}
	}

	private function render_settings() {
		$s = ASM_Plugin::get_settings();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="anderc_asm_save" />
			<?php wp_nonce_field( 'anderc_asm_save' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Activar SMTP', 'anderc-smtp-mailer' ); ?></th>
					<td><label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?> /> <?php esc_html_e( 'Enviar todos los correos a través del servidor SMTP configurado', 'anderc-smtp-mailer' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="host"><?php esc_html_e( 'Servidor SMTP', 'anderc-smtp-mailer' ); ?></label></th>
					<td><input type="text" class="regular-text" id="host" name="host" value="<?php echo esc_attr( $s['host'] ); ?>" placeholder="smtp.gmail.com" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="port"><?php esc_html_e( 'Puerto', 'anderc-smtp-mailer' ); ?></label></th>
					<td>
						<input type="number" id="port" name="port" value="<?php echo esc_attr( $s['port'] ); ?>" min="1" max="65535" />
						<p class="description"><?php esc_html_e( 'Habituales: 587 (TLS), 465 (SSL), 25 (sin cifrado).', 'anderc-smtp-mailer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Cifrado', 'anderc-smtp-mailer' ); ?></th>
					<td>
						<label><input type="radio" name="encryption" value="tls" <?php checked( 'tls', $s['encryption'] ); ?> /> TLS</label>&nbsp;&nbsp;
						<label><input type="radio" name="encryption" value="ssl" <?php checked( 'ssl', $s['encryption'] ); ?> /> SSL</label>&nbsp;&nbsp;
						<label><input type="radio" name="encryption" value="none" <?php checked( 'none', $s['encryption'] ); ?> /> <?php esc_html_e( 'Ninguno', 'anderc-smtp-mailer' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Autenticación', 'anderc-smtp-mailer' ); ?></th>
					<td><label><input type="checkbox" name="auth" value="1" <?php checked( $s['auth'] ); ?> /> <?php esc_html_e( 'El servidor requiere usuario y contraseña', 'anderc-smtp-mailer' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="username"><?php esc_html_e( 'Usuario SMTP', 'anderc-smtp-mailer' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="username" name="username" value="<?php echo esc_attr( $s['username'] ); ?>" autocomplete="off" <?php disabled( defined( 'ANDERC_SMTP_USER' ) ); ?> />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="password"><?php esc_html_e( 'Contraseña SMTP', 'anderc-smtp-mailer' ); ?></label></th>
					<td>
						<input type="password" class="regular-text" id="password" name="password" value="" autocomplete="new-password" placeholder="<?php echo $s['password'] ? esc_attr__( '•••••••• (guardada, escribe para cambiarla)', 'anderc-smtp-mailer' ) : ''; ?>" <?php disabled( defined( 'ANDERC_SMTP_PASS' ) ); ?> />
						<p class="description"><?php esc_html_e( 'Recomendado: define ANDERC_SMTP_USER y ANDERC_SMTP_PASS en wp-config.php para no guardar credenciales en la base de datos.', 'anderc-smtp-mailer' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="from_email"><?php esc_html_e( 'Correo del remitente', 'anderc-smtp-mailer' ); ?></label></th>
					<td><input type="email" class="regular-text" id="from_email" name="from_email" value="<?php echo esc_attr( $s['from_email'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="from_name"><?php esc_html_e( 'Nombre del remitente', 'anderc-smtp-mailer' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="from_name" name="from_name" value="<?php echo esc_attr( $s['from_name'] ); ?>" />
						<p><label><input type="checkbox" name="force_from" value="1" <?php checked( $s['force_from'] ); ?> /> <?php esc_html_e( 'Forzar este remitente en todos los correos', 'anderc-smtp-mailer' ); ?></label></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Registro de correos', 'anderc-smtp-mailer' ); ?></th>
					<td><label><input type="checkbox" name="log_emails" value="1" <?php checked( $s['log_emails'] ); ?> /> <?php esc_html_e( 'Guardar cada correo enviado en el registro', 'anderc-smtp-mailer' ); ?></label></td>
				</tr>
			</table>

			<?php submit_button( __( 'Guardar cambios', 'anderc-smtp-mailer' ) ); ?>
		</form>
		<?php
	}

	private function render_logs() {
		$per_page = 20;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$total    = ASM_Email_Logger::count_logs();
		$logs     = ASM_Email_Logger::get_logs( $paged, $per_page );
		?>
		<p>
			<?php
			/* translators: %d: número total de correos registrados. */
			printf( esc_html__( 'Total de correos registrados: %d', 'anderc-smtp-mailer' ), (int) $total );
			?>
		</p>

		<table class="widefat striped anderc-logs">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Fecha', 'anderc-smtp-mailer' ); ?></th>
					<th><?php esc_html_e( 'Para', 'anderc-smtp-mailer' ); ?></th>
					<th><?php esc_html_e( 'Asunto', 'anderc-smtp-mailer' ); ?></th>
					<th><?php esc_html_e( 'Estado', 'anderc-smtp-mailer' ); ?></th>
					<th><?php esc_html_e( 'Detalle', 'anderc-smtp-mailer' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No hay correos registrados todavía.', 'anderc-smtp-mailer' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( $log->sent_at ); ?></td>
						<td><?php echo esc_html( $log->to_email ); ?></td>
						<td><?php echo esc_html( $log->subject ); ?></td>
						<td><span class="anderc-status anderc-status--<?php echo esc_attr( $log->status ); ?>"><?php echo esc_html( ucfirst( $log->status ) ); ?></span></td>
						<td>
							<details>
								<summary><?php esc_html_e( 'Ver mensaje', 'anderc-smtp-mailer' ); ?></summary>
								<pre class="anderc-log-message"><?php echo esc_html( wp_trim_words( $log->message, 200 ) ); ?></pre>
								<?php if ( ! empty( $log->error ) ) : ?>
									<p class="anderc-log-error"><strong><?php esc_html_e( 'Error:', 'anderc-smtp-mailer' ); ?></strong> <?php echo esc_html( $log->error ); ?></p>
								<?php endif; ?>
							</details>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

		<?php
		$total_pages = (int) ceil( $total / $per_page );
		if ( $total_pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
				array(
					'base'    => add_query_arg( 'paged', '%#%' ),
					'format'  => '',
					'current' => $paged,
					'total'   => $total_pages,
				)
			);
			echo '</div></div>';
		}
		?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
			<input type="hidden" name="action" value="anderc_asm_clear_logs" />
			<?php wp_nonce_field( 'anderc_asm_clear_logs' ); ?>
			<?php submit_button( __( 'Vaciar registro', 'anderc-smtp-mailer' ), 'delete', 'submit', false, array( 'onclick' => 'return confirm("' . esc_js( __( '¿Vaciar todo el registro de correos?', 'anderc-smtp-mailer' ) ) . '");' ) ); ?>
		</form>
		<?php
	}

	private function render_test() {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="anderc_asm_test" />
			<?php wp_nonce_field( 'anderc_asm_test' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="test_email"><?php esc_html_e( 'Enviar correo de prueba a', 'anderc-smtp-mailer' ); ?></label></th>
					<td><input type="email" class="regular-text" id="test_email" name="test_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" required /></td>
				</tr>
			</table>

			<?php submit_button( __( 'Enviar prueba', 'anderc-smtp-mailer' ) ); ?>
		</form>
		<?php
	}
}
