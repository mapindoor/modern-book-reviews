<?php
/**
 * Widgets mit Legacy-Flags des Original-Plugins.
 *
 * Fünf Widgets: Neueste Rezensionen, Buchraster, Buch-Slider,
 * Schlagwort-Wolke, Taxonomie-Liste. Jedes Widget wird nur registriert,
 * wenn der zugehörige Legacy-Schalter (rcno_show_*_widget) aktiv ist.
 *
 * Nicht umgesetzt (bewusst): „Currently Reading"- und Kalender-Widget.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

/**
 * Neueste Rezensionen (Legacy: rcno_show_recent_reviews_widget).
 */
final class Recent_Reviews_Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'mbr_recent_reviews',
			__( 'Neueste Rezensionen (Modern Book Reviews)', 'modern-book-reviews' ),
			array( 'description' => __( 'Liste der zuletzt veröffentlichten Rezensionen.', 'modern-book-reviews' ) )
		);
	}

	/**
	 * @param array<string, string> $args
	 * @param array<string, mixed> $instance
	 */
	public function widget( $args, $instance ): void {
		$title      = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );
		$count      = max( 1, min( 20, absint( $instance['count'] ?? 5 ) ) );
		$show_rating = ! empty( $instance['show_rating'] );

		$query = new \WP_Query(
			array(
				'post_type'           => PostTypes::POST_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => $count,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}

		wp_enqueue_style( 'mbr-public' );

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput -- Theme-Markup.

		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '<ul class="mbr-widget-recent-reviews">';

		foreach ( $query->posts as $review ) {
			$review_id = (int) $review->ID;

			echo '<li class="mbr-widget-review-item">';

			printf(
				'<a class="mbr-widget-review-link" href="%s">%s</a>',
				esc_url( (string) get_permalink( $review_id ) ),
				esc_html( (string) get_the_title( $review_id ) )
			);

			if ( $show_rating ) {
				$score = Meta::get_string( $review_id, 'rcno_admin_rating' );

				if ( '' !== $score && 0.0 < (float) $score ) {
					echo '<span class="mbr-widget-review-rating">' . ReviewBox::stars_html( (float) $score ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
			}

			echo '</li>';
		}

		echo '</ul>';
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

		wp_reset_postdata();
	}

	/**
	 * @param array<string, mixed> $instance
	 * @return string
	 */
	public function form( $instance ): string {
		$title       = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );
		$count       = max( 1, min( 20, absint( $instance['count'] ?? 5 ) ) );
		$show_rating = ! empty( $instance['show_rating'] );

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'title' ) ), esc_html__( 'Titel:', 'modern-book-reviews' ) );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);
		echo '</p>';

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'count' ) ), esc_html__( 'Anzahl:', 'modern-book-reviews' ) );
		printf(
			'<input class="tiny-text" id="%s" name="%s" type="number" min="1" max="20" value="%d" />',
			esc_attr( $this->get_field_id( 'count' ) ),
			esc_attr( $this->get_field_name( 'count' ) ),
			$count
		);
		echo '</p>';

		echo '<p>';
		printf(
			'<label><input type="checkbox" name="%s" value="1"%s /> %s</label>',
			esc_attr( $this->get_field_name( 'show_rating' ) ),
			$show_rating ? ' checked' : '',
			esc_html__( 'Sternebewertung anzeigen', 'modern-book-reviews' )
		);
		echo '</p>';

		return '';
	}

	/**
	 * @param array<string, mixed> $new_instance
	 * @param array<string, mixed> $old_instance
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title'       => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'count'       => max( 1, min( 20, absint( $new_instance['count'] ?? 5 ) ) ),
			'show_rating' => empty( $new_instance['show_rating'] ) ? 0 : 1,
		);
	}
}

/**
 * Buchraster (Legacy: rcno_show_book_grid_widget).
 */
