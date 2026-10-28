<?php
/**
 * Admin-Spalten für die Rezensions-Übersicht.
 *
 * Ergänzt die Listing-Tabelle um die Cover-Spalte (wie im Original-Plugin)
 * mit kleinem Vorschaubild. Ausgabe vollständig escaped.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class AdminColumns {

	/**
	 * Registriert die Spalten-Hooks und das Admin-Stylesheet.
	 */
	public static function register(): void {
		add_filter( 'manage_' . PostTypes::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_' . PostTypes::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_cover_column' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_style' ) );
	}

	/**
	 * Lädt das Stylesheet nur in der Rezensions-Übersicht.
	 */
	public static function enqueue_style( string $hook_suffix ): void {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen instanceof \WP_Screen || PostTypes::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'mbr-admin-columns',
			MBR_PLUGIN_URL . 'assets/admin/admin.css',
			array(),
			MBR_VERSION
		);
	}

	/**
	 * Fügt die Cover-Spalte nach der Checkbox ein und passt die Spalten
	 * an das Verhalten des Original-Plugins an:
	 * - Die WordPress-„Autor"-Spalte wird entfernt (das Original zeigte
	 *   sie nicht; Buchautoren leben in der rcno_author-Taxonomie).
	 * - Taxonomie-Spalten richten sich nach den Legacy-Einstellungen
	 *   (`rcno_author_show`, `rcno_genre_show` …), sodass die Übersicht
	 *   einer bestehenden Installation identisch bleibt.
	 *
	 * @param array<string, string> $columns Bestehende Spalten.
	 * @return array<string, string>
	 */
	public static function add_columns( array $columns ): array {
		unset( $columns['author'] );

		$taxonomies = array(
			PostTypes::TAX_AUTHOR    => 'rcno_author_show',
			PostTypes::TAX_GENRE     => 'rcno_genre_show',
			PostTypes::TAX_SERIES    => 'rcno_series_show',
			PostTypes::TAX_PUBLISHER => 'rcno_publisher_show',
		);

		foreach ( $taxonomies as $taxonomy => $option_key ) {
			if ( '1' !== (string) Settings::get( $option_key, '1' ) ) {
				unset( $columns[ 'taxonomy_' . $taxonomy ] );
			}
		}

		$cover = array( 'mbr_cover' => __( 'Cover', 'modern-book-reviews' ) );

		// Cover direkt hinter der Checkbox/Sperrspalte platzieren.
		$rebuild = array();

		foreach ( $columns as $key => $label ) {
			$rebuild[ $key ] = $label;

			if ( 'cb' === $key ) {
				$rebuild['mbr_cover'] = $cover['mbr_cover'];
			}
		}

		// Fallback, falls die cb-Spalte fehlt.
		if ( ! isset( $rebuild['mbr_cover'] ) ) {
			$rebuild = $cover + $rebuild;
		}

		return $rebuild;
	}

	/**
	 * Rendert das Cover-Vorschaubild in der Spalte.
	 */
	public static function render_cover_column( string $column_name, int $post_id ): void {
		if ( 'mbr_cover' !== $column_name ) {
			return;
		}

		$attachment_id = (int) get_post_thumbnail_id( $post_id );

		if ( $attachment_id < 1 ) {
			$attachment_id = (int) get_post_meta( $post_id, 'rcno_reviews_book_cover_id', true );
		}

		if ( $attachment_id > 0 ) {
			$thumb = wp_get_attachment_image(
				$attachment_id,
				array( 48, 72 ),
				false,
				array( 'class' => 'mbr-cover-column' )
			);

			if ( is_string( $thumb ) && '' !== $thumb ) {
				echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput -- WP-Kernfunktion.
				return;
			}
		}

		$url = Meta::get_string( $post_id, 'rcno_reviews_book_cover_src' );

		if ( '' === $url ) {
			$url = Meta::get_string( $post_id, 'rcno_reviews_book_cover_url' );
		}

		if ( '' === $url ) {
			return;
		}

		printf(
			'<img class="mbr-cover-column" src="%s" alt="" width="48" height="72" loading="lazy" />',
			esc_url( $url )
		);
	}
}
