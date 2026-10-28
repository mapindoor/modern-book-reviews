<?php
/**
 * Einstellungs-Modell.
 *
 * Das Plugin nutzt bewusst denselben Options-Schlüssel und dieselben
 * Schlüsselnamen wie das alte Recencio-Plugin (`rcno_reviews_settings`
 * mit `rcno_*`-Keys), damit bestehende Konfiguration beim Umstieg
 * vollständig erhalten bleibt und jede Einstellung der alten
 * Einstellungsseite weiter greift.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class Settings {

	public const OPTION_KEY = 'rcno_reviews_settings';

	/**
	 * Die vier fest eingebauten Taxonomien (wie im Original-Plugin).
	 */
	public const BUILTIN_TAXONOMIES = array( 'author', 'genre', 'series', 'publisher' );

	/**
	 * Vorbelegung der Option, falls noch keine existiert.
	 * Bestehende Werte werden niemals überschrieben.
	 */
	public static function seed_defaults(): void {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			update_option( self::OPTION_KEY, self::defaults(), false );
		}
	}

	/**
	 * Init-Hook: Sanitizing wird über SettingsPage registriert.
	 */
	public static function init(): void {
		// Derzeit keine init-Logik nötig; Option wird lazy gelesen.
	}

	/**
	 * Standardwerte. Schlüssel sind identisch mit Recencio (rcno_*),
	 * um eine Übernahme bestehender Einstellungen zu ermöglichen.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			// Post Type.
			'rcno_review_labels' => array(
				'singular' => 'Review',
				'plural'   => 'Reviews',
			),
			'rcno_review_slug'   => 'review',

			// Allgemein (Legacy-Keys des Original-Plugins).
			'rcno_reviews_on_homepage' => '1',
			'rcno_reviews_in_rss'      => '1',
			'rcno_reviews_in_rest'     => '1',
			'rcno_reviews_archive'     => 'archive_display_full',
			'rcno_reviews_in_gutenberg' => '1',

			// Taxonomien.
			'rcno_taxonomy_selection'      => '',
			'rcno_enable_builtin_taxonomy' => '0',
			'rcno_author_slug'             => 'author',
			'rcno_genre_slug'              => 'genre',
			'rcno_series_slug'             => 'series',
			'rcno_publisher_slug'          => 'publisher',
			'rcno_author_labels'            => array(
				'singular' => 'Author',
				'plural'   => 'Authors',
			),
			'rcno_genre_labels'            => array(
				'singular' => 'Genre',
				'plural'   => 'Genres',
			),
			'rcno_series_labels'           => array(
				'singular' => 'Series',
				'plural'   => 'Series',
			),
			'rcno_publisher_labels'        => array(
				'singular' => 'Publisher',
				'plural'   => 'Publishers',
			),
			'rcno_author_hierarchical'     => '0',
			'rcno_genre_hierarchical'      => '1',
			'rcno_series_hierarchical'     => '1',
			'rcno_publisher_hierarchical'  => '0',
			'rcno_author_show'             => '1',
			'rcno_genre_show'              => '1',
			'rcno_series_show'             => '1',
			'rcno_publisher_show'          => '1',
			'rcno_author_filter'           => '1',
			'rcno_genre_filter'            => '1',
			'rcno_series_filter'           => '1',
			'rcno_publisher_filter'       => '1',
			'rcno_author_posts'            => '0',
			'rcno_genre_posts'             => '0',
			'rcno_series_posts'            => '0',
			'rcno_publisher_posts'        => '0',

			// Anzeige (Migrations-Keys des Neubaus).
			'mbr_show_review_box'      => '1',
			'mbr_review_box_position'  => 'top',
			'mbr_star_color'           => '#f5a623',
			'mbr_enable_purchase_links' => '1',
			'mbr_purchase_links_label'  => 'Kaufen bei:',
			'mbr_enable_score_box'      => '1',
			'mbr_comment_rating_label' => 'Bewerte dieses Buch',
			'mbr_comment_rating_color' => '#f5a623',

			// Komponenten (Legacy-Keys des Original-Plugins).
			'rcno_enable_star_rating_box'      => '1',
			'rcno_star_rating_color'           => '#f5a623',
			'rcno_enable_purchase_links'       => '1',
			'rcno_store_purchase_links_label'  => 'Kaufen bei:',
			'rcno_show_review_score_box'       => '1',
			'rcno_excerpt_read_more'           => 'Weiterlesen',
			'rcno_excerpt_word_count'          => '55',

			// Kommentar-Bewertung (Legacy-Keys).
			'rcno_enable_comment_ratings'   => '1',
			'rcno_comment_rating_label'     => 'Bewerte dieses Buch',
			'rcno_comment_rating_star_color' => '#f5a623',

			// Buchfeld-Komponenten (Legacy-Keys des Original-Plugins).
			'rcno_show_isbn'                        => '1',
			'rcno_show_isbn13'                      => '1',
			'rcno_show_asin'                        => '1',
			'rcno_show_gr_id'                       => '1',
			'rcno_show_gr_url'                      => '1',
			'rcno_show_illustrator'                 => '1',
			'rcno_show_pub_date'                    => '1',
			'rcno_show_pub_format'                  => '1',
			'rcno_show_pub_edition'                 => '1',
			'rcno_show_series_number'               => '1',
			'rcno_show_page_count'                  => '1',
			'rcno_show_gr_rating'                   => '1',
			'rcno_show_book_cover_url'              => '1',

			// Kauf-Links (Legacy).
			'rcno_store_purchase_links'             => 'Amazon,Barnes & Noble,Kobo',
			'rcno_store_purchase_link_text_color'   => '#171717',
			'rcno_store_purchase_link_background'   => '#ededed',

			// Sterne-/Score-Box-Farben (Legacy).
			'rcno_star_background_color'            => '',
			'rcno_show_review_score_box_background' => '#ffffff',
			'rcno_show_review_score_box_accent'     => '#212121',
			'rcno_show_review_score_box_accent_2'   => '#f5a623',

			// Feste Bewertungskriterien (Legacy).
			'rcno_enable_custom_review_score_criteria' => '0',
			'rcno_custom_review_score_criteria'        => 'Plot,Characters,World',

			// Widgets (Legacy; „Currently Reading" und „Kalender" werden
			// vom Neubau nicht angeboten, gespeicherte Werte bleiben erhalten).
			'rcno_show_book_slider_widget'           => '1',
			'rcno_show_book_grid_widget'             => '1',
			'rcno_show_recent_reviews_widget'        => '1',
			'rcno_show_tag_cloud_widget'             => '1',
			'rcno_show_taxonomy_list_widget'         => '1',
			'rcno_show_currently_reading_widget'     => '0',
			'rcno_show_review_calendar_widget'       => '0',

			// Index-Seite (Legacy).
			'rcno_reviews_index_headers'             => '1',
			'rcno_show_book_covers_index'            => '1',
			'rcno_reviews_ignore_articles'           => '0',
			'rcno_reviews_ignored_articles_list'    => 'The,A,An',
			'rcno_reviews_sort_names'                => 'first_name_last_name',

			// Vorlagen (Legacy).
			'rcno_review_template'                   => 'default',
			'rcno_default_cover'                     => '',
			'rcno_reviews_embedded_title_links'      => '1',
			'rcno_book_details_meta'                 => '',
			'rcno_custom_styling'                    => '',

			// Erweitert (Legacy-Keys).
			'rcno_disable_review_schema' => '0',
			'rcno_disable_book_schema'   => '0',

			// Erweitert: Buch-APIs (Legacy-Keys).
			'rcno_external_book_api'    => 'no-3rd-party',
			'rcno_enable_googlebooks'   => '0',
			'rcno_googlebooks_key'      => '',
			'rcno_enable_goodreads'     => '0',
			'rcno_goodreads_key'        => '',
			'rcno_goodreads_secret'     => '',

			// Erweitert: Unzählbare Wörter / Pluralisierung (Legacy-Keys).
			'rcno_no_pluralization'     => '',
			'rcno_disable_pluralization' => '0',
		);
	}

	/**
	 * Liest einen einzelnen Einstellungswert mit Fallback auf den Standard.
	 */
	public static function get( string $key, mixed $fallback = null ): mixed {
		$options = get_option( self::OPTION_KEY, array() );

		if ( is_array( $options ) && array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}

		$defaults = self::defaults();

		return $defaults[ $key ] ?? $fallback;
	}

	/**
	 * Roher Optionswert oder null, wenn der Key nicht gespeichert ist.
	 */
	public static function raw( string $key ): mixed {
		$options = get_option( self::OPTION_KEY, array() );

		if ( is_array( $options ) && array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}

		return null;
	}

	/**
	 * Effektiver Wert einer Einstellung mit Legacy-Key: Der Recencio-Key
	 * gewinnt; fehlt er komplett (frische Installation des Neubaus),
	 * fällt das Plugin auf den Migrations-Key (mbr_*) zurück.
	 */
	public static function effective( string $legacy_key, string $mbr_key, string $default ): string {
		$legacy = self::raw( $legacy_key );

		if ( null !== $legacy ) {
			return (string) $legacy;
		}

		$mbr = self::raw( $mbr_key );

		if ( null !== $mbr && '' !== (string) $mbr ) {
			return (string) $mbr;
		}

		$defaults = self::defaults();

		return (string) ( $defaults[ $legacy_key ] ?? $default );
	}

	/**
	 * Rezensionen auf der Homepage anzeigen? (Legacy-Key, '' = aus.)
	 */
	public static function show_on_homepage(): bool {
		$value = self::raw( 'rcno_reviews_on_homepage' );

		if ( null === $value ) {
			return true;
		}

		return '1' === (string) $value;
	}

	/**
	 * Rezensionen im RSS-Feed? (Legacy-Key, '' = aus.)
	 */
	public static function in_rss(): bool {
		$value = self::raw( 'rcno_reviews_in_rss' );

		if ( null === $value ) {
			return true;
		}

		return '1' === (string) $value;
	}

	/**
	 * Rezensionen in der REST-API? Das Original-Plugin hatte die
	 * REST-Unterstützung fest aktiviert; ein leerer Legacy-Wert
	 * bedeutet daher „ein“, erst ein explizites '0' schaltet aus.
	 */
	public static function in_rest(): bool {
		$value = self::raw( 'rcno_reviews_in_rest' );

		if ( null === $value || '' === (string) $value ) {
			return true;
		}

		return '1' === (string) $value;
	}

	/**
	 * Gutenberg-Unterstützung? Analog zur REST-API: leerer Legacy-Wert
	 * gilt als „ein“ (das Original lud den Editor unabhängig davon),
	 * erst ein explizites '0' erzwingt den Klassik-Editor.
	 */
	public static function gutenberg(): bool {
		$value = self::raw( 'rcno_reviews_in_gutenberg' );

		if ( null === $value || '' === (string) $value ) {
			return true;
		}

		return '1' === (string) $value;
	}

	/**
	 * Archiv-Darstellung: ganzer Text oder Auszug.
	 */
	public static function archive_mode(): string {
		$value = (string) self::raw( 'rcno_reviews_archive' );

		return 'archive_display_excerpt' === $value ? 'archive_display_excerpt' : 'archive_display_full';
	}

	/**
	 * Taxonomie-Flag (Legacy-Schlüssel rcno_{key}_{suffix}).
	 * Fehlender Key nutzt den Standard, '' bedeutet deaktiviert.
	 */
	public static function tax_flag( string $tax_key, string $suffix, string $default ): string {
		$value = self::raw( 'rcno_' . $tax_key . '_' . $suffix );

		if ( null === $value ) {
			return $default;
		}

		return '1' === (string) $value ? '1' : '0';
	}

	/**
	 * Beschriftungen einer Taxonomie (Legacy-Array singular/plural).
	 *
	 * @return array{singular: string, plural: string}
	 */
	public static function tax_labels( string $tax_key, string $default_singular, string $default_plural ): array {
		$labels = self::get( 'rcno_' . $tax_key . '_labels', array() );

		$singular = is_array( $labels ) && ! empty( $labels['singular'] )
			? sanitize_text_field( (string) $labels['singular'] )
			: $default_singular;
		$plural = is_array( $labels ) && ! empty( $labels['plural'] )
			? sanitize_text_field( (string) $labels['plural'] )
			: $default_plural;

		return array(
			'singular' => $singular,
			'plural'   => $plural,
		);
	}

	/**
	 * Zusätzliche (vom Nutzer im Original-Plugin angelegte) Taxonomien
	 * aus dem Legacy-Key `rcno_taxonomy_selection` (Kommaseparierte Liste).
	 *
	 * @return array<int, array{key: string, name: string}>
	 */
	public static function custom_taxonomies(): array {
		$selection = wp_strip_all_tags( (string) self::get( 'rcno_taxonomy_selection', '' ) );
		$result    = array();

		if ( '' === trim( $selection ) ) {
			return $result;
		}

		foreach ( explode( ',', $selection ) as $entry ) {
			$entry = trim( $entry );

			if ( '' === $entry ) {
				continue;
			}

			$key = self::sanitize_taxonomy_key( $entry );

			if ( '' === $key || in_array( $key, self::BUILTIN_TAXONOMIES, true ) ) {
				continue;
			}

			$result[ $key ] = array(
				'key'  => $key,
				'name' => $entry,
			);
		}

		return $result;
	}

	/**
	 * Slug-Ableitung wie im Original (sanitize_string): Kleinschreibung,
	 * Prozentzeichen entfernen, Leerzeichen zu Bindestrichen.
	 */
	public static function sanitize_taxonomy_key( string $input ): string {
		$input = function_exists( 'mb_strtolower' ) ? mb_strtolower( $input, 'UTF-8' ) : strtolower( $input );
		$input = str_replace( '%', '', $input );
		$input = strtolower( $input );
		$input = preg_replace( '/\s+/', '-', $input ) ?? '';
		$input = str_replace( ',', '', $input );

		return trim( $input, '-' );
	}

	/**
	 * Bereinigt ein Settings-Array vollständig (Settings-API-Sanitize).
	 *
	 * Wichtig: Es wird mit der bestehenden Option GEMERGT. Das Original-
	 * Plugin speicherte deutlich mehr Keys in derselben Option (z. B.
	 * `rcno_book_details_meta`, API-Schlüssel, Widget-Flags); diese
	 * dürfen beim Speichern der Einstellungsseite nicht verloren gehen.
	 *
	 * @param mixed $input Roher Formular-Input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( mixed $input ): array {
		$existing = get_option( self::OPTION_KEY, array() );
		$clean    = is_array( $existing ) ? $existing : self::defaults();

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		$defaults = self::defaults();

		// Beschriftungen des Post Types.
		$labels = is_array( $input['rcno_review_labels'] ?? null ) ? $input['rcno_review_labels'] : array();

		$clean['rcno_review_labels'] = array(
			'singular' => sanitize_text_field( wp_unslash( (string) ( $labels['singular'] ?? 'Review' ) ) ),
			'plural'   => sanitize_text_field( wp_unslash( (string) ( $labels['plural'] ?? 'Reviews' ) ) ),
		);

		// Slugs: nur a-z0-9 und Bindestriche.
		$slug_keys = array(
			'rcno_review_slug',
			'rcno_author_slug',
			'rcno_genre_slug',
			'rcno_series_slug',
			'rcno_publisher_slug',
		);

		foreach ( $slug_keys as $slug_key ) {
			$slug = sanitize_key( wp_unslash( (string) ( $input[ $slug_key ] ?? ( $clean[ $slug_key ] ?? $defaults[ $slug_key ] ) ) ) );

			$clean[ $slug_key ] = '' !== $slug ? $slug : (string) ( $clean[ $slug_key ] ?? $defaults[ $slug_key ] );
		}

		// Allgemein.
		$clean['rcno_enable_builtin_taxonomy'] = self::checkbox( $input, 'rcno_enable_builtin_taxonomy' );
		$clean['rcno_reviews_on_homepage']     = self::checkbox( $input, 'rcno_reviews_on_homepage' );
		$clean['rcno_reviews_in_rss']          = self::checkbox( $input, 'rcno_reviews_in_rss' );
		$clean['rcno_reviews_in_rest']          = self::checkbox( $input, 'rcno_reviews_in_rest' );
		$clean['rcno_reviews_in_gutenberg']     = self::checkbox( $input, 'rcno_reviews_in_gutenberg' );
		$clean['rcno_reviews_archive']          = in_array(
			(string) ( $input['rcno_reviews_archive'] ?? '' ),
			array( 'archive_display_full', 'archive_display_excerpt' ),
			true
		) ? sanitize_key( $input['rcno_reviews_archive'] ) : 'archive_display_full';

		// Zusätzliche Taxonomien (Kommaseparierte Liste wie im Original).
		$clean['rcno_taxonomy_selection'] = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_taxonomy_selection'] ?? ( $clean['rcno_taxonomy_selection'] ?? '' ) ) )
		);

		// Optionen pro Taxonomie: eingebaut + zusätzliche.
		$tax_keys = self::BUILTIN_TAXONOMIES;

		foreach ( array_keys( self::custom_taxonomies_from( (string) $clean['rcno_taxonomy_selection'] ) ) as $custom_key ) {
			$tax_keys[] = $custom_key;
		}

		foreach ( $tax_keys as $tax_key ) {
			$tax_labels = is_array( $input[ 'rcno_' . $tax_key . '_labels' ] ?? null )
				? $input[ 'rcno_' . $tax_key . '_labels' ]
				: array();

			$clean[ 'rcno_' . $tax_key . '_labels' ] = array(
				'singular' => sanitize_text_field( wp_unslash( (string) ( $tax_labels['singular'] ?? '' ) ) ),
				'plural'   => sanitize_text_field( wp_unslash( (string) ( $tax_labels['plural'] ?? '' ) ) ),
			);

			$clean[ 'rcno_' . $tax_key . '_hierarchical' ] = self::checkbox( $input, 'rcno_' . $tax_key . '_hierarchical' );
			$clean[ 'rcno_' . $tax_key . '_show' ]          = self::checkbox( $input, 'rcno_' . $tax_key . '_show' );
			$clean[ 'rcno_' . $tax_key . '_filter' ]        = self::checkbox( $input, 'rcno_' . $tax_key . '_filter' );
			$clean[ 'rcno_' . $tax_key . '_posts' ]         = self::checkbox( $input, 'rcno_' . $tax_key . '_posts' );
		}

		// Rezensionsbox (Neubau).
		$clean['mbr_show_review_box']     = self::checkbox( $input, 'mbr_show_review_box' );
		$clean['mbr_review_box_position'] = in_array( (string) ( $input['mbr_review_box_position'] ?? '' ), array( 'top', 'bottom' ), true )
			? sanitize_key( $input['mbr_review_box_position'] )
			: 'top';

		// Komponenten (Legacy-Keys).
		$clean['rcno_enable_star_rating_box']     = self::checkbox( $input, 'rcno_enable_star_rating_box' );
		$clean['rcno_star_rating_color']          = self::color( $input, 'rcno_star_rating_color', (string) ( $clean['rcno_star_rating_color'] ?? '#f5a623' ) );
		$clean['rcno_enable_purchase_links']      = self::checkbox( $input, 'rcno_enable_purchase_links' );
		$clean['rcno_store_purchase_links_label'] = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_store_purchase_links_label'] ?? ( $clean['rcno_store_purchase_links_label'] ?? 'Kaufen bei:' ) ) )
		);
		$clean['rcno_show_review_score_box']      = self::checkbox( $input, 'rcno_show_review_score_box' );
		$clean['rcno_excerpt_read_more']          = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_excerpt_read_more'] ?? ( $clean['rcno_excerpt_read_more'] ?? 'Weiterlesen' ) ) )
		);

		$word_count = (int) ( $input['rcno_excerpt_word_count'] ?? ( $clean['rcno_excerpt_word_count'] ?? 55 ) );
		$clean['rcno_excerpt_word_count'] = (string) max( 20, min( 500, $word_count ) );

		// Kommentar-Bewertung (Legacy-Keys).
		$clean['rcno_enable_comment_ratings']     = self::checkbox( $input, 'rcno_enable_comment_ratings' );
		$clean['rcno_comment_rating_label']      = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_comment_rating_label'] ?? ( $clean['rcno_comment_rating_label'] ?? 'Bewerte dieses Buch' ) ) )
		);
		$clean['rcno_comment_rating_star_color']  = self::color( $input, 'rcno_comment_rating_star_color', (string) ( $clean['rcno_comment_rating_star_color'] ?? '#f5a623' ) );

		// Erweitert (Legacy-Keys).
		$clean['rcno_disable_review_schema'] = self::checkbox( $input, 'rcno_disable_review_schema' );
		$clean['rcno_disable_book_schema']   = self::checkbox( $input, 'rcno_disable_book_schema' );

		// Erweitert: Buch-APIs (Legacy-Keys).
		$clean['rcno_external_book_api'] = in_array(
			(string) ( $input['rcno_external_book_api'] ?? '' ),
			array( 'no-3rd-party', 'google-books', 'good-reads' ),
			true
		) ? sanitize_key( $input['rcno_external_book_api'] ) : 'no-3rd-party';

		$clean['rcno_enable_googlebooks'] = self::checkbox( $input, 'rcno_enable_googlebooks' );
		$clean['rcno_googlebooks_key']   = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_googlebooks_key'] ?? ( $clean['rcno_googlebooks_key'] ?? '' ) ) )
		);

		$clean['rcno_enable_goodreads'] = self::checkbox( $input, 'rcno_enable_goodreads' );
		$clean['rcno_goodreads_key']    = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_goodreads_key'] ?? ( $clean['rcno_goodreads_key'] ?? '' ) ) )
		);
		$clean['rcno_goodreads_secret'] = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_goodreads_secret'] ?? ( $clean['rcno_goodreads_secret'] ?? '' ) ) )
		);

		// Erweitert: Unzählbare Wörter / Pluralisierung (Legacy-Keys).
		$clean['rcno_no_pluralization'] = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_no_pluralization'] ?? ( $clean['rcno_no_pluralization'] ?? '' ) ) )
		);
		$clean['rcno_disable_pluralization'] = self::checkbox( $input, 'rcno_disable_pluralization' );

		// Buchfeld-Komponenten.
		foreach ( array(
			'rcno_show_isbn',
			'rcno_show_isbn13',
			'rcno_show_asin',
			'rcno_show_gr_id',
			'rcno_show_gr_url',
			'rcno_show_illustrator',
			'rcno_show_pub_date',
			'rcno_show_pub_format',
			'rcno_show_pub_edition',
			'rcno_show_series_number',
			'rcno_show_page_count',
			'rcno_show_gr_rating',
			'rcno_show_book_cover_url',
		) as $component_key ) {
			$clean[ $component_key ] = self::checkbox( $input, $component_key );
		}

		// Kauf-Links: Shops-Liste und Button-Farben.
		$clean['rcno_store_purchase_links'] = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_store_purchase_links'] ?? ( $clean['rcno_store_purchase_links'] ?? 'Amazon,Barnes & Noble,Kobo' ) ) )
		);
		$clean['rcno_store_purchase_link_text_color'] = self::color( $input, 'rcno_store_purchase_link_text_color', (string) ( $clean['rcno_store_purchase_link_text_color'] ?? '#171717' ) );
		$clean['rcno_store_purchase_link_background'] = self::color( $input, 'rcno_store_purchase_link_background', (string) ( $clean['rcno_store_purchase_link_background'] ?? '#ededed' ) );

		// Sterne-/Score-Box-Farben.
		$clean['rcno_star_background_color'] = self::color( $input, 'rcno_star_background_color', (string) ( $clean['rcno_star_background_color'] ?? '' ) );
		$clean['rcno_show_review_score_box_background'] = self::color( $input, 'rcno_show_review_score_box_background', (string) ( $clean['rcno_show_review_score_box_background'] ?? '#ffffff' ) );
		$clean['rcno_show_review_score_box_accent'] = self::color( $input, 'rcno_show_review_score_box_accent', (string) ( $clean['rcno_show_review_score_box_accent'] ?? '#212121' ) );
		$clean['rcno_show_review_score_box_accent_2'] = self::color( $input, 'rcno_show_review_score_box_accent_2', (string) ( $clean['rcno_show_review_score_box_accent_2'] ?? '#f5a623' ) );

		// Feste Bewertungskriterien.
		$clean['rcno_enable_custom_review_score_criteria'] = self::checkbox( $input, 'rcno_enable_custom_review_score_criteria' );
		$clean['rcno_custom_review_score_criteria'] = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_custom_review_score_criteria'] ?? ( $clean['rcno_custom_review_score_criteria'] ?? 'Plot,Characters,World' ) ) )
		);

		// Widgets.
		foreach ( array(
			'rcno_show_book_slider_widget',
			'rcno_show_book_grid_widget',
			'rcno_show_recent_reviews_widget',
			'rcno_show_tag_cloud_widget',
			'rcno_show_taxonomy_list_widget',
		) as $widget_key ) {
			$clean[ $widget_key ] = self::checkbox( $input, $widget_key );
		}

		// Index-Seite.
		$clean['rcno_reviews_index_headers']         = self::checkbox( $input, 'rcno_reviews_index_headers' );
		$clean['rcno_show_book_covers_index']        = self::checkbox( $input, 'rcno_show_book_covers_index' );
		$clean['rcno_reviews_ignore_articles']      = self::checkbox( $input, 'rcno_reviews_ignore_articles' );
		$clean['rcno_reviews_ignored_articles_list'] = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_reviews_ignored_articles_list'] ?? ( $clean['rcno_reviews_ignored_articles_list'] ?? 'The,A,An' ) ) )
		);
		$clean['rcno_reviews_sort_names'] = 'last_name_first_name' === (string) ( $input['rcno_reviews_sort_names'] ?? '' )
			? 'last_name_first_name'
			: 'first_name_last_name';

		// Vorlagen.
		$clean['rcno_review_template'] = in_array(
			(string) ( $input['rcno_review_template'] ?? '' ),
			array( 'basic', 'default', 'metamor', 'insert', 'simple', 'inverse' ),
			true
		) ? sanitize_key( $input['rcno_review_template'] ) : 'default';
		$clean['rcno_default_cover'] = esc_url_raw( wp_unslash( (string) ( $input['rcno_default_cover'] ?? ( $clean['rcno_default_cover'] ?? '' ) ) ) );
		$clean['rcno_reviews_embedded_title_links'] = self::checkbox( $input, 'rcno_reviews_embedded_title_links' );
		$clean['rcno_book_details_meta'] = sanitize_text_field(
			wp_unslash( (string) ( $input['rcno_book_details_meta'] ?? ( $clean['rcno_book_details_meta'] ?? '' ) ) )
		);
		$clean['rcno_custom_styling'] = wp_strip_all_tags(
			(string) ( $input['rcno_custom_styling'] ?? ( $clean['rcno_custom_styling'] ?? '' ) )
		);

		return $clean;
	}

	/**
	 * Ist eine Buchfeld-Komponente aktiviert? (Legacy-Keys rcno_show_*)
	 * Fehlender Key gilt als aktiv, '' (Legacy: nie angehakt) als inaktiv.
	 */
	public static function component_enabled( string $key ): bool {
		$value = self::raw( $key );

		if ( null === $value ) {
			return true;
		}

		return '1' === (string) $value;
	}

	/**
	 * Ist ein Widget aktiviert? (Legacy-Keys rcno_show_*_widget)
	 */
	public static function widget_enabled( string $key ): bool {
		$value = self::raw( $key );

		if ( null === $value ) {
			return true;
		}

		return '1' === (string) $value;
	}

	/**
	 * Farb-Wert als Hex. Das Original-Plugin speicherte teils rgba()-Werte
	 * (z. B. „rgba(255, 235, 59, 1)") – diese werden für die CSS-Variablen
	 * in Hex normalisiert. Leere Werte sind erlaubt (z. B. transparent).
	 */
	public static function color_value( string $key, string $fallback ): string {
		$value = self::raw( $key );

		if ( null === $value ) {
			$defaults = self::defaults();

			return (string) ( $defaults[ $key ] ?? $fallback );
		}

		$normalized = self::normalize_color( (string) $value );

		return $normalized ?? $fallback;
	}

	/**
	 * Normalisiert Hex- und rgba()/rgb()-Farbangaben zu #rrggbb.
	 * Ungültige Werte ergeben null.
	 */
	public static function normalize_color( string $value ): ?string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		$hex = sanitize_hex_color( $value );

		if ( null !== $hex && '' !== $hex ) {
			return $hex;
		}

		if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*[\d.]+)?\s*\)$/i', $value, $m ) ) {
			$r = max( 0, min( 255, (int) $m[1] ) );
			$g = max( 0, min( 255, (int) $m[2] ) );
			$b = max( 0, min( 255, (int) $m[3] ) );

			return sprintf( '#%02x%02x%02x', $r, $g, $b );
		}

		return null;
	}

	/**
	 * Formatiert einen Autor-Namen gemäß rcno_reviews_sort_names:
	 * last_name_first_name => „Vokal, Norbert", sonst „Norbert Vokal".
	 */
	public static function format_author_name( string $name ): string {
		$name = trim( $name );

		if ( 'last_name_first_name' !== (string) self::get( 'rcno_reviews_sort_names', 'first_name_last_name' ) ) {
			return $name;
		}

		$parts = preg_split( '/\s+/', $name );

		if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
			return $name;
		}

		$last = array_pop( $parts );

		return $last . ', ' . implode( ' ', $parts );
	}

	/**
	 * Sortiertitel ohne Artikel (rcno_reviews_ignore_articles):
	 * „The Hobbit" => „Hobbit" für die Sortierung der Index-Seite.
	 */
	public static function strip_articles( string $title ): string {
		if ( '1' !== (string) self::get( 'rcno_reviews_ignore_articles', '0' ) ) {
			return $title;
		}

		$articles = array_filter( array_map( 'trim', explode( ',', (string) self::get( 'rcno_reviews_ignored_articles_list', 'The,A,An' ) ) ) );

		if ( array() === $articles ) {
			return $title;
		}

		$pattern = '/^(' . implode( '|', array_map( 'preg_quote', $articles ) ) . ')\s+/i';

		return trim( (string) preg_replace( $pattern, '', $title ) );
	}

	/**
	 * Mehrzahl eines Wortes bilden (Legacy-Verhalten des Original-Plugins).
	 *
	 * - `rcno_disable_pluralization` aktiv  => Wort unverändert.
	 * - Wort steht in `rcno_no_pluralization` (kommagetrennt, z. B.
	 *   „Butter, Leute") => unverändert (unzählbare Wörter).
	 *
	 * Einfache englische Regeln: s/x/z/ch/sh => „-es", Endung y => „-ies",
	 * sonst „-s".
	 */
	public static function pluralize( string $word ): string {
		$word = trim( $word );

		if ( '' === $word || self::pluralization_disabled( $word ) ) {
			return $word;
		}

		$lower = strtolower( $word );

		if ( preg_match( '/(?:s|x|z|ch|sh)$/i', $lower ) ) {
			return $word . 'es';
		}

		if ( preg_match( '/[^aeiou]y$/i', $lower ) ) {
			return substr( $word, 0, -1 ) . 'ies';
		}

		return $word . 's';
	}

	/**
	 * Einzahl eines Wortes bilden (Inverse Regel zur Mehrzahl).
	 */
	public static function singularize( string $word ): string {
		$word = trim( $word );

		if ( '' === $word || self::pluralization_disabled( $word ) ) {
			return $word;
		}

		$lower = strtolower( $word );

		if ( preg_match( '/ies$/', $lower ) ) {
			return substr( $word, 0, -3 ) . 'y';
		}

		if ( preg_match( '/(?:s|x|z|ch|sh)es$/', $lower ) ) {
			return substr( $word, 0, -2 );
		}

		if ( preg_match( '/[^s]s$/', $lower ) && ! preg_match( '/ss$/', $lower ) ) {
			return substr( $word, 0, -1 );
		}

		return $word;
	}

	/**
	 * Ist die Mehrzahlbildung für dieses Wort deaktiviert?
	 */
	private static function pluralization_disabled( string $word ): bool {
		if ( '1' === (string) self::get( 'rcno_disable_pluralization', '0' ) ) {
			return true;
		}

		$list = self::split_list( 'rcno_no_pluralization', '' );
		$lower = strtolower( trim( $word ) );

		return in_array( $lower, array_map( 'strtolower', $list ), true );
	}

	/**
	 * Parst eine kommaseparierte Liste (z. B. Shops, Kriterien) sicher.
	 *
	 * @return array<int, string>
	 */
	public static function split_list( string $key, string $fallback = '' ): array {
		$value = (string) self::get( $key, $fallback );

		$items = array_filter(
			array_map(
				static fn( string $item ): string => trim( wp_strip_all_tags( $item ) ),
				explode( ',', $value )
			),
			static fn( string $item ): bool => '' !== $item
		);

		return array_values( $items );
	}

	/**
	 * Parst eine Taxonomie-Auswahl (kommasepariert) in Keys.
	 *
	 * @return array<string, array{key: string, name: string}>
	 */
	private static function custom_taxonomies_from( string $selection ): array {
		$result = array();

		if ( '' === trim( $selection ) ) {
			return $result;
		}

		foreach ( explode( ',', $selection ) as $entry ) {
			$entry = trim( $entry );

			if ( '' === $entry ) {
				continue;
			}

			$key = self::sanitize_taxonomy_key( $entry );

			if ( '' === $key || in_array( $key, self::BUILTIN_TAXONOMIES, true ) ) {
				continue;
			}

			$result[ $key ] = array(
				'key'  => $key,
				'name' => $entry,
			);
		}

		return $result;
	}

	/**
	 * Formular-Checkbox als '1'/'0'.
	 *
	 * Das Template legt zu jeder Checkbox ein verstecktes Marker-Feld
	 * (mbr_checkbox_markers) an, damit „nicht angehakt" von „nicht im
	 * Formular" unterschieden werden kann. Keys außerhalb des Formulars
	 * behalten ihren gespeicherten Wert (Merge-Verhalten).
	 */
	private static function checkbox( array $input, string $key ): string {
		$markers = is_array( $input['mbr_checkbox_markers'] ?? null ) ? $input['mbr_checkbox_markers'] : null;

		if ( is_array( $markers ) && array_key_exists( $key, $markers ) ) {
			return empty( $input[ $key ] ) ? '0' : '1';
		}

		// Nicht im Formular: bestehenden Wert beibehalten.
		$existing = self::raw( $key );

		return null === $existing ? '0' : ( '1' === (string) $existing ? '1' : '0' );
	}

	/**
	 * Hex-Farbe mit Fallback; verhindert CSS-Injection über Farb-Eingaben.
	 * Erkennt auch rgba()-Angaben des Original-Plugins und normalisiert sie.
	 */
	private static function color( array $input, string $key, string $fallback ): string {
		$raw_value = isset( $input[ $key ] ) ? (string) wp_unslash( $input[ $key ] ) : null;

		if ( null === $raw_value ) {
			// Nicht im Formular: bestehenden Wert beibehalten.
			$existing = self::raw( $key );

			return null === $existing ? $fallback : (string) $existing;
		}

		if ( '' === trim( $raw_value ) ) {
			// Leere Farbe ist erlaubt (z. B. transparenter Sternehintergrund).
			return '';
		}

		$normalized = self::normalize_color( $raw_value );

		return $normalized ?? $fallback;
	}
}
