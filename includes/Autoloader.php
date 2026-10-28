<?php
/**
 * Leichter PSR-4-Autoloader (Namespace "ModernBookReviews" -> includes/).
 *
 * Bewusst ohne Composer-Abhängigkeit, damit das Plugin ohne Build-Schritt
 * direkt aus dem ZIP heraus funktioniert.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class Autoloader {

	private const NAMESPACE_PREFIX = 'ModernBookReviews\\';
	private const BASE_DIR        = MBR_PLUGIN_DIR . 'includes/';

	/**
	 * Registriert den Autoloader bei SPL.
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Lädt eine Klasse des Plugin-Namespaces.
	 */
	public static function autoload( string $class_name ): void {
		if ( ! str_starts_with( $class_name, self::NAMESPACE_PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::NAMESPACE_PREFIX ) );
		$file      = self::BASE_DIR . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
}
