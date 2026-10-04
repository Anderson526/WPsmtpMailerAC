<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Intercepta `wp_mail` y guarda cada correo en la tabla personalizada de registros.
 */
class ASM_Email_Logger {

	const TABLE = 'anderc_email_logs';

	/**
	 * ID del último registro insertado en esta petición, para actualizar su estado.
	 *
	 * @var int
	 */
	private $last_log_id = 0;

	public function __construct() {
		add_filter( 'wp_mail', array( $this, 'log_mail' ), PHP_INT_MAX );
		add_action( 'wp_mail_succeeded', array( $this, 'mark_sent' ) );
		add_action( 'wp_mail_failed', array( $this, 'mark_failed' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	public static function create_table() {
		global $wpdb;

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			sent_at DATETIME NOT NULL,
			to_email TEXT NOT NULL,
			subject TEXT NOT NULL,
			message LONGTEXT NOT NULL,
			headers TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pendiente',
			error TEXT NULL,
			PRIMARY KEY  (id),
			KEY sent_at (sent_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Registra el correo antes de enviarlo y devuelve los argumentos sin modificar.
	 */
	public function log_mail( $args ) {
		global $wpdb;

		$settings = ASM_Plugin::get_settings();
		if ( empty( $settings['log_emails'] ) ) {
			return $args;
		}

		$to      = isset( $args['to'] ) ? $args['to'] : '';
		$to      = is_array( $to ) ? implode( ', ', $to ) : (string) $to;
		$headers = isset( $args['headers'] ) ? $args['headers'] : '';
		$headers = is_array( $headers ) ? implode( "\n", $headers ) : (string) $headers;

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'sent_at'  => current_time( 'mysql' ),
				'to_email' => sanitize_textarea_field( $to ),
				'subject'  => sanitize_text_field( isset( $args['subject'] ) ? (string) $args['subject'] : '' ),
				'message'  => (string) ( isset( $args['message'] ) ? $args['message'] : '' ),
				'headers'  => sanitize_textarea_field( $headers ),
				'status'   => 'pendiente',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$this->last_log_id = $inserted ? (int) $wpdb->insert_id : 0;

		return $args;
	}

	public function mark_sent() {
		$this->update_status( 'enviado', '' );
	}

	/**
	 * @param WP_Error $error Error devuelto por PHPMailer.
	 */
	public function mark_failed( $error ) {
		$message = is_wp_error( $error ) ? $error->get_error_message() : '';
		$this->update_status( 'fallido', $message );
	}

	private function update_status( $status, $error ) {
		global $wpdb;

		if ( ! $this->last_log_id ) {
			return;
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table_name(),
			array(
				'status' => $status,
				'error'  => sanitize_textarea_field( $error ),
			),
			array( 'id' => $this->last_log_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function get_logs( $page = 1, $per_page = 20 ) {
		global $wpdb;

		$table  = self::table_name();
		$offset = max( 0, ( $page - 1 ) * $per_page );

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY sent_at DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$per_page,
				$offset
			)
		);
	}

	public static function count_logs() {
		global $wpdb;
		$table = self::table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB
	}

	public static function clear_logs() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB
	}
}
