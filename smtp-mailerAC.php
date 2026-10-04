<?php
/*
Plugin Name: SMTP & Email Logger - toolkitAC
Plugin URI: https://anderson526.github.io/portfolio-profesional/
Description: Envía los correos de WordPress por SMTP para evitar que lleguen a spam y guarda un registro de todos los envíos. Parte de la suite AC Essential.
Version: 1.0.0
Author: Anderson Chila
Author URI: https://anderson526.github.io/portfolio-profesional/
Text Domain: anderc-smtp-mailer
Domain Path: /languages
Requires at least: 6.0
Requires PHP: 7.4
License: GPL-2.0-or-later
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ANDERC_ASM_VERSION', '1.0.0' );
define( 'ANDERC_ASM_FILE', __FILE__ );
define( 'ANDERC_ASM_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANDERC_ASM_URL', plugin_dir_url( __FILE__ ) );

// Autoloader estilo PSR-4 (compatible con Composer si se añade vendor/).
spl_autoload_register(
	function ( $class ) {
		if ( 0 !== strpos( $class, 'ASM_' ) ) {
			return;
		}
		$file = ANDERC_ASM_DIR . 'includes/class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

if ( file_exists( ANDERC_ASM_DIR . 'vendor/autoload.php' ) ) {
	require_once ANDERC_ASM_DIR . 'vendor/autoload.php';
}

register_activation_hook( __FILE__, array( 'ASM_Email_Logger', 'create_table' ) );

ASM_Plugin::instance();
