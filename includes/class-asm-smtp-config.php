<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configura PHPMailer mediante el hook `phpmailer_init` con los datos guardados.
 */
class ASM_SMTP_Config {

	public function __construct() {
		add_action( 'phpmailer_init', array( $this, 'configure' ) );
		add_filter( 'wp_mail_from', array( $this, 'from_email' ), 999 );
		add_filter( 'wp_mail_from_name', array( $this, 'from_name' ), 999 );
	}

	/**
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Instancia de PHPMailer.
	 */
	public function configure( $phpmailer ) {
		$s = ASM_Plugin::get_settings();

		if ( empty( $s['enabled'] ) || empty( $s['host'] ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host    = $s['host'];        // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$phpmailer->Port    = (int) $s['port'];  // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$phpmailer->Timeout = 10;                // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		if ( in_array( $s['encryption'], array( 'ssl', 'tls' ), true ) ) {
			$phpmailer->SMTPSecure = $s['encryption']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		} else {
			$phpmailer->SMTPSecure = '';   // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$phpmailer->SMTPAutoTLS = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}

		if ( ! empty( $s['auth'] ) ) {
			$phpmailer->SMTPAuth = true;             // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$phpmailer->Username = $s['username'];   // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$phpmailer->Password = $s['password'];   // phpcs:ignore WordPress.NamingConventions.ValidVariableName
		}
	}

	public function from_email( $email ) {
		$s = ASM_Plugin::get_settings();
		if ( ! empty( $s['enabled'] ) && ! empty( $s['force_from'] ) && is_email( $s['from_email'] ) ) {
			return $s['from_email'];
		}
		return $email;
	}

	public function from_name( $name ) {
		$s = ASM_Plugin::get_settings();
		if ( ! empty( $s['enabled'] ) && ! empty( $s['force_from'] ) && ! empty( $s['from_name'] ) ) {
			return $s['from_name'];
		}
		return $name;
	}
}
