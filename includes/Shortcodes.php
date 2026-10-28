<?php
/**
 * Shortcodes mit Legacy-Aliasen des alten Recencio-Plugins.
 *
 * Neue Shortcodes:  [mbr-review-box], [mbr-reviews-grid], [mbr-reviews-index], [mbr-purchase-links]
 * Aliase:           [rcno-review-box], [rcno-reviews-grid], [rcno-reviews-index], [rcno-purchase-links]
 *
 * Alle Attribute werden validiert (absint/sanitize_key), Ausgaben escaped.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class Shortcodes {

	/**
	 * Registriert alle Shortcodes.
	 */
	public static function register(): void {
		add_shortcode( 'mbr-review-box', array( __CLASS__, 'review_box' ) );
		add_shortcode( 'mbr-reviews-grid', array( __CLASS__, 'reviews_grid' ) );
		add_shortcode( 'mbr-reviews-index', array( __CLASS__, 'reviews_index' ) );
		add_shortcode( 'mbr-purchase-links', array( __CLASS__, 'purchase_links' ) );

		// Legacy-Aliase für bestehende Inhalte.
		add_shortcode( 'rcno-review-box', array( __CLASS__, 'review_box' ) );
		add_shortcode( 'rcno-reviews-grid', array( __CLASS__, 'reviews_grid' ) );
		add_shortcode( 'rcno-reviews-index', array( __CLASS__, 'reviews_index' ) );
		add_shortcode( 'rcno-purchase-links', array( __CLASS__, 'purchase_links' ) );
	}

	/**
	 * Sollen eingebettete Titel (Raster/Index) verlinkt werden?
	 * Legacy-Key: rcno_reviews_embedded_title_links.
	 */
	private static function embedded_title_links(): bool {
		$value = Settings::raw( 'rcno_reviews_embedded_title_links' );

		if ( null === $value ) {
			return true;
		}

		return '1' === (string) $value;
	}

	/**
	 * [mbr-review-box id="123"]
	 */
	public static function review_box( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array( 'id' => 0 ),
			(array) $atts,
			'mbr-review-box'
		);

		$review_id = absint( $atts['id'] );

		if ( 0 === $review_id ) {
			$review_id = (int) get_the_ID();
		}

		return ReviewBox::render( $review_id );
	}

	/**
	 * [mbr-reviews-grid count="12" columns="4" author="term-slug" genre="term-slug"]
	 */
	public static function reviews_grid( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'count'   => 12,
				'columns' => 4,
				'author'  => '',
				'genre'   => '',
				'series'  => '',
			),
			(array) $atts,
			'mbr-reviews-grid'
		);

		$args = array(
			'post_type'              => PostTypes::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => max( 1, min( 100, absint( $atts['count'] ) ) ),
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
			'ignore_sticky_posts'    => true,
		);

		$tax_query = array();

		foreach ( array( 'author' => PostTypes::TAX_AUTHOR, 'genre' => PostTypes::TAX_GENRE, 'series' => PostTypes::TAX_SERIES ) as $key => $taxonomy ) {
			if ( '' !== (string) $atts[ $key ] ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => sanitize_title( (string) $atts[ $key ] ),
				);
			}
		}

		if ( array() !== $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '';
		}

		wp_enqueue_style( 'mbr-public' );

		$columns     = max( 1, min( 6, absint( $atts['columns'] ) ) );
		$style       = '--mbr-grid-columns:' . $columns . ';';
		$linked      = self::embedded_title_links();
		$item_tag    = $linked ? 'a' : 'span';

		ob_start();
		?>
		<div class="mbr-reviews-grid" style="<?php echo esc_attr( $style ); ?>">
			<?php foreach ( $query->posts as $review ) : ?>
				<?php
				$review_id = (int) $review->ID;
				$href      = $linked ? ' href="' . esc_url( (string) get_permalink( $review_id ) ) . '"' : '';
				?>
				<<?php echo esc_html( $item_tag ); ?> class="mbr-grid-item"<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput -- esc_url() im Attribut. ?>>
					<?php $cover = ReviewBox::cover_markup( $review_id ); ?>
					<?php if ( '' !== $cover ) : ?>
						<span class="mbr-grid-cover"><?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput -- interne Helper, escaped. ?></span>
					<?php endif; ?>
					<span class="mbr-grid-title"><?php echo esc_html( (string) get_the_title( $review_id ) ); ?></span>
					<?php $authors = get_the_term_list( $review_id, PostTypes::TAX_AUTHOR, '', ', ' ); ?>
					<?php if ( is_string( $authors ) && '' !== $authors ) : ?>
						<span class="mbr-grid-authors"><?php echo wp_strip_all_tags( $authors ); ?></span>
					<?php endif; ?>
				</<?php echo esc_html( $item_tag ); ?>>
			<?php endforeach; ?>
		</div>
		<?php
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * [mbr-reviews-index] / [rcno-reviews-index]
	 *
	 * A–Z-Index aller Rezensionen. Steuerung über die Legacy-Keys:
	 * rcno_reviews_index_headers, rcno_show_book_covers_index,
	 * rcno_reviews_ignore_articles (+ Artikelliste), rcno_reviews_embedded_title_links.
	 */
	public static function reviews_index( array|string $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'count' => 500,
				'genre' => '',
			),
			(array) $atts,
			'mbr-reviews-index'
		);

		$args = array(
			'post_type'              => PostTypes::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => max( 10, min( 500, absint( $atts['count'] ) ) ),
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'ignore_sticky_posts'    => true,
		);

		$genre = sanitize_title( (string) $atts['genre'] );

		if ( '' !== $genre ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => PostTypes::TAX_GENRE,
					'field'    => 'slug',
					'terms'    => $genre,
				),
			);
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '';
		}

		wp_enqueue_style( 'mbr-public' );

		$headers   = '1' === (string) Settings::get( 'rcno_reviews_index_headers', '1' );
		$covers    = '1' === (string) Settings::get( 'rcno_show_book_covers_index', '1' );
		$linked    = self::embedded_title_links();

		// Nach Sortiertitel (ohne Artikel) gruppieren.
		$groups = array();

		foreach ( $query->posts as $review ) {
			$review_id = (int) $review->ID;
			$title     = (string) get_the_title( $review_id );
			$sort      = Settings::strip_articles( $title );

			$letter = mb_strtoupper( mb_substr( $sort, 0, 1 ) );

			if ( '' === $letter ) {
				$letter = '#';
			}

			if ( ! isset( $groups[ $letter ] ) ) {
				$groups[ $letter ] = array();
			}

			$groups[ $letter ][] = $review_id;
		}

		ksort( $groups, SORT_STRING );

		ob_start();
		?>
		<div class="mbr-reviews-index">
			<?php foreach ( $groups as $letter => $review_ids ) : ?>
				<?php if ( $headers ) : ?>
					<h3 class="mbr-index-letter"><?php echo esc_html( $letter ); ?></h3>
				<?php endif; ?>
				<ul class="mbr-index-group" data-letter="<?php echo esc_attr( $letter ); ?>">
					<?php foreach ( $review_ids as $review_id ) : ?>
						<li class="mbr-index-item">
							<?php if ( $covers ) : ?>
								<?php $cover = ReviewBox::cover_markup( $review_id ); ?>
								<?php if ( '' !== $cover ) : ?>
									<span class="mbr-index-cover"><?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput -- interne Helper, escaped. ?></span>
								<?php endif; ?>
							<?php endif; ?>
							<span class="mbr-index-title">
								<?php if ( $linked ) : ?>
									<a href="<?php echo esc_url( (string) get_permalink( $review_id ) ); ?>"><?php echo esc_html( (string) get_the_title( $review_id ) ); ?></a>
								<?php else : ?>
									<?php echo esc_html( (string) get_the_title( $review_id ) ); ?>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</div>
		<?php
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * [mbr-purchase-links id="123"]
	 */
	public static function purchase_links( array|string $atts = array() ): string {
		$atts      = shortcode_atts( array( 'id' => 0 ), (array) $atts, 'mbr-purchase-links' );
		$review_id = absint( $atts['id'] );

		if ( 0 === $review_id ) {
			$review_id = (int) get_the_ID();
		}

		$links = Meta::get_buy_links( $review_id );

		if ( array() === $links ) {
			return '';
		}

		wp_enqueue_style( 'mbr-public' );

		$html = '<div class="mbr-purchase-links mbr-purchase-links-shortcode">';

		foreach ( $links as $link ) {
			$label = '' !== $link['store'] ? $link['store'] : (string) wp_parse_url( $link['link'], PHP_URL_HOST );

			$html .= sprintf(
				'<a class="mbr-purchase-button" href="%s" target="_blank" rel="nofollow sponsored noopener">%s</a>',
				esc_url( $link['link'] ),
				esc_html( $label )
			);
		}

		$html .= '</div>';

		return $html;
	}
}
