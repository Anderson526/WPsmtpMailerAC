<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Núcleo del plugin: carga módulos y centraliza los ajustes SMTP.
 */
final class ASM_Plugin {

	const OPTION = 'anderc_asm_settings';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		new ASM_SMTP_Config();
		new ASM_Email_Logger();

		if ( is_admin() ) {
			new ASM_Admin();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'anderc-smtp-mailer', false, dirname( plugin_basename( ANDERC_ASM_FILE ) ) . '/languages' );
	}

	public static function defaults() {
		return array(
			'enabled'    => 0,
			'host'       => '',
			'port'       => 587,
			'encryption' => 'tls',
			'auth'       => 1,
			'username'   => '',
			'password'   => '',
			'from_email' => get_bloginfo( 'admin_email' ),
			'from_name'  => get_bloginfo( 'name' ),
			'force_from' => 1,
			'log_emails' => 1,
		);
	}

	public static function get_settings() {
		$settings = get_option( self::OPTION, array() );
		$settings = wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );

		// Las constantes en wp-config.php tienen prioridad (recomendado para credenciales).
		if ( defined( 'ANDERC_SMTP_USER' ) ) {
			$settings['username'] = ANDERC_SMTP_USER;
		}
		if ( defined( 'ANDERC_SMTP_PASS' ) ) {
			$settings['password'] = ANDERC_SMTP_PASS;
		}

		return $settings;
	}

	public static function update_settings( array $settings ) {
		update_option( self::OPTION, $settings );
	}
}
