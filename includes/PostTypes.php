<?php
/**
 * Registriert Post Type und Taxonomien des Plugins.
 *
 * Die Registrierung ist absichtlich identisch zum alten Recencio-Plugin
 * (gleiche internen Namen `rcno_review` / `rcno_author` / `rcno_genre` /
 * `rcno_series` / `rcno_publisher`, gleiches Rewrite-Verhalten), damit
 * bestehende Permalinks nicht brechen.
 *
 * Alle Taxonomie-Optionen der alten Einstellungsseite werden gelesen:
 * Beschriftungen, Hierarchie, Admin-Spalte, Admin-Filter und die
 * Nutzung mit normalen Beiträgen (`rcno_{tax}_posts`), plus die über
 * `rcno_taxonomy_selection` angelegten zusätzlichen Taxonomien.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class PostTypes {

	public const POST_TYPE     = 'rcno_review';
	public const TAX_AUTHOR    = 'rcno_author';
	public const TAX_GENRE     = 'rcno_genre';
	public const TAX_SERIES    = 'rcno_series';
	public const TAX_PUBLISHER = 'rcno_publisher';

	/**
	 * Registriert den Rezensions-Post-Type.
	 */
	public static function register(): void {
		$singular = (string) self::label( 'singular', 'Review' );
		$plural   = (string) self::label( 'plural', 'Reviews' );

		/*
		 * Exakte Replikation des Original-Verhaltens: Rewrite-Slug wird
		 * aus der Singular-Beschriftung abgeleitet, das Archiv aus der
		 * Plural-Beschriftung. Dadurch bleiben bestehende Permalinks
		 * unverändert erhalten.
		 */
		$rewrite_slug = self::sanitize_string( $singular );
		$archive_slug = self::sanitize_string( $plural );

		$supports = array(
			'title',
			'editor',
			'thumbnail',
			'excerpt',
			'author',
			'comments',
			'revisions',
			// Bewusst OHNE 'custom-fields': Wie im Original wird das
			// Roh-Panel „Individuelle Felder“ im Editor ausgeblendet.
			// Alle Buchdaten werden über die beschrifteten Metaboxen
			// (AdminMetaboxes) gepflegt.
		);

		if ( '1' === (string) Settings::get( 'rcno_enable_builtin_taxonomy' ) ) {
			$supports[] = 'category';
			$supports[] = 'post_tag';
		}

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => self::post_type_labels( $singular, $plural ),
				'public'        => true,
				'show_in_rest'  => Settings::in_rest() || Settings::gutenberg(),
				'rest_base'     => 'rcno/reviews',
				'has_archive'   => '' !== $archive_slug ? $archive_slug : 'reviews',
				'menu_position' => 5,
				'menu_icon'     => 'dashicons-book',
				'supports'      => $supports,
				'taxonomies'    => '1' === (string) Settings::get( 'rcno_enable_builtin_taxonomy' )
					? array( 'category', 'post_tag' )
					: array(),
				'rewrite'       => array(
					'slug'       => '' !== $rewrite_slug ? $rewrite_slug : 'review',
					'with_front' => false,
					'feeds'      => true,
					'pages'      => true,
				),
				'map_meta_cap'  => true,
			)
		);
	}

	/**
	 * Registriert die Buch-Taxonomien (Autor, Genre, Serie, Verlag) sowie
	 * die im Original-Plugin über „Taxonomie-Auswahl" angelegten
	 * zusätzlichen Taxonomien.
	 */
	public static function register_taxonomies(): void {
		$builtin = array(
			self::TAX_AUTHOR    => array(
				'key'            => 'author',
				'singular'       => 'Author',
				'plural'         => 'Authors',
				'slug_key'       => 'rcno_author_slug',
				'slug_default'   => 'author',
				'hier_default'   => '0',
			),
			self::TAX_GENRE     => array(
				'key'            => 'genre',
				'singular'       => 'Genre',
				'plural'         => 'Genres',
				'slug_key'       => 'rcno_genre_slug',
				'slug_default'   => 'genre',
				'hier_default'   => '1',
			),
			self::TAX_SERIES    => array(
				'key'            => 'series',
				'singular'       => 'Series',
				'plural'         => 'Series',
				'slug_key'       => 'rcno_series_slug',
				'slug_default'   => 'series',
				'hier_default'   => '1',
			),
			self::TAX_PUBLISHER => array(
				'key'            => 'publisher',
				'singular'       => 'Publisher',
				'plural'         => 'Publishers',
				'slug_key'       => 'rcno_publisher_slug',
				'slug_default'   => 'publisher',
				'hier_default'   => '0',
			),
		);

		foreach ( $builtin as $taxonomy => $config ) {
			self::register_taxonomy( $taxonomy, $config );
		}

		// Zusätzliche Taxonomien aus der Legacy-Auswahl (rcno_taxonomy_selection).
		$archive_slug = self::sanitize_string( (string) Settings::tax_labels( 'review', 'Review', 'Reviews' )['plural'] );

		foreach ( Settings::custom_taxonomies() as $custom ) {
			$taxonomy = 'rcno_' . str_replace( ' ', '-', $custom['name'] );

			if ( taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$labels = Settings::tax_labels(
			$custom['key'],
			Settings::singularize( $custom['name'] ),
			Settings::pluralize( $custom['name'] )
		);

			self::register_taxonomy(
				$taxonomy,
				array(
					'key'          => $custom['key'],
					'singular'     => $labels['singular'],
					'plural'       => $labels['plural'],
					/*
					 * Rewrite-Slug des Originals: Archiv-Slug des Post
					 * Types + Plural-Beschriftung (z. B. reviews/bewertungen),
					 * damit bestehende URLs erhalten bleiben.
					 */
					'slug'         => '' !== $archive_slug
						? $archive_slug . '/' . Settings::sanitize_taxonomy_key( $labels['plural'] )
						: Settings::sanitize_taxonomy_key( $labels['plural'] ),
					'slug_key'     => null,
					'slug_default' => '',
					'hier_default' => '0',
				)
			);
		}
	}

	/**
	 * Registriert eine einzelne Taxonomie mit allen Legacy-Optionen.
	 *
	 * @param string                                     $taxonomy Interner Name.
	 * @param array{key: string, singular: string, plural: string, slug?: string, slug_key?: string|null, slug_default?: string, hier_default?: string} $config Konfiguration.
	 */
	private static function register_taxonomy( string $taxonomy, array $config ): void {
		$key      = (string) $config['key'];
		$labels   = Settings::tax_labels( $key, (string) $config['singular'], (string) $config['plural'] );
		$singular = '' !== $labels['singular'] ? $labels['singular'] : (string) $config['singular'];
		$plural   = '' !== $labels['plural'] ? $labels['plural'] : (string) $config['plural'];

		// Hierarchie wie im Original aus den Einstellungen ('' = aus).
		$hierarchical = '1' === Settings::tax_flag( $key, 'hierarchical', (string) ( $config['hier_default'] ?? '0' ) );

		// Admin-Spalte („In Tabellen anzeigen") wie im Original.
		$show_column = '1' === Settings::tax_flag( $key, 'show', '1' );

		// „Mit Beiträgen nutzen": Taxonomie auch an normale Posts hängen.
		$object_types = array( self::POST_TYPE );

		if ( '1' === Settings::tax_flag( $key, 'posts', '0' ) ) {
			$object_types[] = 'post';
		}

		$slug = isset( $config['slug'] )
			? (string) $config['slug']
			: self::slug( (string) $config['slug_key'], (string) $config['slug_default'] );

		register_taxonomy(
			$taxonomy,
			$object_types,
			array(
				'labels'            => self::taxonomy_labels( $singular, $plural ),
				'hierarchical'      => $hierarchical,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => $show_column,
				'show_in_menu'      => true,
				'show_tagcloud'     => ! $hierarchical,
				'rewrite'           => array(
					'slug'       => '' !== $slug ? $slug : $key,
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Liest die Beschriftung des Post Types aus den Einstellungen.
	 */
	private static function label( string $which, string $fallback ): string {
		$labels = Settings::get( 'rcno_review_labels', array() );

		if ( is_array( $labels ) && ! empty( $labels[ $which ] ) ) {
			return sanitize_text_field( (string) $labels[ $which ] );
		}

		return $fallback;
	}

	/**
	 * Liest und bereinigt einen Rewrite-Slug.
	 */
	private static function slug( string $key, string $fallback ): string {
		$slug = sanitize_key( (string) Settings::get( $key, $fallback ) );

		return '' !== $slug ? $slug : $fallback;
	}

	/**
	 * Repliziert die Slug-Ableitung des Original-Plugins (sanitize_string):
	 * Kleinschreibung, Leerzeichen zu Bindestrichen, Rand-Bindestriche weg.
	 * Wird für die URL-Struktur des Post Types verwendet, damit bestehende
	 * Permalinks nicht brechen.
	 */
	private static function sanitize_string( string $input ): string {
		$input = strtolower( trim( strip_tags( $input ) ) );
		$input = str_replace( '%', '', $input );
		$input = preg_replace( '/\s+/', '-', $input ) ?? '';

		return trim( $input, '-' );
	}

	/**
	 * @return array<string, string>
	 */
	private static function post_type_labels( string $singular, string $plural ): array {
		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'add_new'            => sprintf( /* translators: 1: Beschriftung Singular */ __( 'Neu: %s', 'modern-book-reviews' ), $singular ),
			'add_new_item'       => sprintf( /* translators: 1: Beschriftung Singular */ __( '%s erstellen', 'modern-book-reviews' ), $singular ),
			'edit_item'          => sprintf( /* translators: 1: Beschriftung Singular */ __( '%s bearbeiten', 'modern-book-reviews' ), $singular ),
			'new_item'           => sprintf( /* translators: 1: Beschriftung Singular */ __( 'Neuer %s', 'modern-book-reviews' ), $singular ),
			'view_item'          => sprintf( /* translators: 1: Beschriftung Singular */ __( '%s ansehen', 'modern-book-reviews' ), $singular ),
			'view_items'         => sprintf( /* translators: 1: Beschriftung Plural */ __( '%s ansehen', 'modern-book-reviews' ), $plural ),
			'search_items'       => sprintf( /* translators: 1: Beschriftung Plural */ __( '%s durchsuchen', 'modern-book-reviews' ), $plural ),
			'not_found'          => sprintf( /* translators: 1: Beschriftung Plural */ __( 'Keine %s gefunden', 'modern-book-reviews' ), $plural ),
			'not_found_in_trash' => sprintf( /* translators: 1: Beschriftung Plural */ __( 'Keine %s im Papierkorb gefunden', 'modern-book-reviews' ), $plural ),
			'all_items'          => sprintf( /* translators: 1: Beschriftung Plural */ __( 'Alle %s', 'modern-book-reviews' ), $plural ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function taxonomy_labels( string $singular, string $plural ): array {
		return array(
			'name'              => $plural,
			'singular_name'     => $singular,
			'search_items'      => sprintf( /* translators: 1: Beschriftung Plural */ __( '%s durchsuchen', 'modern-book-reviews' ), $plural ),
			'all_items'         => sprintf( /* translators: 1: Beschriftung Plural */ __( 'Alle %s', 'modern-book-reviews' ), $plural ),
			'parent_item'       => sprintf( /* translators: 1: Beschriftung Singular */ __( 'Übergeordnete(r) %s', 'modern-book-reviews' ), $singular ),
			'parent_item_colon' => sprintf( /* translators: 1: Beschriftung Singular */ __( 'Übergeordnete(r) %s:', 'modern-book-reviews' ), $singular ),
			'edit_item'         => sprintf( /* translators: 1: Beschriftung Singular */ __( '%s bearbeiten', 'modern-book-reviews' ), $singular ),
			'update_item'       => sprintf( /* translators: 1: Beschriftung Singular */ __( '%s aktualisieren', 'modern-book-reviews' ), $singular ),
			'add_new_item'      => sprintf( /* translators: 1: Beschriftung Singular */ __( 'Neue(r) %s anlegen', 'modern-book-reviews' ), $singular ),
			'new_item_name'     => sprintf( /* translators: 1: Beschriftung Singular */ __( 'Name der/des neuen %s', 'modern-book-reviews' ), $singular ),
			'menu_name'         => $plural,
		);
	}
}
