<?php
/**
 * Einstellungsseite (unter Einstellungen → Buchrezensionen).
 *
 * Nutzt die Settings API inkl. register_setting mit vollständigem
 * Sanitize-Callback (siehe Settings::sanitize) und Nonce-Schutz
 * (settings_fields). Capability: manage_options.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class SettingsPage {

	public const CAPABILITY  = 'manage_options';
	public const MENU_SLUG   = 'mbr-settings';
	public const PAGE_TITLE  = 'Buchrezensionen';
	public const SETTINGS_GROUP = 'mbr_settings_group';

	/**
	 * Registriert das Admin-Menü.
	 */
	public static function add_menu(): void {
		// Einstellungsseite als Untermenü des „Reviews“-Menüs, damit
		// alle Plugin-Funktionen an einem Ort erreichbar sind.
		add_submenu_page(
			'edit.php?post_type=' . PostTypes::POST_TYPE,
			__( 'Buchrezensionen – Einstellungen', 'modern-book-reviews' ),
			__( 'Einstellungen', 'modern-book-reviews' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * URL der Einstellungsseite (unter dem Reviews-Menü).
	 */
	public static function settings_url(): string {
		return admin_url( 'edit.php?post_type=' . PostTypes::POST_TYPE . '&page=' . self::MENU_SLUG );
	}

	/**
	 * Registriert die Einstellung über die Settings API.
	 */
	public static function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			Settings::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * „Einstellungen"-Link in der Aktionsleiste des Plugin-Eintrags
	 * (plugins.php), wie ihn das Original-Plugin bot.
	 *
	 * @param array<int, string> $links Bestehende Aktionslinks.
	 * @return array<int, string>
	 */
	public static function action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( self::settings_url() ),
			esc_attr__( 'Einstellungen für Modern Book Reviews', 'modern-book-reviews' ),
			esc_html__( 'Einstellungen', 'modern-book-reviews' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Setzt die Einstellungen auf die Standardwerte zurück
	 * („Reset Settings“ des Original-Plugins). Der Legacy-Options-Key
	 * wird geleert und mit den Defaults neu befüllt; Permalinks werden
	 * anschließend erneuert.
	 */
	public static function maybe_reset(): void {
		if ( ! isset( $_POST['mbr_reset_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$nonce = isset( $_POST['_mbr_reset_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['_mbr_reset_nonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'mbr_reset_settings' ) ) {
			return;
		}

		delete_option( Settings::OPTION_KEY );
		Settings::seed_defaults();
		flush_rewrite_rules( false );

		add_settings_error(
			'mbr-settings',
			'mbr-reset',
			__( 'Alle Einstellungen wurden auf die Standardwerte zurückgesetzt.', 'modern-book-reviews' ),
			'success'
		);
	}

	/**
	 * Export („Einstellungen sichern“ des Original-Plugins): lädt die
	 * Plugin-Einstellungen als JSON-Datei herunter.
	 * Aufruf per GET mit Nonce aus der Einstellungsseite.
	 */
	public static function handle_export(): void {
		if ( ! isset( $_GET['mbr_export'] ) || '1' !== (string) $_GET['mbr_export'] ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'mbr_export_settings' ) ) {
			return;
		}

		$options = get_option( Settings::OPTION_KEY, array() );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="modern-book-reviews-settings.json"' );

		echo wp_json_encode( is_array( $options ) ? $options : array() );
		exit;
	}

	/**
	 * Import („Einstellungen importieren“ des Original-Plugins):
	 * liest eine zuvor exportierte JSON-Datei ein und führt die Werte
	 * nach derselben Sanitize-Logik wie beim normalen Speichern zusammen.
	 */
	public static function maybe_import(): void {
		if ( ! isset( $_POST['mbr_import_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$nonce = isset( $_POST['_mbr_import_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['_mbr_import_nonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'mbr_import_settings' ) ) {
			return;
		}

		$redirect = self::settings_url();

		if ( ! isset( $_FILES['mbr_settings_file'] )
			|| ! is_array( $_FILES['mbr_settings_file'] )
			|| (int) ( $_FILES['mbr_settings_file']['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK
		) {
			wp_safe_redirect( add_query_arg( 'mbr-imported', 'error', $redirect ) );
			exit;
		}

		$file   = $_FILES['mbr_settings_file'];
		$source = isset( $file['tmp_name'] ) && is_uploaded_file( (string) $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';

		if ( '' === $source || (int) ( $file['size'] ?? 0 ) > 1024 * 1024 ) {
			wp_safe_redirect( add_query_arg( 'mbr-imported', 'error', $redirect ) );
			exit;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Upload-Temp-Datei.
		$raw    = (string) file_get_contents( $source );
		$import = json_decode( $raw, true );

		if ( ! is_array( $import ) ) {
			wp_safe_redirect( add_query_arg( 'mbr-imported', 'error', $redirect ) );
			exit;
		}

		update_option( Settings::OPTION_KEY, Settings::sanitize( $import ), false );
		flush_rewrite_rules( false );

		wp_safe_redirect( add_query_arg( 'mbr-imported', '1', $redirect ) );
		exit;
	}

	/**
	 * Nach dem Speichern der Slugs die Rewrite-Regeln erneuern.
	 *
	 * @param mixed $old_value Alter Wert.
	 * @param mixed $new_value Neuer Wert.
	 */
	public static function flush_after_save( mixed $old_value, mixed $new_value ): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		flush_rewrite_rules( false );
	}

	/**
	 * Rendert die Einstellungsseite.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Seite.', 'modern-book-reviews' ) );
		}

		// Ergebnis-Hinweis nach dem Import (Weiterleitung nach maybe_import()).
		if ( isset( $_GET['mbr-imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reiner Anzeige-Hinweis.
			if ( '1' === (string) $_GET['mbr-imported'] ) {
				add_settings_error(
					'mbr-settings',
					'mbr-imported',
					__( 'Einstellungen wurden erfolgreich importiert.', 'modern-book-reviews' ),
					'success'
				);
			} else {
				add_settings_error(
					'mbr-settings',
					'mbr-import-error',
					__( 'Import fehlgeschlagen: Bitte eine gültige, zuvor exportierte JSON-Datei wählen.', 'modern-book-reviews' ),
					'error'
				);
			}
		}

		require MBR_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
