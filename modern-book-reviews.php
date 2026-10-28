<?php
/**
 * Modern Book Reviews
 *
 * Moderne, sichere Neuentwicklung als datenkompatibler Ersatz für das
 * eingestellte "Recencio Book Reviews"-Plugin (wzy Media / Kemory Grubb).
 * Übernimmt denselben Custom Post Type (`rcno_review`), dieselben Taxonomien
 * und Meta-Keys, sodass bestehende Rezensionen, URLs und Daten erhalten bleiben.
 *
 * @wordpress-plugin
 * Plugin Name:          Modern Book Reviews
 * Plugin URI:           https://github.com/mapindoor/modern-book-reviews
 * Description:          Buchrezensionen für WordPress – datenkompatibler, moderner und sicherer Ersatz für Recencio Book Reviews. Gutenberg-Sidebar, ISBN-Abruf, JSON-LD-Schema, Sternebewertungen.
 * Version:              1.1.2
 * Requires at least:    6.4
 * Requires PHP:         8.2
 * Author:               mapindoor
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          modern-book-reviews
 * Domain Path:          /languages
 */

defined( 'WPINC' ) || exit;

define( 'MBR_VERSION', '1.1.2' );
define( 'MBR_PLUGIN_FILE', __FILE__ );
define( 'MBR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MBR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*
 * Mindestversionen prüfen. Bei alten Umgebungen sauber abbrechen statt
 * Whitescreen mit Fatal Error.
 */
if ( version_compare( PHP_VERSION, '8.2.0', '<' ) || version_compare( get_bloginfo( 'version' ), '6.4', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Modern Book Reviews benötigt PHP 8.2+ und WordPress 6.4+. Bitte Umgebung aktualisieren.', 'modern-book-reviews' )
			);
		}
	);
	return;
}

require_once MBR_PLUGIN_DIR . 'includes/Autoloader.php';

ModernBookReviews\Autoloader::register();

register_activation_hook(
	__FILE__,
	static function (): void {
		ModernBookReviews\PostTypes::register();
		ModernBookReviews\PostTypes::register_taxonomies();
		ModernBookReviews\Settings::seed_defaults();
		flush_rewrite_rules( false );
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		flush_rewrite_rules( false );
	}
);

/**
 * Einstiegspunkt des Plugins.
 */
function mbr(): ModernBookReviews\Plugin {
	return ModernBookReviews\Plugin::instance();
}

add_action( 'plugins_loaded', 'mbr', 5 );
