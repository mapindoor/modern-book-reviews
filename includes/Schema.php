<?php
/**
 * Strukturierte Daten (JSON-LD) für Book- und Review-Schema.
 *
 * Die Ausgabe erfolgt mit wp_json_encode() und JSON_HEX_TAG, damit
 * kein `</script>` aus Buchdaten die Seite brechen kann (XSS-Schutz).
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class Schema {

	/**
	 * Gibt die JSON-LD-Blöcke im <head> von Rezensions-Seiten aus.
	 * Die Legacy-Flags `rcno_disable_book_schema` und
	 * `rcno_disable_review_schema` schalten die Blöcke einzeln ab.
	 */
	public static function print_schema(): void {
		$post = get_post();

		if ( ! $post instanceof \WP_Post
			|| PostTypes::POST_TYPE !== $post->post_type
			|| ! is_singular( PostTypes::POST_TYPE )
			|| post_password_required( $post )
		) {
			return;
		}

		$review_id = (int) $post->ID;

		$schemas = array();

		if ( '1' !== (string) Settings::get( 'rcno_disable_book_schema', '0' ) ) {
			$schemas[] = self::book_schema( $review_id );
		}

		if ( '1' !== (string) Settings::get( 'rcno_disable_review_schema', '0' ) ) {
			$schemas[] = self::review_schema( $review_id );
		}

		foreach ( $schemas as $schema ) {
			printf(
				'<script type="application/ld+json">%s</script>' . "\n",
				wp_json_encode( $schema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE )
			);
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function book_schema( int $review_id ): array {
		$book_title = Meta::get_string( $review_id, 'rcno_book_title' );
		$book_title = '' !== $book_title ? $book_title : (string) get_the_title( $review_id );

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Book',
			'name'     => $book_title,
		);

		$authors = self::term_names( $review_id, PostTypes::TAX_AUTHOR );

		if ( array() !== $authors ) {
			$schema['author'] = array_map(
				static fn( string $name ): array => array(
					'@type' => 'Person',
					'name'  => $name,
				),
				$authors
			);
		}

		$publishers = self::term_names( $review_id, PostTypes::TAX_PUBLISHER );

		if ( array() !== $publishers ) {
			$schema['publisher'] = array(
				'@type' => 'Organization',
				'name'  => $publishers[0],
			);
		}

		$isbn = Meta::get_string( $review_id, 'rcno_book_isbn13' );

		if ( '' === $isbn ) {
			$isbn = Meta::get_string( $review_id, 'rcno_book_isbn' );
		}

		if ( '' !== $isbn ) {
			$schema['isbn'] = $isbn;
		}

		$pages = (int) Meta::get_string( $review_id, 'rcno_book_page_count' );

		if ( $pages > 0 ) {
			$schema['numberOfPages'] = $pages;
		}

		$pub_date = Meta::get_string( $review_id, 'rcno_book_pub_date' );

		if ( '' !== $pub_date ) {
			$schema['datePublished'] = $pub_date;
		}

		$cover = self::cover_url( $review_id );

		if ( '' !== $cover ) {
			$schema['image'] = $cover;
		}

		$genres = self::term_names( $review_id, PostTypes::TAX_GENRE );

		if ( array() !== $genres ) {
			$schema['genre'] = $genres;
		}

		$description = Meta::get_string( $review_id, 'rcno_book_description' );

		if ( '' === $description ) {
			$description = (string) get_the_excerpt( $review_id );
		}

		if ( '' !== $description ) {
			$schema['description'] = $description;
		}

		return $schema;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function review_schema( int $review_id ): array {
		$schema = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'Review',
			'itemReviewed' => array_merge(
				array( '@type' => 'Book' ),
				array( 'name' => (string) get_the_title( $review_id ) )
			),
			'headline'     => (string) get_the_title( $review_id ),
			'datePublished' => get_the_date( 'c', $review_id ),
			'author'       => array(
				'@type' => 'Person',
				'name'  => (string) get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $review_id ) ),
				'url'   => (string) get_author_posts_url( (int) get_post_field( 'post_author', $review_id ) ),
			),
		);

		$rating = (float) Meta::get_string( $review_id, 'rcno_admin_rating' );

		if ( $rating > 0 && $rating <= 5 ) {
			$schema['reviewRating'] = array(
				'@type'       => 'Rating',
				'ratingValue' => $rating,
				'bestRating'  => 5,
				'worstRating' => 1,
			);
		}

		return $schema;
	}

	/**
	 * Cover-URL (Beitragsbild > Legacy-Meta).
	 */
	private static function cover_url( int $review_id ): string {
		$attachment_id = (int) get_post_thumbnail_id( $review_id );

		if ( $attachment_id > 0 ) {
			$src = wp_get_attachment_image_url( $attachment_id, 'large' );

			if ( is_string( $src ) && '' !== $src ) {
				return $src;
			}
		}

		$legacy_id = (int) get_post_meta( $review_id, 'rcno_reviews_book_cover_id', true );

		if ( $legacy_id > 0 ) {
			$src = wp_get_attachment_image_url( $legacy_id, 'large' );

			if ( is_string( $src ) && '' !== $src ) {
				return $src;
			}
		}

		$src = Meta::get_string( $review_id, 'rcno_reviews_book_cover_src' );

		if ( '' !== $src ) {
			return $src;
		}

		// Standard-Cover aus den Einstellungen (Legacy: rcno_default_cover).
		return esc_url_raw( Settings::get( 'rcno_default_cover', '' ) );
	}

	/**
	 * @return array<int, string>
	 */
	private static function term_names( int $review_id, string $taxonomy ): array {
		$terms = get_the_terms( $review_id, $taxonomy );

		if ( ! is_array( $terms ) ) {
			return array();
		}

		return array_map(
			static fn( \WP_Term $term ): string => $term->name,
			$terms
		);
	}
}
