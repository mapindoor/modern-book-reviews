<?php
/**
 * Die öffentliche Rezensionsbox: Cover, Buchdaten, Sterne, Kauflinks.
 *
 * Alle Ausgaben werden konsequent escaped (esc_html/esc_url/esc_attr),
 * Zahlen werden vor der Ausgabe typsicher gecastet. Farben aus den
 * Einstellungen fließen nur als validierte Hex-Werte in CSS-Variablen.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class ReviewBox {

	/**
	 * Registriert die öffentlichen Assets. Alle Farb-Einstellungen des
	 * Original-Plugins (inkl. gespeicherter rgba()-Werte) werden als
	 * validierte Hex-Werte in CSS-Variablen gesetzt; zusätzlich wird
	 * das eigene CSS aus der Vorlagen-Einstellung ausgegeben.
	 */
	public static function register_assets(): void {
		wp_register_style(
			'mbr-public',
			MBR_PLUGIN_URL . 'assets/css/public.css',
			array(),
			MBR_VERSION
		);

		$star_color = Settings::color_value( 'rcno_star_rating_color', Settings::effective( 'rcno_star_rating_color', 'mbr_star_color', '#f5a623' ) );

		$vars = array();

		if ( '' !== $star_color ) {
			$vars[] = '--mbr-star-color:' . $star_color;
		}

		$star_bg = Settings::color_value( 'rcno_star_background_color', '' );

		if ( '' !== $star_bg ) {
			$vars[] = '--mbr-star-bg:' . $star_bg;
		}

		$comment_color = Settings::color_value( 'rcno_comment_rating_star_color', Settings::effective( 'rcno_comment_rating_star_color', 'mbr_comment_rating_color', $star_color ) );

		if ( '' !== $comment_color && $comment_color !== $star_color ) {
			$vars[] = '--mbr-comment-star-color:' . $comment_color;
		}

		$purchase_text = Settings::color_value( 'rcno_store_purchase_link_text_color', '' );

		if ( '' !== $purchase_text ) {
			$vars[] = '--mbr-purchase-text:' . $purchase_text;
		}

		$purchase_bg = Settings::color_value( 'rcno_store_purchase_link_background', '' );

		if ( '' !== $purchase_bg ) {
			$vars[] = '--mbr-purchase-bg:' . $purchase_bg;
		}

		$score_bg = Settings::color_value( 'rcno_show_review_score_box_background', '' );

		if ( '' !== $score_bg ) {
			$vars[] = '--mbr-score-bg:' . $score_bg;
		}

		$score_accent = Settings::color_value( 'rcno_show_review_score_box_accent', '' );

		if ( '' !== $score_accent ) {
			$vars[] = '--mbr-score-accent:' . $score_accent;
		}

		$score_accent_2 = Settings::color_value( 'rcno_show_review_score_box_accent_2', '' );

		if ( '' !== $score_accent_2 ) {
			$vars[] = '--mbr-score-accent-2:' . $score_accent_2;
		}

		if ( array() !== $vars ) {
			wp_add_inline_style( 'mbr-public', ':root{' . implode( ';', $vars ) . ';}' );
		}

		// Eigene Styles aus der Vorlagen-Einstellung (bereits beim Speichern
		// von HTML-Tags befreit; nur mit manage_options setzbar).
		$custom_css = (string) Settings::get( 'rcno_custom_styling', '' );

		if ( '' !== trim( $custom_css ) ) {
			wp_add_inline_style( 'mbr-public', wp_strip_all_tags( $custom_css ) );
		}
	}

	/**
	 * Filtert the_content und hängt die Box oben oder unten an.
	 *
	 * Auf Archiv-Seiten gilt die Legacy-Einstellung
	 * `rcno_reviews_archive`: „ganze Rezension" oder „nur ein Auszug"
	 * (Auszug wie im Original mit Cover und Weiterlesen-Link).
	 */
	public static function prepend_box( string $content ): string {
		global $post;

		if ( ! $post instanceof \WP_Post
			|| PostTypes::POST_TYPE !== $post->post_type
			|| ! in_the_loop()
			|| ! is_main_query()
			|| post_password_required( $post )
			|| is_feed() // Im Feed bewusst keine Box (Legacy-Verhalten).
		) {
			return $content;
		}

		// Archiv-Seiten: Auszug statt ganzer Rezension (wie Original,
		// unabhängig von der Rezensionsbox).
		if ( ! is_singular( PostTypes::POST_TYPE )
			&& 'archive_display_excerpt' === Settings::archive_mode()
		) {
			return self::archive_excerpt( $post, $content );
		}

		if ( '1' !== (string) Settings::get( 'mbr_show_review_box', '1' ) ) {
			return $content;
		}

		$box = self::render( (int) $post->ID );

		if ( '' === $box ) {
			return $content;
		}

		return 'bottom' === (string) Settings::get( 'mbr_review_box_position', 'top' )
			? $content . $box
			: $box . $content;
	}

	/**
	 * Archiv-Auszug: kleines Cover + gekürzter Text + Weiterlesen-Link
	 * (Legacy-Keys rcno_excerpt_word_count / rcno_excerpt_read_more).
	 */
	private static function archive_excerpt( \WP_Post $post, string $content ): string {
		wp_enqueue_style( 'mbr-public' );

		$word_count = (int) Settings::get( 'rcno_excerpt_word_count', 55 );
		$word_count = max( 20, min( 500, $word_count ) );
		$read_more  = (string) Settings::get( 'rcno_excerpt_read_more', 'Weiterlesen' );

		$excerpt = has_excerpt( $post->ID )
			? wp_strip_all_tags( (string) get_the_excerpt( $post->ID ) )
			: wp_trim_words( wp_strip_all_tags( $content ), $word_count );

		ob_start();
		?>
		<div class="mbr-archive-excerpt">
			<?php $cover = self::cover_html( (int) $post->ID ); ?>
			<?php if ( '' !== $cover ) : ?>
				<div class="mbr-archive-excerpt-cover">
					<a href="<?php echo esc_url( (string) get_permalink( $post->ID ) ); ?>">
						<?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput -- intern vollständig escaped. ?>
					</a>
				</div>
			<?php endif; ?>
			<div class="mbr-archive-excerpt-body">
				<h2 class="mbr-archive-excerpt-title">
					<a href="<?php echo esc_url( (string) get_permalink( $post->ID ) ); ?>">
						<?php echo esc_html( (string) get_the_title( $post->ID ) ); ?>
					</a>
				</h2>
				<p><?php echo esc_html( $excerpt ); ?></p>
				<p class="mbr-archive-excerpt-more">
					<a href="<?php echo esc_url( (string) get_permalink( $post->ID ) ); ?>">
						<?php echo esc_html( '' !== $read_more ? $read_more : __( 'Weiterlesen', 'modern-book-reviews' ) ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Rendert die Rezensionsbox für eine Rezension.
	 */
	public static function render( int $review_id ): string {
		if ( 0 === $review_id || PostTypes::POST_TYPE !== (string) get_post_type( $review_id ) ) {
			return '';
		}

		wp_enqueue_style( 'mbr-public' );

		$book_title = Meta::get_string( $review_id, 'rcno_book_title' );
		$book_title = '' !== $book_title ? $book_title : (string) get_the_title( $review_id );

		$rating = (float) Meta::get_string( $review_id, 'rcno_admin_rating' );
		$rating = ( $rating >= 0 && $rating <= 5 ) ? $rating : 0.0;

		$cover = self::cover_html( $review_id );
		$authors = self::term_list_html( $review_id, PostTypes::TAX_AUTHOR );

		ob_start();
		$template = sanitize_key( (string) Settings::get( 'rcno_review_template', 'default' ) );
		?>
		<section class="mbr-review-box mbr-template-<?php echo esc_attr( $template ); ?>" itemscope itemtype="https://schema.org/Book">
			<?php if ( self::score_box_at_top( $review_id ) ) : ?>
				<?php self::render_score_box( $review_id ); ?>
			<?php endif; ?>
			<div class="mbr-review-box-inner">
				<?php if ( '' !== $cover || $rating > 0 ) : ?>
					<div class="mbr-review-cover">
						<?php if ( '' !== $cover ) : ?>
							<?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput -- intern vollständig escaped. ?>
						<?php endif; ?>

						<?php if ( $rating > 0 && '1' === Settings::effective( 'rcno_enable_star_rating_box', 'mbr_star_rating_box', '1' ) ) : ?>
							<p class="mbr-review-rating mbr-review-rating-admin">
								<?php echo self::stars_html( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern vollständig escaped. ?>
							</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="mbr-review-details">
					<h2 class="mbr-review-book-title" itemprop="name"><?php echo esc_html( $book_title ); ?></h2>

					<?php if ( '' !== $authors ) : ?>
						<p class="mbr-review-authors">
							<span class="mbr-review-meta-label"><?php esc_html_e( 'Autor:in', 'modern-book-reviews' ); ?></span>
							<?php echo $authors; // Kernfunktion, escaped. ?>
						</p>
					<?php endif; ?>

					<?php $rows = self::detail_rows( $review_id ); ?>

					<?php foreach ( $rows as $row ) : ?>
						<?php if ( empty( $row['small'] ) ) : ?>
							<?php echo self::meta_row_html( $row ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern vollständig escaped. ?>
						<?php endif; ?>
					<?php endforeach; ?>

					<?php $description = self::book_description( $review_id ); ?>
					<?php if ( '' !== $description ) : ?>
						<div class="mbr-book-description">
							<?php echo esc_html( $description ); ?>
						</div>
					<?php endif; ?>

					<?php foreach ( $rows as $row ) : ?>
						<?php if ( ! empty( $row['small'] ) ) : ?>
							<?php echo self::meta_row_html( $row ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern vollständig escaped. ?>
						<?php endif; ?>
					<?php endforeach; ?>

					<?php if ( '1' === (string) Settings::get( 'rcno_enable_comment_ratings', '1' ) ) : ?>
						<?php $reader = CommentRating::aggregate( $review_id ); ?>
						<?php if ( null !== $reader && $reader['count'] > 0 ) : ?>
							<p class="mbr-review-rating mbr-review-rating-readers">
								<span class="mbr-review-meta-label"><?php esc_html_e( 'Leserbewertung', 'modern-book-reviews' ); ?></span>
								<?php echo self::stars_html( (float) $reader['average'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span class="mbr-review-rating-count">
									<?php
									printf(
										/* translators: 1: Anzahl Bewertungen, 2: Durchschnitt */
										esc_html__( '(%1$d Bewertungen, Ø %2$s)', 'modern-book-reviews' ),
										(int) $reader['count'],
										esc_html( (string) $reader['average'] )
									);
									?>
								</span>
							</p>
						<?php endif; ?>
					<?php endif; ?>

					<?php self::render_purchase_links( $review_id ); ?>
				</div>
			</div>

		<?php if ( ! self::score_box_at_top( $review_id ) ) : ?>
			<?php self::render_score_box( $review_id ); ?>
		<?php endif; ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Buchbeschreibung wie im Original-Plugin: HTML-Tags entfernen und
	 * auf 75 Wörter kürzen (Wortanzahl per Filter `mbr_book_description_word_count`
	 * anpassbar). Der Text wird beim Speichern bereits über die registrierte
	 * Meta-Sanitizing bereinigt und hier zusätzlich escaped.
	 */
	private static function book_description( int $review_id ): string {
		$description = Meta::get_string( $review_id, 'rcno_book_description' );

		if ( '' === trim( $description ) ) {
			return '';
		}

		$word_count  = max( 1, (int) apply_filters( 'mbr_book_description_word_count', 75 ) );
		$description = wp_trim_words( wp_strip_all_tags( $description ), $word_count );

		return trim( $description );
	}

	/**
	 * Öffentlicher Wrapper für Cover-Ausgabe (Shortcodes, Templates).
	 */
	public static function cover_markup( int $review_id ): string {
		return self::cover_html( $review_id );
	}

	/**
	 * Cover: Beitragsbild, sonst Legacy-Daten des alten Plugins
	 * (Attachment-ID bzw. gespeicherte Cover-URL).
	 */
	private static function cover_html( int $review_id ): string {
		if ( has_post_thumbnail( $review_id ) ) {
			return (string) get_the_post_thumbnail( $review_id, 'medium', array( 'class' => 'mbr-cover-image', 'itemprop' => 'image' ) );
		}

		$legacy_id = (int) get_post_meta( $review_id, 'rcno_reviews_book_cover_id', true );

		if ( $legacy_id > 0 ) {
			$image = wp_get_attachment_image(
				$legacy_id,
				'medium',
				false,
				array( 'class' => 'mbr-cover-image', 'itemprop' => 'image' )
			);

			if ( is_string( $image ) && '' !== $image ) {
				return $image;
			}
		}

		$legacy_src = Meta::get_string( $review_id, 'rcno_reviews_book_cover_src' );

		if ( '' === $legacy_src ) {
			$legacy_src = Meta::get_string( $review_id, 'rcno_reviews_book_cover_url' );
		}

		$src = esc_url_raw( $legacy_src );

		if ( '' === $src ) {
			// Standard-Cover aus den Einstellungen (Legacy: rcno_default_cover).
			$src = esc_url_raw( Settings::get( 'rcno_default_cover', '' ) );
		}

		if ( '' === $src ) {
			return '';
		}

		return sprintf(
			'<img class="mbr-cover-image mbr-cover-default" src="%s" alt="%s" itemprop="image" loading="lazy" />',
			esc_url( $src ),
			esc_attr( (string) get_the_title( $review_id ) )
		);
	}

	/**
	 * Eine Buchdetail-Zeile als HTML; Zeilen mit „small“-Flag
	 * (ISBN, ISBN-13, Buch-URL) erhalten die kleinere Darstellung.
	 *
	 * @param array{label: string, value: string, url?: bool, small?: bool} $row
	 */
	private static function meta_row_html( array $row ): string {
		$classes = 'mbr-review-meta' . ( empty( $row['small'] ) ? '' : ' mbr-review-meta-small' );
		$value   = (string) $row['value'];

		$rendered = ( ! empty( $row['url'] ) && '' !== esc_url_raw( $value ) )
			? sprintf(
				'<a href="%s" target="_blank" rel="nofollow noopener">%s</a>',
				esc_url( $value ),
				esc_html( $value )
			)
			: esc_html( $value );

		return sprintf(
			'<p class="%s"><span class="mbr-review-meta-label">%s</span><span class="mbr-review-meta-value">%s</span></p>',
			esc_attr( $classes ),
			esc_html( (string) $row['label'] ),
			$rendered
		);
	}

	/**
	 * Buchdetail-Zeilen. Sichtbarkeit folgt den Komponenten-Checkboxen
	 * (rcno_show_*), Reihenfolge und Auswahl zusätzlich dem Legacy-Key
	 * `rcno_book_details_meta` (kommaseparierte Meta-Keys), falls gesetzt.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	private static function detail_rows( int $review_id ): array {
		// label => array( flag, Quelle ).
		$values = array(
			'ISBN'                 => array( 'rcno_show_isbn', 'meta', 'rcno_book_isbn' ),
			'ISBN-13'              => array( 'rcno_show_isbn13', 'meta', 'rcno_book_isbn13' ),
			'ASIN'                 => array( 'rcno_show_asin', 'meta', 'rcno_book_asin' ),
			'Goodreads-ID'         => array( 'rcno_show_gr_id', 'meta', 'rcno_book_gr_id' ),
			'Buch-URL'             => array( 'rcno_show_gr_url', 'meta', 'rcno_book_gr_url' ),
			'Illustration'         => array( 'rcno_show_illustrator', 'meta', 'rcno_book_illustrator' ),
			'Erschienen'           => array( 'rcno_show_pub_date', 'meta', 'rcno_book_pub_date' ),
			'Format'               => array( 'rcno_show_pub_format', 'meta', 'rcno_book_pub_format' ),
			'Ausgabe'              => array( 'rcno_show_pub_edition', 'meta', 'rcno_book_pub_edition' ),
			'Band'                 => array( 'rcno_show_series_number', 'meta', 'rcno_book_series_number' ),
			'Seiten'               => array( 'rcno_show_page_count', 'meta', 'rcno_book_page_count' ),
			'Goodreads-Bewertung'  => array( 'rcno_show_gr_rating', 'meta', 'rcno_book_gr_rating' ),
			'Genre'                => array( null, 'taxonomy', PostTypes::TAX_GENRE ),
			'Verlag'               => array( null, 'taxonomy', PostTypes::TAX_PUBLISHER ),
			'Serie'                => array( null, 'taxonomy', PostTypes::TAX_SERIES ),
		);

		// Komponenten-Checkboxen filtern.
		$visible = array();

		foreach ( $values as $label => $source ) {
			if ( null === $source[0] || Settings::component_enabled( (string) $source[0] ) ) {
				$visible[ $label ] = $source;
			}
		}

		// Meta-/Taxonomie-Zuordnung für die Reihenfolge aus
		// rcno_book_details_meta (wie im Original inkl. Taxonomie-Keys).
		$meta_map = array();

		foreach ( $visible as $label => $source ) {
			$meta_map[ 'meta' === $source[1] ? (string) $source[2] : 'taxonomy:' . (string) $source[2] ] = $label;
		}

		// Taxonomie-Keys des Original-Plugins ohne Präfix (z. B. „rcno_series“)
		// sowie Kurzformen („genre“, „serie“) werden ebenfalls erkannt.
		$taxonomy_aliases = array(
			PostTypes::TAX_GENRE     => 'taxonomy:' . PostTypes::TAX_GENRE,
			PostTypes::TAX_PUBLISHER => 'taxonomy:' . PostTypes::TAX_PUBLISHER,
			PostTypes::TAX_SERIES    => 'taxonomy:' . PostTypes::TAX_SERIES,
			'genre'                   => 'taxonomy:' . PostTypes::TAX_GENRE,
			'serie'                   => 'taxonomy:' . PostTypes::TAX_SERIES,
			'series'                  => 'taxonomy:' . PostTypes::TAX_SERIES,
			'publisher'               => 'taxonomy:' . PostTypes::TAX_PUBLISHER,
		);

		$details_meta = trim( wp_strip_all_tags( (string) Settings::get( 'rcno_book_details_meta', '' ) ) );

		if ( '' !== $details_meta ) {
			$ordered = array();

			foreach ( explode( ',', $details_meta ) as $meta_key ) {
				$meta_key = sanitize_key( trim( $meta_key ) );

				if ( isset( $meta_map[ $meta_key ] ) ) {
					$ordered[ $meta_map[ $meta_key ] ] = $visible[ $meta_map[ $meta_key ] ];
					continue;
				}

				if ( isset( $taxonomy_aliases[ $meta_key ], $meta_map[ $taxonomy_aliases[ $meta_key ] ] ) ) {
					$lookup = $taxonomy_aliases[ $meta_key ];
					$ordered[ $meta_map[ $lookup ] ] = $visible[ $meta_map[ $lookup ] ];
				}
			}

			// Felder, die in der Reihenfolge-Angabe fehlen (z. B. die
			// Buch-URL in Alt-Installationen), werden nicht länger
			// ausgeblendet, sondern am Ende ergänzt – der alte Plugin-Bug,
			// bei dem der Buch-Link nie erschien, ist damit behoben.
			foreach ( $visible as $label => $source ) {
				if ( ! isset( $ordered[ $label ] ) ) {
					$ordered[ $label ] = $source;
				}
			}

			$visible = $ordered;
		}

		$rows = array();

		// Eigene Metadaten-Felder (Erweiterung): am Ende ergänzen.
		foreach ( Extensions::custom_fields() as $field ) {
			$visible[ $field['label'] ] = array( null, 'meta', $field['key'] );
		}

		foreach ( $visible as $label => $source ) {
			$value = 'taxonomy' === $source[1]
				? self::terms_string( $review_id, (string) $source[2] )
				: Meta::get_string( $review_id, (string) $source[2] );

			if ( '' !== trim( (string) $value ) ) {
				$rows[] = array(
					'label' => (string) $label,
					'value' => (string) $value,
					// Buch-URL wie im Original als klickbaren Link ausgeben.
					'url'   => 'meta' === $source[1] && 'rcno_book_gr_url' === (string) $source[2],
					// ISBN, ISBN-13 und Buch-URL erscheinen kleiner unter
					// der Beschreibung statt oberhalb.
					'small' => 'meta' === $source[1] && in_array(
						(string) $source[2],
						array( 'rcno_book_isbn', 'rcno_book_isbn13', 'rcno_book_gr_url' ),
						true
					),
				);
			}
		}

		return $rows;
	}

	/**
	 * Term-Link-Liste als HTML-String; WP_Error wird sicher abgefangen.
	 */
	private static function term_list_html( int $review_id, string $taxonomy ): string {
		$list = get_the_term_list( $review_id, $taxonomy, '', ', ' );

		return is_string( $list ) ? $list : '';
	}

	/**
	 * Term-Namen als kommaseparierter String (nur Namen, kein HTML).
	 */
	private static function terms_string( int $review_id, string $taxonomy ): string {
		$terms = get_the_terms( $review_id, $taxonomy );

		if ( ! is_array( $terms ) ) {
			return '';
		}

		$names = array_map(
			static fn( \WP_Term $term ): string => $term->name,
			$terms
		);

		return implode( ', ', $names );
	}

	/**
	 * Sterne-Ausgabe (0–5, halbe Sterne möglich).
	 */
	public static function stars_html( float $rating ): string {
		$rating = max( 0.0, min( 5.0, $rating ) );
		$percent = (int) round( $rating * 20 );

		return sprintf(
			'<span class="mbr-stars" role="img" aria-label="%s"><span class="mbr-stars-fill" style="width:%d%%"></span></span>',
			esc_attr( sprintf( /* translators: 1: Bewertung */ __( 'Bewertung: %s von 5', 'modern-book-reviews' ), number_format_i18n( $rating, 1 ) ) ),
			$percent
		);
	}

	/**
	 * Kauft-/Affiliate-Links. Links sind beim Speichern bereits über
	 * esc_url_raw bereinigt; hier erfolgt zusätzlich esc_url beim
	 * Attribut-Rendering (defense in depth) plus rel="nofollow noopener".
	 */
	private static function render_purchase_links( int $review_id ): void {
		if ( '1' !== Settings::effective( 'rcno_enable_purchase_links', 'mbr_enable_purchase_links', '1' ) ) {
			return;
		}

		$links = Meta::get_buy_links( $review_id );

		if ( array() === $links ) {
			return;
		}

		$label = Settings::effective( 'rcno_store_purchase_links_label', 'mbr_purchase_links_label', 'Kaufen bei:' );
		?>
		<div class="mbr-purchase-links">
			<?php if ( '' !== $label ) : ?>
				<span class="mbr-review-meta-label"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
			<?php foreach ( $links as $link ) : ?>
				<a class="mbr-purchase-button"
					href="<?php echo esc_url( $link['link'] ); ?>"
					target="_blank"
					rel="nofollow sponsored noopener">
					<?php
				// Slug-Shops („amazon“) ansehnlich ausgeben, freie
				// Shop-Namen unverändert lassen.
				$store = $link['store'];
				$store = '' !== $store
					? ucwords( str_replace( '-', ' ', $store ) )
					: (string) wp_parse_url( $link['link'], PHP_URL_HOST );
				?>
				<?php echo esc_html( $store ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Bewertungskriterien-Box (Noten oder Zahlen).
	 */
	private static function render_score_box( int $review_id ): void {
		if ( '1' !== Settings::effective( 'rcno_show_review_score_box', 'mbr_enable_score_box', '1' )
			|| '1' !== Meta::get_string( $review_id, 'rcno_review_score_enable' )
		) {
			return;
		}

		$criteria = Meta::get_criteria( $review_id );

		if ( array() === $criteria ) {
			return;
		}

		$type = Meta::get_string( $review_id, 'rcno_review_score_type' );
		$type = in_array( $type, array( 'letter', 'stars' ), true ) ? $type : 'number';
		?>
		<div class="mbr-score-box">
			<?php foreach ( $criteria as $criterion ) : ?>
				<div class="mbr-score-item">
					<span class="mbr-score-label"><?php echo esc_html( $criterion['label'] ); ?></span>
					<span class="mbr-score-value mbr-score-<?php echo esc_attr( $type ); ?>">
						<?php if ( 'stars' === $type ) : ?>
							<?php echo self::stars_html( (float) $criterion['score'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern vollständig escaped. ?>
						<?php else : ?>
							<?php echo esc_html( $criterion['score'] ); ?>
						<?php endif; ?>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Positioniert die Bewertungskriterien-Box („Bewertung der
	 * Besprechung“) gemäß rcno_review_score_position oben oder unten
	 * innerhalb der Rezensionsbox.
	 */
	private static function score_box_at_top( int $review_id ): bool {
		return 'top' === Meta::get_string( $review_id, 'rcno_review_score_position' );
	}
}
