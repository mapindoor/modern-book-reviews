<?php
/**
 * Besucher-Bewertung über das Kommentarformular.
 *
 * Sicherheitsmodell (Unterschied zum alten Plugin, das per AJAX ohne
 * Ownership-Prüfung beliebige Kommentare bewerten konnte):
 * - Die Bewertung ist Teil des normalen Kommentar-Formulars und läuft
 *   vollständig über den WordPress-Comment-Flow.
 * - Eigener Nonce-Schutz; fehlt der Nonce, wird keine Bewertung gespeichert.
 * - Nur ganze Zahlen 1–5, nur auf Rezensions-Beiträgen.
 * - Meta-Key identisch zum alten Plugin (`rcno_review_comment_rating`),
 *   bestehende Besucher-Bewertungen bleiben also erhalten.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class CommentRating {

	public const META_KEY = 'rcno_review_comment_rating';
	private const NONCE_ACTION = 'mbr_comment_rating';
	private const NONCE_FIELD  = 'mbr_comment_rating_nonce';
	private const RATING_FIELD = 'mbr_comment_rating';

	/**
	 * Registriert die Formular- und Speicher-Hooks.
	 */
	public static function register(): void {
		add_action( 'comment_form_after_fields', array( __CLASS__, 'rating_field' ) );
		add_action( 'comment_form_logged_in_after', array( __CLASS__, 'rating_field' ) );
	}

	/**
	 * Rendert das Sterne-Auswahlfeld im Kommentarformular.
	 */
	public static function rating_field(): void {
		$post = get_post();

		if ( ! $post instanceof \WP_Post || PostTypes::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( '1' !== (string) Settings::get( 'rcno_enable_comment_ratings', '1' ) ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$label = Settings::effective( 'rcno_comment_rating_label', 'mbr_comment_rating_label', 'Bewerte dieses Buch' );
		?>
		<p class="mbr-comment-rating">
			<label class="mbr-comment-rating-label" for="mbr-rating-5">
				<?php echo esc_html( $label ); ?>
			</label>
			<span class="mbr-comment-rating-stars">
				<?php for ( $star = 5; $star >= 1; $star-- ) : ?>
					<input type="radio"
						id="mbr-rating-<?php echo (int) $star; ?>"
						name="<?php echo esc_attr( self::RATING_FIELD ); ?>"
						value="<?php echo (int) $star; ?>" />
					<label for="mbr-rating-<?php echo (int) $star; ?>" title="<?php echo (int) $star; ?>">
						<?php echo esc_html( str_repeat( '★', (int) $star ) ); ?>
					</label>
				<?php endfor; ?>
			</span>
		</p>
		<?php
	}

	/**
	 * Speichert die Bewertung nach dem Kommentar (Comment-Flow von WordPress:
	 * Nonces und Anti-Spam von Core werden bereits verarbeitet).
	 */
	public static function save( int $comment_id, int $comment_approved, array $commentdata ): void {
		$post_id = (int) ( $commentdata['comment_post_ID'] ?? 0 );

		if ( 0 === $post_id || PostTypes::POST_TYPE !== (string) get_post_type( $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$raw_rating = isset( $_POST[ self::RATING_FIELD ] ) ? (int) wp_unslash( $_POST[ self::RATING_FIELD ] ) : 0;

		if ( $raw_rating < 1 || $raw_rating > 5 ) {
			return;
		}

		add_comment_meta( $comment_id, self::META_KEY, $raw_rating, true );
	}

	/**
	 * Zeigt die Sterne eines bewerteten Kommentars an.
	 */
	public static function display( string $comment_text, \WP_Comment $comment ): string {
		$rating = (int) get_comment_meta( (int) $comment->comment_ID, self::META_KEY, true );

		if ( $rating < 1 || $rating > 5 ) {
			return $comment_text;
		}

		wp_enqueue_style( 'mbr-public' );

		$stars = sprintf(
			'<p class="mbr-comment-rating-result">%s %s</p>',
			ReviewBox::stars_html( (float) $rating ),
			esc_html(
				sprintf(
					/* translators: 1: Bewertung */
					__( '%d von 5', 'modern-book-reviews' ),
					$rating
				)
			)
		);

		return $stars . $comment_text;
	}

	/**
	 * Aggregiert alle Besucher-Bewertungen einer Rezension.
	 *
	 * @return array{count: int, average: float}|null
	 */
	public static function aggregate( int $review_id ): ?array {
		$comments = get_comments(
			array(
				'post_id'  => $review_id,
				'status'   => 'approve',
				'meta_key' => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery -- begrenzte Menge, Caching empfohlen.
				'number'   => 200,
			)
		);

		$total = 0;
		$count = 0;

		foreach ( $comments as $comment ) {
			$rating = (int) get_comment_meta( (int) $comment->comment_ID, self::META_KEY, true );

			if ( $rating >= 1 && $rating <= 5 ) {
				$total += $rating;
				$count++;
			}
		}

		if ( 0 === $count ) {
			return null;
		}

		return array(
			'count'   => $count,
			'average' => round( $total / $count, 1 ),
		);
	}
}
