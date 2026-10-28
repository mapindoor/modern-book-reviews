<?php
/**
 * Hauptklasse: verdrahtet alle Komponenten mit WordPress-Hooks.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {
		$this->wire_core();
		$this->wire_public();
		$this->wire_admin();
	}

	public static function instance(): Plugin {
		return self::$instance ??= new self();
	}

	/**
	 * Kern: Textdomain, Post-Types, Meta-Registrierung, Version-Upgrade.
	 */
	private function wire_core(): void {
		add_action( 'init', array( PostTypes::class, 'register' ) );
		add_action( 'init', array( PostTypes::class, 'register_taxonomies' ) );
		add_action( 'init', array( Meta::class, 'register' ) );
		add_action( 'init', array( Settings::class, 'init' ) );
		add_action( 'init', array( Shortcodes::class, 'register' ) );
		add_action( 'rest_api_init', array( IsbnLookup::class, 'register_routes' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );
		add_action( 'admin_init', array( SettingsPage::class, 'maybe_reset' ) );
		add_action( 'admin_init', array( SettingsPage::class, 'maybe_import' ) );
		add_action( 'admin_init', array( SettingsPage::class, 'handle_export' ) );
	}

	/**
	 * Öffentliche Seite: Rezensionsbox, Schema.org, Kommentar-Bewertung,
	 * Query-Filter (Homepage, RSS, Archive) und Gutenberg-Umschaltung.
	 */
	private function wire_public(): void {
		QueryFilters::register();
		add_action( 'wp_enqueue_scripts', array( ReviewBox::class, 'register_assets' ) );
		add_filter( 'the_content', array( ReviewBox::class, 'prepend_box' ), 15 );
		add_action( 'wp_head', array( Schema::class, 'print_schema' ), 5 );
		CommentRating::register();
		add_action( 'comment_post', array( CommentRating::class, 'save' ), 10, 3 );
		add_filter( 'comment_text', array( CommentRating::class, 'display' ), 10, 2 );
		Widgets::register();
	}

	/**
	 * Admin: Metaboxen im Original-Layout, Einstellungsseite.
	 */
	private function wire_admin(): void {
		AdminMetaboxes::register();
		Extensions::register();
		add_action( 'admin_menu', array( SettingsPage::class, 'add_menu' ) );
		add_action( 'admin_init', array( SettingsPage::class, 'register_settings' ) );
		add_action( 'update_option_rcno_reviews_settings', array( SettingsPage::class, 'flush_after_save' ), 10, 2 );
		add_filter( 'plugin_action_links_' . MBR_PLUGIN_BASENAME, array( SettingsPage::class, 'action_links' ) );
		AdminColumns::register();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'modern-book-reviews', false, dirname( MBR_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Versions-Migration (z. B. für spätere Updates).
	 */
	public function maybe_upgrade(): void {
		if ( get_option( 'mbr_version' ) === MBR_VERSION ) {
			return;
		}

		Settings::seed_defaults();
		update_option( 'mbr_version', MBR_VERSION, false );
	}
}
