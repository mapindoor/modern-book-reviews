<?php
/**
 * Registriert alle Buch-Metadaten über die moderne Post-Meta-API.
 *
 * Jedes Feld wird typsicher mit Schema, Berechtigungsprüfung (auth_callback)
 * und Sanitize-Callback registriert und ist über die REST API verfügbar
 * (show_in_rest). Dadurch bearbeitet der Gutenberg-Editor die Daten über
 * die offizielle Schnittstelle – keine eigenen AJAX-Endpunkte zum Speichern,
 * keine separaten Nonce-Verarbeitungen, kein direkter $_POST-Zugriff.
 *
 * Die Meta-Keys sind bewusst identisch mit dem alten Recencio-Plugin,
 * damit bestehende Rezensionen ohne Migration weiterlaufen.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class Meta {

	public const POST_TYPE = PostTypes::POST_TYPE;

	/**
	 * Registriert alle Meta-Felder.
	 */
	public static function register(): void {
		$string_fields = array(
			'rcno_book_title'             => array(),
			'rcno_book_isbn'              => array( 'pattern' => '^[0-9Xx-]{9,17}$' ),
			'rcno_book_isbn13'            => array( 'pattern' => '^[0-9Xx-]{9,17}$' ),
			'rcno_book_asin'              => array( 'pattern' => '^[A-Za-z0-9]{10}$' ),
			'rcno_book_illustrator'       => array(),
			'rcno_book_series_number'     => array(),
			'rcno_book_pub_date'          => array(),
			'rcno_book_pub_format'       => array(),
			'rcno_book_pub_edition'       => array(),
			'rcno_book_page_count'        => array(),
			'rcno_book_description'       => array(),
			'rcno_book_gr_id'             => array(),
			'rcno_book_gr_rating'         => array(),
			'rcno_admin_rating'          => array( 'pattern' => '^[0-5](\.[0-9]+)?$' ),
			'rcno_review_score_type'      => array(),
			'rcno_review_score_position'  => array(),
			'rcno_review_score_enable'    => array(),
		);

		foreach ( $string_fields as $meta_key => $args ) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => array( __CLASS__, 'can_edit' ),
					...$args,
				)
			);
		}

		// URLs separat mit sauberer URL-Bereinigung. Das sind zugleich die
		// Legacy-Felder des alten Plugins (Cover-Fallback, Goodreads-Link).
		foreach ( array( 'rcno_reviews_book_cover_src', 'rcno_reviews_book_cover_url', 'rcno_book_gr_url' ) as $meta_key ) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'esc_url_raw',
					'auth_callback'     => array( __CLASS__, 'can_edit' ),
				)
			);
		}

		// Kauf-/Affiliate-Links: Array von {store, link}. Der Wert wird
		// vollständig bereinigt (Text bzw. URL je Element).
		register_post_meta(
			self::POST_TYPE,
			'rcno_review_buy_links',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'     => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'store' => array(
									'type'              => 'string',
									'sanitize_callback' => 'sanitize_text_field',
								),
								'link'  => array(
									'type'              => 'string',
									'format'            => 'uri',
									'sanitize_callback' => 'esc_url_raw',
								),
							),
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_buy_links' ),
				'auth_callback'     => array( __CLASS__, 'can_edit' ),
			)
		);

		// Bewertungskriterien: Array von {label, score}.
		register_post_meta(
			self::POST_TYPE,
			'rcno_review_score_criteria',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'     => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'label' => array(
									'type'              => 'string',
									'sanitize_callback' => 'sanitize_text_field',
								),
								'score' => array(
									'type'              => 'string',
									'sanitize_callback' => array( __CLASS__, 'sanitize_score' ),
								),
							),
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_criteria' ),
				'auth_callback'     => array( __CLASS__, 'can_edit' ),
			)
		);
	}

	/**
	 * Berechtigungsprüfung beim Schreiben von Meta.
	 */
	public static function can_edit( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Bereinigt die Kauf-Links-Liste.
	 *
	 * @param mixed $value Roher Wert.
	 * @return array<int, array{store: string, link: string}>
	 */
	public static function sanitize_buy_links( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$link = esc_url_raw( (string) ( $item['link'] ?? '' ) );

			if ( '' === $link ) {
				continue;
			}

			$clean[] = array(
				'store' => sanitize_text_field( (string) ( $item['store'] ?? '' ) ),
				'link'  => $link,
			);
		}

		return $clean;
	}

	/**
	 * Bereinigt die Bewertungskriterien.
	 *
	 * @param mixed $value Roher Wert.
	 * @return array<int, array{label: string, score: string}>
	 */
	public static function sanitize_criteria( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();

		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $item['label'] ?? '' ) );

			if ( '' === $label ) {
				continue;
			}

			$clean[] = array(
				'label' => $label,
				'score' => self::sanitize_score( (string) ( $item['score'] ?? '' ) ),
			);
		}

		return $clean;
	}

	/**
	 * Erlaubt Buchstaben-Noten (A–F, +/-) oder Zahlen 0–10.
	 */
	public static function sanitize_score( string $score ): string {
		$score = strtoupper( trim( $score ) );

		if ( preg_match( '/^[A-F][+-]?$/', $score ) ) {
			return $score;
		}

		if ( preg_match( '/^\d{1,2}(\.\d+)?$/', $score ) && (float) $score <= 10 ) {
			return (string) (float) $score;
		}

		return '';
	}

	/**
	 * Liest eine Meta-fernsicher als String.
	 */
	public static function get_string( int $post_id, string $meta_key ): string {
		$value = get_post_meta( $post_id, $meta_key, true );

		// Manche Recencio-Installationen nutzten abweichende Meta-Keys
		// (z. B. „rcno_book_gr_review“ statt „rcno_book_gr_rating“).
		// Bei leerem Wert wird auf den Legacy-Alias zurückgegriffen.
		if ( ( '' === $value || null === $value ) && isset( self::LEGACY_ALIASES[ $meta_key ] ) ) {
			$value = get_post_meta( $post_id, self::LEGACY_ALIASES[ $meta_key ], true );
		}

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Abweichende Meta-Keys einzelner Original-Installationen.
	 */
	private const LEGACY_ALIASES = array(
		'rcno_book_gr_rating' => 'rcno_book_gr_review',
	);

	/**
	 * Liest die Kauf-Links als typsicheres Array.
	 *
	 * @return array<int, array{store: string, link: string}>
	 */
	public static function get_buy_links( int $post_id ): array {
		$value = get_post_meta( $post_id, 'rcno_review_buy_links', true );

		if ( ! is_array( $value ) ) {
			return array();
		}

		$links = array();

		foreach ( $value as $item ) {
			if ( is_array( $item ) && ! empty( $item['link'] ) ) {
				$links[] = array(
					'store' => (string) ( $item['store'] ?? '' ),
					'link'  => (string) $item['link'],
				);
			}
		}

		return $links;
	}

	/**
	 * Liest die Bewertungskriterien als typsicheres Array.
	 *
	 * @return array<int, array{label: string, score: string}>
	 */
	public static function get_criteria( int $post_id ): array {
		$value = get_post_meta( $post_id, 'rcno_review_score_criteria', true );

		if ( ! is_array( $value ) ) {
			return array();
		}

		$criteria = array();

		foreach ( $value as $item ) {
			if ( is_array( $item ) && ! empty( $item['label'] ) ) {
				$criteria[] = array(
					'label' => (string) $item['label'],
					'score' => (string) ( $item['score'] ?? '' ),
				);
			}
		}

		return $criteria;
	}
}