final class Book_Grid_Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'mbr_book_grid',
			__( 'Buchraster (Modern Book Reviews)', 'modern-book-reviews' ),
			array( 'description' => __( 'Cover-Raster der neuesten Rezensionen.', 'modern-book-reviews' ) )
		);
	}

	/**
	 * @param array<string, string> $args
	 * @param array<string, mixed> $instance
	 */
	public function widget( $args, $instance ): void {
		$title = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );
		$count = max( 1, min( 24, absint( $instance['count'] ?? 8 ) ) );

		$html = Shortcodes::reviews_grid(
			array(
				'count'   => $count,
				'columns' => max( 1, min( 6, absint( $instance['columns'] ?? 4 ) ) ),
			)
		);

		if ( '' === $html ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- Shortcode-Helper, intern escaped.

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * @param array<string, mixed> $instance
	 * @return string
	 */
	public function form( $instance ): string {
		$title   = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );
		$count   = max( 1, min( 24, absint( $instance['count'] ?? 8 ) ) );
		$columns = max( 1, min( 6, absint( $instance['columns'] ?? 4 ) ) );

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'title' ) ), esc_html__( 'Titel:', 'modern-book-reviews' ) );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);
		echo '</p>';

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'count' ) ), esc_html__( 'Anzahl:', 'modern-book-reviews' ) );
		printf(
			'<input class="tiny-text" id="%s" name="%s" type="number" min="1" max="24" value="%d" />',
			esc_attr( $this->get_field_id( 'count' ) ),
			esc_attr( $this->get_field_name( 'count' ) ),
			$count
		);
		echo ' ';

		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'columns' ) ), esc_html__( 'Spalten:', 'modern-book-reviews' ) );
		printf(
			'<input class="tiny-text" id="%s" name="%s" type="number" min="1" max="6" value="%d" />',
			esc_attr( $this->get_field_id( 'columns' ) ),
			esc_attr( $this->get_field_name( 'columns' ) ),
			$columns
		);
		echo '</p>';

		return '';
	}

	/**
	 * @param array<string, mixed> $new_instance
	 * @param array<string, mixed> $old_instance
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title'   => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'count'   => max( 1, min( 24, absint( $new_instance['count'] ?? 8 ) ) ),
			'columns' => max( 1, min( 6, absint( $new_instance['columns'] ?? 4 ) ) ),
		);
	}
}

/**
 * Buch-Slider (Legacy: rcno_show_book_slider_widget).
 */
final class Book_Slider_Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'mbr_book_slider',
			__( 'Buch-Slider (Modern Book Reviews)', 'modern-book-reviews' ),
			array( 'description' => __( 'Reihe von Buch-Covern zum horizontalen Scrollen.', 'modern-book-reviews' ) )
		);
	}

	/**
	 * @param array<string, string> $args
	 * @param array<string, mixed> $instance
	 */
	public function widget( $args, $instance ): void {
		$title = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );
		$count = max( 3, min( 24, absint( $instance['count'] ?? 8 ) ) );

		$query = new \WP_Query(
			array(
				'post_type'           => PostTypes::POST_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => $count,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}

		wp_enqueue_style( 'mbr-public' );

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '<div class="mbr-book-slider">';

		foreach ( $query->posts as $review ) {
			$review_id = (int) $review->ID;

			echo '<a class="mbr-slider-item" href="' . esc_url( (string) get_permalink( $review_id ) ) . '">';

			$cover = ReviewBox::cover_markup( $review_id );

			if ( '' !== $cover ) {
				echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput -- interne Helper, escaped.
			}

			echo '<span class="mbr-slider-title">' . esc_html( (string) get_the_title( $review_id ) ) . '</span>';
			echo '</a>';
		}

		echo '</div>';
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

		wp_reset_postdata();
	}

	/**
	 * @param array<string, mixed> $instance
	 * @return string
	 */
	public function form( $instance ): string {
		$title = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );
		$count = max( 3, min( 24, absint( $instance['count'] ?? 8 ) ) );

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'title' ) ), esc_html__( 'Titel:', 'modern-book-reviews' ) );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);
		echo '</p>';

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'count' ) ), esc_html__( 'Anzahl:', 'modern-book-reviews' ) );
		printf(
			'<input class="tiny-text" id="%s" name="%s" type="number" min="3" max="24" value="%d" />',
			esc_attr( $this->get_field_id( 'count' ) ),
			esc_attr( $this->get_field_name( 'count' ) ),
			$count
		);
		echo '</p>';

		return '';
	}

	/**
	 * @param array<string, mixed> $new_instance
	 * @param array<string, mixed> $old_instance
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title' => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'count' => max( 3, min( 24, absint( $new_instance['count'] ?? 8 ) ) ),
		);
	}
}

/**
 * Schlagwort-Wolke (Legacy: rcno_show_tag_cloud_widget).
 */
final class Tag_Cloud_Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'mbr_tag_cloud',
			__( 'Schlagwort-Wolke (Modern Book Reviews)', 'modern-book-reviews' ),
			array( 'description' => __( 'Schlagwörter der Rezensionen als Wolke.', 'modern-book-reviews' ) )
		);
	}

	/**
	 * @param array<string, string> $args
	 * @param array<string, mixed> $instance
	 */
	public function widget( $args, $instance ): void {
		$title = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );

		$cloud = wp_tag_cloud(
			array(
				'taxonomy'   => 'post_tag',
				'echo'       => false,
				'show_count' => false,
			)
		);

		if ( ! is_string( $cloud ) || '' === trim( $cloud ) ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '<div class="mbr-widget-tag-cloud mbr-tagcloud">' . $cloud . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * @param array<string, mixed> $instance
	 * @return string
	 */
	public function form( $instance ): string {
		$title = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'title' ) ), esc_html__( 'Titel:', 'modern-book-reviews' ) );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);
		echo '</p>';

		return '';
	}

	/**
	 * @param array<string, mixed> $new_instance
	 * @param array<string, mixed> $old_instance
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title' => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
		);
	}
}

/**
 * Taxonomie-Liste (Legacy: rcno_show_taxonomy_list_widget).
 */
final class Taxonomy_List_Widget extends \WP_Widget {

