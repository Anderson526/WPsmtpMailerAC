<?php
// Limpieza al desinstalar: borra ajustes y la tabla de registros.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'anderc_asm_settings' );

$table = $wpdb->prefix . 'anderc_email_logs';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB
