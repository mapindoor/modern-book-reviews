<?php
/**
 * Query-Filter: setzt die Allgemein-Einstellungen des Original-Plugins um.
 *
 * - Rezensionen auf der Homepage (rcno_reviews_on_homepage)
 * - Rezensionen im RSS-Feed (rcno_reviews_in_rss)
 * - Rezensionen in Kategorie-/Schlagwort-/Autor-/Datums-Archiven
 *   (wie im Original fest integriert)
 * - Gutenberg-Unterstützung (rcno_reviews_in_gutenberg)
 * - Taxonomie-Filter-Dropdowns in der Admin-Übersicht
 *   (rcno_{tax}_filter)
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class QueryFilters {

	/**
	 * Verdrahtet alle Query-Filter.
	 */
	public static function register(): void {
		add_action( 'pre_get_posts', array( __CLASS__, 'review_query' ) );
		add_filter( 'request', array( __CLASS__, 'add_to_rss_feed' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'gutenberg_support' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'taxonomy_filters' ) );
		add_filter( 'parse_query', array( __CLASS__, 'apply_taxonomy_filter' ) );
	}

	/**
	 * Fügt den Rezensions-Post-Type zu den öffentlichen Queries hinzu
	 * (Verhalten des Original-Plugins rcno_review_query).
	 */
	public static function review_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Rezensions-Archiv: nur Rezensionen.
		if ( $query->is_post_type_archive( PostTypes::POST_TYPE ) ) {
			$query->set( 'post_type', PostTypes::POST_TYPE );

			return;
		}

		// Homepage, wenn aktiviert.
		if ( Settings::show_on_homepage()
			&& ( $query->is_home() || $query->is_front_page() )
		) {
			self::add_post_type( $query );

			return;
		}

		// Kategorie-, Schlagwort-, Autor- und Datums-Archive (wie Original).
		if ( $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) {
			self::add_post_type( $query );
		}
	}

	/**
	 * Fügt rcno_review zur bestehenden post_type-Liste der Query hinzu.
	 */
	private static function add_post_type( \WP_Query $query ): void {
		$post_type = $query->get( 'post_type' );

		if ( is_array( $post_type ) ) {
			if ( ! in_array( PostTypes::POST_TYPE, $post_type, true ) ) {
				$post_type[] = PostTypes::POST_TYPE;
				$query->set( 'post_type', $post_type );
			}

			return;
		}

		if ( '' === (string) $post_type ) {
			$query->set( 'post_type', array( 'post', PostTypes::POST_TYPE ) );

			return;
		}

		if ( 'attachment' === (string) $post_type || PostTypes::POST_TYPE === (string) $post_type ) {
			return;
		}

		$query->set( 'post_type', array( $post_type, PostTypes::POST_TYPE ) );
	}

	/**
	 * Fügt die Rezensionen dem Haupt-RSS-Feed hinzu
	 * (Legacy-Key rcno_reviews_in_rss, Verhalten des Originals).
	 *
	 * @param mixed $query_vars Request-Variablen.
	 * @return mixed
	 */
	public static function add_to_rss_feed( mixed $query_vars ): mixed {
		if ( ! is_array( $query_vars ) ) {
			return $query_vars;
		}

		if ( ! Settings::in_rss() ) {
			return $query_vars;
		}

		if ( isset( $query_vars['feed'] ) && ! isset( $query_vars['post_type'] ) ) {
			$query_vars['post_type'] = array( 'post', PostTypes::POST_TYPE );
		}

		return $query_vars;
	}

	/**
	 * Gutenberg-Unterstützung für Rezensionen (rcno_reviews_in_gutenberg).
	 *
	 * @param bool   $use_block_editor Ob der Block-Editor genutzt wird.
	 * @param string $post_type       Post-Type.
	 */
	public static function gutenberg_support( bool $use_block_editor, string $post_type ): bool {
		if ( PostTypes::POST_TYPE === $post_type && ! Settings::gutenberg() ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Rendert die Taxonomie-Filter-Dropdowns in der Admin-Übersicht.
	 * Hierarchische Taxonomien rendert WordPress selbst; wir ergänzen
	 * die nicht-hierarchischen (wie das Original-Plugin).
	 */
	public static function taxonomy_filters(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen instanceof \WP_Screen || PostTypes::POST_TYPE !== $screen->post_type ) {
			return;
		}

		foreach ( self::taxonomy_configs() as $taxonomy => $config ) {
			$tax = get_taxonomy( $taxonomy );

			if ( false === $tax || $tax->hierarchical ) {
				continue;
			}

			if ( '1' !== Settings::tax_flag( (string) $config['key'], 'filter', '1' ) ) {
				continue;
			}

			wp_dropdown_categories(
				array(
					'show_option_all' => sprintf(
						/* translators: 1: Beschriftung Plural */
						__( 'Alle %s', 'modern-book-reviews' ),
						$tax->labels->name
					),
					'taxonomy'        => $taxonomy,
					'name'            => 'mbr_tax_filter_' . $taxonomy,
					'value_field'     => 'slug',
					'hide_empty'      => false,
					'selected'        => sanitize_text_field( wp_unslash( (string) ( $_GET[ 'mbr_tax_filter_' . $taxonomy ] ?? '' ) ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nur Anzeige.
					'hierarchical'    => false,
				)
			);
		}
	}

	/**
	 * Wendet das gewählte Filter-Dropdown auf die Admin-Query an.
	 */
	public static function apply_taxonomy_filter( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$post_type = (string) $query->get( 'post_type' );

		if ( '' === $post_type ) {
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( PostTypes::POST_TYPE !== $post_type ) {
			return;
		}

		foreach ( array_keys( self::taxonomy_configs() ) as $taxonomy ) {
			$param = 'mbr_tax_filter_' . $taxonomy;

			if ( ! isset( $_GET[ $param ] ) || '' === (string) $_GET[ $param ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				continue;
			}

			$slug = sanitize_title( wp_unslash( (string) $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( '' === $slug ) {
				continue;
			}

			$query->set( 'tax_query', array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $slug,
				),
			) );
		}
	}

	/**
	 * Alle registrierten Plugin-Taxonomien mit Settings-Key.
	 *
	 * @return array<string, array{key: string}>
	 */
	private static function taxonomy_configs(): array {
		$configs = array(
			PostTypes::TAX_AUTHOR    => array( 'key' => 'author' ),
			PostTypes::TAX_GENRE     => array( 'key' => 'genre' ),
			PostTypes::TAX_SERIES    => array( 'key' => 'series' ),
			PostTypes::TAX_PUBLISHER => array( 'key' => 'publisher' ),
		);

		foreach ( Settings::custom_taxonomies() as $custom ) {
			$taxonomy = 'rcno_' . str_replace( ' ', '-', $custom['name'] );

			if ( taxonomy_exists( $taxonomy ) ) {
				$configs[ $taxonomy ] = array( 'key' => $custom['key'] );
			}
		}

		return $configs;
	}
}