	public function __construct() {
		parent::__construct(
			'mbr_taxonomy_list',
			__( 'Taxonomie-Liste (Modern Book Reviews)', 'modern-book-reviews' ),
			array( 'description' => __( 'Liste der Autoren, Genres, Serien oder Verlage.', 'modern-book-reviews' ) )
		);
	}

	/**
	 * @return array<string, string> Verfügbare Taxonomien.
	 */
	private function taxonomy_choices(): array {
		return array(
			PostTypes::TAX_AUTHOR    => __( 'Autoren', 'modern-book-reviews' ),
			PostTypes::TAX_GENRE     => __( 'Genres', 'modern-book-reviews' ),
			PostTypes::TAX_SERIES    => __( 'Serien', 'modern-book-reviews' ),
			PostTypes::TAX_PUBLISHER => __( 'Verlage', 'modern-book-reviews' ),
		);
	}

	/**
	 * @param array<string, string> $args
	 * @param array<string, mixed> $instance
	 */
	public function widget( $args, $instance ): void {
		$title     = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );
		$taxonomy  = sanitize_key( (string) ( $instance['taxonomy'] ?? PostTypes::TAX_AUTHOR ) );
		$showcount = ! empty( $instance['show_count'] );

		$choices = $this->taxonomy_choices();

		if ( ! isset( $choices[ $taxonomy ] ) ) {
			$taxonomy = PostTypes::TAX_AUTHOR;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'number'     => 100,
			)
		);

		if ( is_wp_error( $terms ) || array() === $terms ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput

		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '<ul class="mbr-widget-taxonomy-list">';

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			echo '<li class="mbr-widget-taxonomy-item"><a href="' . esc_url( (string) $link ) . '">' . esc_html( Settings::format_author_name( (string) $term->name ) ) . '</a>';

			if ( $showcount ) {
				printf( ' <span class="mbr-widget-count">(%d)</span>', (int) $term->count );
			}

			echo '</li>';
		}

		echo '</ul>';
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * @param array<string, mixed> $instance
	 * @return string
	 */
	public function form( $instance ): string {
		$title     = sanitize_text_field( (string) ( $instance['title'] ?? '' ) );
		$taxonomy  = sanitize_key( (string) ( $instance['taxonomy'] ?? PostTypes::TAX_AUTHOR ) );
		$showcount = ! empty( $instance['show_count'] );

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'title' ) ), esc_html__( 'Titel:', 'modern-book-reviews' ) );
		printf(
			'<input class="widefat" id="%s" name="%s" type="text" value="%s" />',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);
		echo '</p>';

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'taxonomy' ) ), esc_html__( 'Taxonomie:', 'modern-book-reviews' ) );
		printf( '<select class="widefat" id="%s" name="%s">', esc_attr( $this->get_field_id( 'taxonomy' ) ), esc_attr( $this->get_field_name( 'taxonomy' ) ) );

		foreach ( $this->taxonomy_choices() as $key => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				$taxonomy === $key ? ' selected' : '',
				esc_html( $label )
			);
		}

		echo '</select></p>';

		echo '<p>';
		printf(
			'<label><input type="checkbox" name="%s" value="1"%s /> %s</label>',
			esc_attr( $this->get_field_name( 'show_count' ) ),
			$showcount ? ' checked' : '',
			esc_html__( 'Anzahl anzeigen', 'modern-book-reviews' )
		);
		echo '</p>';

		return '';
	}

	/**
	 * @param array<string, mixed> $new_instance
	 * @param array<string, mixed> $old_instance
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		$taxonomy = sanitize_key( (string) ( $new_instance['taxonomy'] ?? PostTypes::TAX_AUTHOR ) );

		if ( ! isset( $this->taxonomy_choices()[ $taxonomy ] ) ) {
			$taxonomy = PostTypes::TAX_AUTHOR;
		}

		return array(
			'title'      => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'taxonomy'   => $taxonomy,
			'show_count' => empty( $new_instance['show_count'] ) ? 0 : 1,
		);
	}
}

/**
 * Registrierung aller Widgets, gesteuert über die Legacy-Schalter.
 */
final class Widgets {

	private const FLAG_MAP = array(
		'rcno_show_recent_reviews_widget' => Recent_Reviews_Widget::class,
		'rcno_show_book_grid_widget'      => Book_Grid_Widget::class,
		'rcno_show_book_slider_widget'    => Book_Slider_Widget::class,
		'rcno_show_tag_cloud_widget'      => Tag_Cloud_Widget::class,
		'rcno_show_taxonomy_list_widget'  => Taxonomy_List_Widget::class,
	);

	/**
	 * Hängt die widgets_init-Registrierung ein.
	 */
	public static function register(): void {
		add_action( 'widgets_init', array( __CLASS__, 'register_widgets' ) );
	}

	/**
	 * Registriert nur die aktivierten Widgets.
	 */
	public static function register_widgets(): void {
		foreach ( self::FLAG_MAP as $flag => $class ) {
			if ( Settings::widget_enabled( $flag ) ) {
				register_widget( $class );
			}
		}
	}
}
