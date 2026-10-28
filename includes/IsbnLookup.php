<?php
/**
 * ISBN-Lookup über Open Library und Google Books.
 *
 * Serverseitiger REST-Endpoint `/mbr/v1/isbn/{isbn}`:
 * - Berechtigung: `edit_posts` (nur eingeloggte Redakteure).
 * - Antworten werden 12 Stunden als Transient gecacht.
 * - Feste Allow-List für externe Hosts, Timeout 5 Sekunden,
 *   keine Benutzereingaben in URLs außer der validierten ISBN.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class IsbnLookup {

	public const REST_NAMESPACE = 'mbr/v1';
	private const CACHE_TTL      = 12 * HOUR_IN_SECONDS;

	/**
	 * Registriert die REST-Routen.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/isbn/(?P<isbn>[0-9Xx-]{9,17})',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'handle_request' ),
				'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
				'args'                => array(
					'isbn' => array(
						'type'              => 'string',
						'required'          => true,
						'pattern'           => '^[0-9Xx-]{9,17}$',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Bearbeitet eine Lookup-Anfrage.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	/**
	 * Programmatischer ISBN-Lookup (z. B. für die Erweiterung
	 * „Buchdeckel laden“). Liefert die Buchdaten oder null.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function lookup( string $raw_isbn ): ?array {
		$isbn = preg_replace( '/[^0-9X]/i', '', $raw_isbn );

		if ( null === $isbn || ! preg_match( '/^([0-9]{9}[0-9X]|[0-9]{13})$/i', $isbn ) ) {
			return null;
		}

		$cache_key = 'mbr_isbn_' . md5( strtolower( $isbn ) );

		$cached = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$book = self::query_configured_api( $isbn );

		if ( null === $book ) {
			$book = self::query_open_library( $isbn );
		}

		if ( null === $book ) {
			$book = self::query_google_books( $isbn, '' );
		}

		if ( null === $book ) {
			return null;
		}

		set_transient( $cache_key, $book, self::CACHE_TTL );

		return $book;
	}

	public static function handle_request( \WP_REST_Request $request ) {
		$raw_isbn = (string) $request->get_param( 'isbn' );
		$isbn     = preg_replace( '/[^0-9X]/i', '', $raw_isbn );

		if ( null === $isbn || ! preg_match( '/^([0-9]{9}[0-9X]|[0-9]{13})$/i', $isbn ) ) {
			return new \WP_Error(
				'mbr_invalid_isbn',
				__( 'Ungültige ISBN.', 'modern-book-reviews' ),
				array( 'status' => 400 )
			);
		}

		$cache_key = 'mbr_isbn_' . md5( strtolower( $isbn ) );

		$cached = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return rest_ensure_response( $cached );
		}

		$book = self::query_configured_api( $isbn );

		// Fallback bzw. „Keine“: freie Quellen ohne API-Key.
		if ( null === $book ) {
			$book = self::query_open_library( $isbn );
		}

		if ( null === $book ) {
			$book = self::query_google_books( $isbn, '' );
		}

		if ( null === $book ) {
			return new \WP_Error(
				'mbr_isbn_not_found',
				__( 'Zu dieser ISBN wurden keine Daten gefunden.', 'modern-book-reviews' ),
				array( 'status' => 404 )
			);
		}

		set_transient( $cache_key, $book, self::CACHE_TTL );

		return rest_ensure_response( $book );
	}

	/**
	 * Fragt die in den Einstellungen gewählte externe Buch-API ab
	 * (Legacy-Keys rcno_external_book_api, rcno_enable_googlebooks,
	 * rcno_enable_goodreads + rcno_googlebooks_key / rcno_goodreads_key).
	 * Liefert null, wenn keine gewählte API aktiviert bzw. konfiguriert ist.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function query_configured_api( string $isbn ): ?array {
		$api = (string) Settings::get( 'rcno_external_book_api', 'no-3rd-party' );

		if ( 'google-books' === $api && '1' === (string) Settings::get( 'rcno_enable_googlebooks', '0' ) ) {
			$key = trim( (string) Settings::get( 'rcno_googlebooks_key', '' ) );

			if ( '' !== $key ) {
				return self::query_google_books( $isbn, $key );
			}
		}

		if ( 'good-reads' === $api && '1' === (string) Settings::get( 'rcno_enable_goodreads', '0' ) ) {
			$key = trim( (string) Settings::get( 'rcno_goodreads_key', '' ) );

			if ( '' !== $key ) {
				return self::query_goodreads( $isbn, $key );
			}
		}

		return null;
	}

	/**
	 * Fragt die Goodreads®-API ab (Legacy-Einstellung, API-Key erforderlich).
	 *
	 * @return array<string, mixed>|null
	 */
	private static function query_goodreads( string $isbn, string $key ): ?array {
		$url = add_query_arg(
			array(
				'key'    => rawurlencode( $key ),
				'format' => 'json',
			),
			'https://www.goodreads.com/book/isbn/' . rawurlencode( $isbn )
		);

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'    => 5,
				'user-agent' => 'ModernBookReviews/' . MBR_VERSION . '; WordPress',
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['title'] ) ) {
			return null;
		}

		$book = array(
			'source'      => 'goodreads',
			'title'       => sanitize_text_field( (string) $data['title'] ),
			'isbn13'      => isset( $data['isbn13'] ) ? sanitize_text_field( (string) $data['isbn13'] ) : '',
			'publisher'   => isset( $data['publisher'] ) ? sanitize_text_field( (string) $data['publisher'] ) : '',
			'pub_date'    => isset( $data['publication_year'] ) ? sanitize_text_field( (string) $data['publication_year'] ) : '',
			'page_count'  => isset( $data['num_pages'] ) ? (int) $data['num_pages'] : 0,
			'cover_url'   => isset( $data['image_url'] ) ? esc_url_raw( str_replace( 'http://', 'https://', (string) $data['image_url'] ) ) : '',
			'authors'     => array(),
			'description' => isset( $data['description'] )
				? wp_trim_words( sanitize_textarea_field( wp_strip_all_tags( (string) $data['description'] ) ), 60 )
				: '',
		);

		foreach ( (array) ( $data['authors']['author'] ?? array() ) as $author ) {
			if ( is_array( $author ) && ! empty( $author['name'] ) ) {
				$book['authors'][] = sanitize_text_field( (string) $author['name'] );
			} elseif ( is_string( $author ) ) {
				$book['authors'][] = sanitize_text_field( $author );
			}
		}

		return $book;
	}

	/**
	 * Fragt Open Library ab.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function query_open_library( string $isbn ): ?array {
		$response = wp_safe_remote_get(
			'https://openlibrary.org/isbn/' . rawurlencode( $isbn ) . '.json',
			array(
				'timeout'    => 5,
				'user-agent' => 'ModernBookReviews/' . MBR_VERSION . '; WordPress',
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['title'] ) ) {
			return null;
		}

		$book = array(
			'source'     => 'openlibrary',
			'title'      => sanitize_text_field( (string) $data['title'] ),
			'isbn13'     => isset( $data['isbn_13'][0] ) ? sanitize_text_field( (string) $data['isbn_13'][0] ) : '',
			'publisher'  => isset( $data['publishers'][0] ) ? sanitize_text_field( (string) $data['publishers'][0] ) : '',
			'pub_date'   => isset( $data['publish_date'] ) ? sanitize_text_field( (string) $data['publish_date'] ) : '',
			'page_count' => isset( $data['number_of_pages'] ) ? (int) $data['number_of_pages'] : 0,
			'cover_url'  => isset( $data['covers'][0] )
				? 'https://covers.openlibrary.org/b/id/' . (int) $data['covers'][0] . '-L.jpg'
				: '',
			'authors'    => array(),
			'description' => '',
		);

		foreach ( (array) ( $data['authors'] ?? array() ) as $author ) {
			if ( is_array( $author ) && ! empty( $author['name'] ) ) {
				$book['authors'][] = sanitize_text_field( (string) $author['name'] );
			}
		}

		if ( isset( $data['description'] ) && is_string( $data['description'] ) ) {
			$book['description'] = wp_trim_words( sanitize_textarea_field( $data['description'] ), 60 );
		}

		return $book;
	}

	/**
	 * Fragt Google Books ab. Ohne API-Key für einfache ISBN-Suchen nutzbar;
	 * mit Key (Einstellung „Google Books® API Key“) wird der Key mitgesendet.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function query_google_books( string $isbn, string $key = '' ): ?array {
		$url = 'https://www.googleapis.com/books/v1/volumes?q=isbn%3A' . rawurlencode( $isbn );

		if ( '' !== $key ) {
			$url .= '&key=' . rawurlencode( $key );
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'    => 5,
				'user-agent' => 'ModernBookReviews/' . MBR_VERSION . '; WordPress',
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data  = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$volume = $data['items'][0]['volumeInfo'] ?? null;

		if ( ! is_array( $volume ) || empty( $volume['title'] ) ) {
			return null;
		}

		$cover = '';

		if ( isset( $volume['imageLinks']['thumbnail'] ) ) {
			$cover = esc_url_raw( str_replace( 'http://', 'https://', (string) $volume['imageLinks']['thumbnail'] ) );
		}

		return array(
			'source'     => 'googlebooks',
			'title'      => sanitize_text_field( (string) $volume['title'] ),
			'isbn13'     => isset( $volume['industryIdentifiers'] ) ? self::extract_isbn13( $volume['industryIdentifiers'] ) : '',
			'publisher'  => isset( $volume['publisher'] ) ? sanitize_text_field( (string) $volume['publisher'] ) : '',
			'pub_date'   => isset( $volume['publishedDate'] ) ? sanitize_text_field( (string) $volume['publishedDate'] ) : '',
			'page_count' => isset( $volume['pageCount'] ) ? (int) $volume['pageCount'] : 0,
			'cover_url'  => $cover,
			'authors'    => array_map(
				static fn( $author ): string => sanitize_text_field( (string) $author ),
				(array) ( $volume['authors'] ?? array() )
			),
			'description' => isset( $volume['description'] )
				? wp_trim_words( sanitize_textarea_field( (string) $volume['description'] ), 60 )
				: '',
		);
	}

	/**
	 * @param array<int, array<string, string>> $identifiers
	 */
	private static function extract_isbn13( array $identifiers ): string {
		foreach ( $identifiers as $identifier ) {
			if ( ( $identifier['type'] ?? '' ) === 'ISBN_13' ) {
				return sanitize_text_field( (string) ( $identifier['identifier'] ?? '' ) );
			}
		}

		return '';
	}
}
