<?php
/**
 * Klassische Metaboxen im Rezensions-Editor – gleiche Anordnung,
 * Titel und Felder wie das Original-Plugin (Recencio Book Reviews):
 *
 *  - Normal/hohe Priorität: „Buchbeschreibung/Synopse“,
 *    „Allgemeine Information“, „Bewertung der Besprechung“
 *  - Normal: „Kauflinks“
 *  - Seitenleiste: „ISBN“ (inkl. Abruf-Button), „Buchdeckel“
 *    (Mediathek + eigene URL), „5-Sterne-Bewertung“
 *
 * Gespeichert wird in dieselben Meta-Keys, die auch über die REST-API
 * registriert sind (Meta::register); damit bleiben Gutenberg-Daten,
 * Legacy-Daten und Frontend kompatibel.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class AdminMetaboxes {

	private const NONCE_ACTION = 'mbr_save_metaboxes';
	private const NONCE_NAME   = 'mbr_metaboxes_nonce';

	/**
	 * Verdrahtet Metaboxen, Speicherung und Assets.
	 */
	public static function register(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metaboxes' ) );
		add_action( 'save_post_' . PostTypes::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Registriert alle Metaboxen in Original-Anordnung.
	 */
	public static function add_metaboxes(): void {
		add_meta_box(
			'mbr_book_description_metabox',
			__( 'Buchbeschreibung/Synopse', 'modern-book-reviews' ),
			array( __CLASS__, 'render_description' ),
			PostTypes::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'mbr_general_info_metabox',
			__( 'Allgemeine Information', 'modern-book-reviews' ),
			array( __CLASS__, 'render_general_info' ),
			PostTypes::POST_TYPE,
			'normal',
			'high'
		);

		if ( Settings::component_enabled( 'rcno_show_review_score_box' ) ) {
			add_meta_box(
				'mbr_review_score_metabox',
				__( 'Bewertung der Besprechung', 'modern-book-reviews' ),
				array( __CLASS__, 'render_review_score' ),
				PostTypes::POST_TYPE,
				'normal',
				'high'
			);
		}

		if ( '1' === Settings::effective( 'rcno_enable_purchase_links', 'mbr_enable_purchase_links', '1' ) ) {
			add_meta_box(
				'mbr_buy_links_metabox',
				__( 'Kauflinks', 'modern-book-reviews' ),
				array( __CLASS__, 'render_buy_links' ),
				PostTypes::POST_TYPE,
				'normal',
				'default'
			);
		}

		add_meta_box(
			'mbr_isbn_metabox',
			__( 'ISBN', 'modern-book-reviews' ),
			array( __CLASS__, 'render_isbn' ),
			PostTypes::POST_TYPE,
			'side',
			'high'
		);

		add_meta_box(
			'mbr_book_cover_metabox',
			__( 'Buchdeckel', 'modern-book-reviews' ),
			array( __CLASS__, 'render_book_cover' ),
			PostTypes::POST_TYPE,
			'side',
			'high'
		);

		if ( '1' === Settings::effective( 'rcno_enable_star_rating_box', 'mbr_star_rating_box', '1' ) ) {
			add_meta_box(
				'mbr_review_rating_metabox',
				__( '5-Sterne-Bewertung', 'modern-book-reviews' ),
				array( __CLASS__, 'render_review_rating' ),
				PostTypes::POST_TYPE,
				'side',
				'default'
			);
		}
	}

	/**
	 * Assets nur im Rezensions-Editor laden.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen instanceof \WP_Screen || PostTypes::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'mbr-admin-metaboxes',
			MBR_PLUGIN_URL . 'assets/admin/metaboxes.css',
			array(),
			MBR_VERSION
		);

		wp_enqueue_script(
			'mbr-admin-metaboxes',
			MBR_PLUGIN_URL . 'assets/admin/metaboxes.js',
			array(),
			MBR_VERSION,
			true
		);

		wp_localize_script(
			'mbr-admin-metaboxes',
			'MBR_ADMIN',
			array(
				'restUrl' => esc_url_raw( rest_url( IsbnLookup::REST_NAMESPACE ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'stores'  => self::store_options(),
			)
		);
	}

	/**
	 * Shop-Optionen (Slug => Name) wie im Original.
	 *
	 * @return array<string, string>
	 */
	private static function store_options(): array {
		$names  = Settings::split_list( 'rcno_store_purchase_links', 'Amazon,Barnes & Noble,Kobo' );
		$stores = array();

		foreach ( $names as $name ) {
			$stores[ sanitize_title( $name ) ] = $name;
		}

		return $stores;
	}

	/* ---------------- render: Beschreibung ---------------- */

	public static function render_description( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$description = Meta::get_string( $post->ID, 'rcno_book_description' );
		$screen      = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_block    = $screen && $screen->is_block_editor();

		if ( $is_block ) {
			// Im Block-Editor (wie im Original) schlichte Textarea ohne TinyMCE.
			printf(
				'<textarea class="widefat" rows="8" name="rcno_book_description" id="rcno_book_description">%s</textarea>',
				esc_textarea( $description )
			);
			return;
		}

		wp_editor(
			$description,
			'rcno_book_description',
			array(
				'textarea_rows' => 8,
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => false,
			)
		);
	}

	/* ---------------- render: Allgemeine Information ---------------- */

	/**
	 * Label/Slug-Klassen des Original-Layouts.
	 *
	 * @return array<int, array{key: string, label: string, flag: string|null, class: string}>
	 */
	private static function general_fields(): array {
		return array(
			array( 'key' => 'rcno_book_title', 'label' => __( 'Titel', 'modern-book-reviews' ), 'flag' => null, 'class' => 'book-title' ),
			array( 'key' => 'rcno_book_illustrator', 'label' => __( 'Illustrator', 'modern-book-reviews' ), 'flag' => 'rcno_show_illustrator', 'class' => 'illustrator-info' ),
			array( 'key' => 'rcno_book_pub_date', 'label' => __( 'Veröffentlichungsdatum', 'modern-book-reviews' ), 'flag' => 'rcno_show_pub_date', 'class' => 'publication-date' ),
			array( 'key' => 'rcno_book_pub_format', 'label' => __( 'Format', 'modern-book-reviews' ), 'flag' => 'rcno_show_pub_format', 'class' => 'publication-format' ),
			array( 'key' => 'rcno_book_pub_edition', 'label' => __( 'Ausgabe', 'modern-book-reviews' ), 'flag' => 'rcno_show_pub_edition', 'class' => 'publication-edition' ),
			array( 'key' => 'rcno_book_series_number', 'label' => __( 'Band', 'modern-book-reviews' ), 'flag' => 'rcno_show_series_number', 'class' => 'publication-series-number' ),
			array( 'key' => 'rcno_book_page_count', 'label' => __( 'Seitenzahl', 'modern-book-reviews' ), 'flag' => 'rcno_show_page_count', 'class' => 'publication-page-count' ),
			array( 'key' => 'rcno_book_gr_id', 'label' => __( 'GoodReads ID', 'modern-book-reviews' ), 'flag' => 'rcno_show_gr_id', 'class' => 'publication-gr-id' ),
			array( 'key' => 'rcno_book_isbn13', 'label' => __( 'ISBN13', 'modern-book-reviews' ), 'flag' => 'rcno_show_isbn13', 'class' => 'publication-isbn13' ),
			array( 'key' => 'rcno_book_asin', 'label' => __( 'ASIN', 'modern-book-reviews' ), 'flag' => 'rcno_show_asin', 'class' => 'publication-asin' ),
			array( 'key' => 'rcno_book_gr_rating', 'label' => __( 'Durchschnitt', 'modern-book-reviews' ), 'flag' => 'rcno_show_gr_rating', 'class' => 'publication-gr-review' ),
			array( 'key' => 'rcno_book_gr_url', 'label' => __( 'Buch-URL', 'modern-book-reviews' ), 'flag' => 'rcno_show_gr_url', 'class' => 'publication-gr-url' ),
		);
	}

	public static function render_general_info( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<div class="mbr-general-info-metaboxes"><div class="mbr-general-metabox-1">';

		foreach ( self::general_fields() as $field ) {
			if ( null !== $field['flag'] && ! Settings::component_enabled( $field['flag'] ) ) {
				continue;
			}

			// Goodreads-Bewertung: Legacy-Wert aus „rcno_book_gr_review“
			// wird über den Alias-Fallback angezeigt (Meta::get_string).
			$value = Meta::get_string( $post->ID, $field['key'] );

			printf(
				'<div class="%1$s mbr-general-field"><label class="mbr-general-label" for="%2$s">%3$s</label>' .
				'<input type="text" name="%2$s" id="%2$s" value="%4$s"/></div>',
				esc_attr( $field['class'] ),
				esc_attr( $field['key'] ),
				esc_html( $field['label'] ),
				esc_attr( $value )
			);
		}

		// Eigene Metadaten-Felder (Erweiterung) am Raster-Ende ergänzen.
		foreach ( Extensions::custom_fields() as $field ) {
			printf(
				'<div class="mbr-custom-field mbr-general-field"><label class="mbr-general-label" for="%1$s">%2$s</label>' .
				'<input type="text" name="%1$s" id="%1$s" value="%3$s"/></div>',
				esc_attr( $field['key'] ),
				esc_html( $field['label'] ),
				esc_attr( (string) get_post_meta( $post->ID, $field['key'], true ) )
			);
			}

		echo '</div></div>';
	}

	/* ---------------- render: Bewertung der Besprechung ---------------- */

	public static function render_review_score( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$criteria     = get_post_meta( $post->ID, 'rcno_review_score_criteria', true );
		$type         = Meta::get_string( $post->ID, 'rcno_review_score_type' );
		$position     = Meta::get_string( $post->ID, 'rcno_review_score_position' );
		$enable       = Meta::get_string( $post->ID, 'rcno_review_score_enable' );
		$fixed        = '1' === (string) Settings::get( 'rcno_enable_custom_review_score_criteria', '0' );
		$fixed_list   = Settings::split_list( 'rcno_custom_review_score_criteria', '' );
		$criteria     = is_array( $criteria ) ? $criteria : array();

		if ( empty( $criteria ) && $fixed && ! empty( $fixed_list ) ) {
			$criteria = array_map(
				static fn( string $label ): array => array( 'label' => $label, 'score' => '' ),
				$fixed_list
			);
		}
		?>
		<div class="mbr-review-score-container">

			<div class="mbr-review-score-options">
				<div class="mbr-review-score-enable">
					<label for="rcno_review_score_enable"><?php esc_html_e( 'Bewertungsbox aktivieren', 'modern-book-reviews' ); ?></label>
					<input type="hidden" name="rcno_review_score_enable" value="0"/>
					<input type="checkbox" id="rcno_review_score_enable" name="rcno_review_score_enable"
						value="1" <?php checked( $enable, '1', true ); ?>/>
				</div>

				<div class="mbr-review-score-type">
					<label for="rcno_review_score_type"><?php esc_html_e( 'Bewertungstyp', 'modern-book-reviews' ); ?></label>
					<select id="rcno_review_score_type" name="rcno_review_score_type">
						<option value="number" <?php selected( $type, 'number' ); ?>><?php esc_html_e( 'Anzahl', 'modern-book-reviews' ); ?></option>
						<option value="letter" <?php selected( $type, 'letter' ); ?>><?php esc_html_e( 'Buchstabe', 'modern-book-reviews' ); ?></option>
						<option value="stars" <?php selected( $type, 'stars' ); ?>><?php esc_html_e( 'Sterne', 'modern-book-reviews' ); ?></option>
					</select>
				</div>

				<div class="mbr-review-score-position">
					<label for="rcno_review_score_position"><?php esc_html_e( 'Position der Rezensionsbox', 'modern-book-reviews' ); ?></label>
					<select id="rcno_review_score_position" name="rcno_review_score_position">
						<option value="top" <?php selected( $position, 'top' ); ?>><?php esc_html_e( 'Oben', 'modern-book-reviews' ); ?></option>
						<option value="bottom" <?php selected( $position, 'bottom' ); ?>><?php esc_html_e( 'Unten', 'modern-book-reviews' ); ?></option>
					</select>
				</div>
			</div>

			<div class="mbr-review-score-table">
				<table id="mbr-review-score-table" class="mbr-repeatable" width="100%">
					<thead>
					<tr>
						<th width="40%"><?php esc_html_e( 'Kriterium', 'modern-book-reviews' ); ?></th>
						<th width="40%"><?php esc_html_e( 'Punkte', 'modern-book-reviews' ); ?></th>
						<th width="8%"></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $criteria as $field ) : ?>
						<tr>
							<td><input type="text" class="widefat" name="label[]"
									value="<?php echo esc_attr( (string) ( $field['label'] ?? '' ) ); ?>"<?php echo $fixed ? ' readonly' : ''; ?>/></td>
							<td><input type="number" min="0" max="5" step="0.1" class="widefat" name="score[]"
									value="<?php echo esc_attr( (string) ( $field['score'] ?? '' ) ); ?>"/></td>
							<?php if ( ! $fixed ) : ?>
								<td><a class="button mbr-remove-row" href="#"><?php esc_html_e( 'Entfernen', 'modern-book-reviews' ); ?></a></td>
							<?php else : ?>
								<td></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>

					<!-- Leerzeile als Vorlage zum Duplizieren -->
					<tr class="mbr-empty-row hidden">
						<td><input type="text" class="widefat" name="label[]"/></td>
						<td><input type="number" min="0" max="5" step="0.1" class="widefat" name="score[]"/></td>
						<td><a class="button mbr-remove-row" href="#"><?php esc_html_e( 'Entfernen', 'modern-book-reviews' ); ?></a></td>
					</tr>
					</tbody>
				</table>

				<p class="description" style="text-align:right;"><?php
					esc_html_e( 'Kriterium: Name der Bewertungskategorie | Punkte: Wert zwischen 0 und 5 in 0,1-Schritten', 'modern-book-reviews' );
				?></p>

				<?php if ( ! $fixed ) : ?>
					<p><a class="button button-primary mbr-add-row" data-target="#mbr-review-score-table" href="#"><?php
						esc_html_e( 'Kriterium hinzufügen', 'modern-book-reviews' );
					?></a></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/* ---------------- render: Kauflinks ---------------- */

	public static function render_buy_links( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$links  = Meta::get_buy_links( $post->ID );
		$stores = self::store_options();
		?>
		<div class="mbr-buy-links-container">
			<div class="mbr-buy-links-table">
				<table id="mbr-buy-links-table" class="mbr-repeatable" width="100%">
					<thead>
					<tr>
						<th width="25%"><?php esc_html_e( 'Shop', 'modern-book-reviews' ); ?></th>
						<th width="65%"><?php esc_html_e( 'Link', 'modern-book-reviews' ); ?></th>
						<th width="10%"></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $links as $link ) : ?>
						<?php self::buy_links_row( $stores, (string) $link['store'], (string) $link['link'] ); ?>
					<?php endforeach; ?>

					<!-- Leerzeile als Vorlage zum Duplizieren -->
					<?php self::buy_links_row( $stores, '', '', true ); ?>
					</tbody>
				</table>

				<p class="description" style="text-align:right;"><?php
					esc_html_e( 'Wähle hier deine Affiliate-Kauflinks aus und trage sie ein.', 'modern-book-reviews' );
				?></p>

				<p><a class="button button-primary mbr-add-row" data-target="#mbr-buy-links-table" href="#"><?php
					esc_html_e( 'Link hinzufügen', 'modern-book-reviews' );
				?></a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Eine Zeile der Kauflink-Tabelle. Nicht in der Shop-Liste
	 * gespeicherte Shops (z. B. eigene Buchhandlungen) werden als
	 * freies Textfeld bearbeitbar; über „Anderer Shop …“ lässt sich
	 * jederzeit ein beliebiger neuer Shop eintragen.
	 */
	private static function buy_links_row( array $stores, string $store, string $link, bool $is_template = false ): void {
		$is_free = '' !== $store && ! isset( $stores[ $store ] );
		?>
		<tr class="<?php echo $is_template ? 'mbr-empty-row hidden' : ''; ?>">
			<td>
				<?php if ( $is_free ) : ?>
					<input type="text" class="widefat mbr-buy-link-store-free" name="store[]"
						placeholder="<?php esc_attr_e( 'Shop (frei)', 'modern-book-reviews' ); ?>"
						value="<?php echo esc_attr( $store ); ?>"/>
				<?php else : ?>
					<select name="store[]" class="mbr-buy-link-store" style="width:100%">
						<?php
						foreach ( $stores as $slug => $name ) {
							printf(
								'<option value="%s" %s>%s</option>',
								esc_attr( $slug ),
								selected( $slug, $store, false ),
								esc_html( $name )
							);
						}
						?>
						<option value="__custom__"><?php esc_html_e( 'Anderer Shop …', 'modern-book-reviews' ); ?></option>
					</select>
				<?php endif; ?>
			</td>
			<td><input type="text" class="widefat" name="link[]" value="<?php echo esc_attr( $link ); ?>"/></td>
			<td><a class="button mbr-remove-row" href="#"><?php esc_html_e( 'Entfernen', 'modern-book-reviews' ); ?></a></td>
		</tr>
		<?php
	}

	/* ---------------- render: ISBN ---------------- */

	public static function render_isbn( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$isbn = Meta::get_string( $post->ID, 'rcno_book_isbn' );
		?>
		<div class="mbr-isbn-metabox">
			<input type="text" name="rcno_book_isbn" id="rcno_book_isbn" value="<?php echo esc_attr( $isbn ); ?>"/>
			<button type="button" class="button" id="mbr-isbn-fetch"><?php esc_html_e( 'Abrufen', 'modern-book-reviews' ); ?></button>
			<div class="mbr-isbn-metabox-error hidden">
				<p><?php esc_html_e( 'Dein Buch wurde nicht gefunden.', 'modern-book-reviews' ); ?></p>
			</div>
		</div>
		<?php
	}

	/* ---------------- render: Buchdeckel ---------------- */

	public static function render_book_cover( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$cover_id    = (int) get_post_meta( $post->ID, 'rcno_reviews_book_cover_id', true );
		$cover_src   = (string) get_post_meta( $post->ID, 'rcno_reviews_book_cover_src', true );
		$cover_alt   = (string) get_post_meta( $post->ID, 'rcno_reviews_book_cover_alt', true );
		$cover_title = (string) get_post_meta( $post->ID, 'rcno_reviews_book_cover_title', true );
		$cover_url   = Meta::get_string( $post->ID, 'rcno_reviews_book_cover_url' );

		if ( '' === $cover_src && $cover_id > 0 ) {
			$cover_src = (string) wp_get_attachment_url( $cover_id );
		}
		?>
		<div id="mbr-book-cover-container" class="mbr-book-cover-container<?php echo '' === $cover_src ? ' hidden' : ''; ?>">
			<img id="mbr-cover-preview" src="<?php echo esc_attr( $cover_src ); ?>"
				alt="<?php echo esc_attr( $cover_alt ); ?>" style="width:100%;"/>
			<?php if ( Settings::component_enabled( 'rcno_show_book_cover_url' ) ) : ?>
				<input type="text" id="rcno-reviews-book-cover-url" name="rcno_reviews_book_cover_url"
					value="<?php echo esc_attr( $cover_url ); ?>"
					placeholder="<?php esc_attr_e( 'Eigene URL', 'modern-book-reviews' ); ?>"
					style="width:100%;"/>
			<?php endif; ?>
		</div>

		<p class="hide-if-no-js">
			<a title="<?php esc_attr_e( 'Buchdeckel hinzufügen', 'modern-book-reviews' ); ?>" href="javascript:"
				id="mbr-add-book-cover"><?php esc_html_e( 'Buchdeckel hinzufügen', 'modern-book-reviews' ); ?></a>
		</p>
		<p class="hide-if-no-js <?php echo '' === $cover_src ? 'hidden' : ''; ?>">
			<a title="<?php esc_attr_e( 'Buchdeckel entfernen', 'modern-book-reviews' ); ?>" href="javascript:"
				id="mbr-remove-book-cover"><?php esc_html_e( 'Buchdeckel entfernen', 'modern-book-reviews' ); ?></a>
		</p>

		<input type="hidden" id="rcno-reviews-book-cover-src" name="rcno_reviews_book_cover_src"
			value="<?php echo esc_attr( $cover_src ); ?>"/>
		<input type="hidden" id="rcno-reviews-book-cover-alt" name="rcno_reviews_book_cover_alt"
			value="<?php echo esc_attr( $cover_alt ); ?>"/>
		<input type="hidden" id="rcno-reviews-book-cover-title" name="rcno_reviews_book_cover_title"
			value="<?php echo esc_attr( $cover_title ); ?>"/>
		<input type="hidden" id="rcno-reviews-book-cover-id" name="rcno_reviews_book_cover_id"
			value="<?php echo esc_attr( (string) $cover_id ); ?>"/>
		<?php
	}

	/* ---------------- render: 5-Sterne-Bewertung ---------------- */

	public static function render_review_rating( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$rating = Meta::get_string( $post->ID, 'rcno_admin_rating' );
		?>
		<div id="mbr-star-rating" class="mbr-star-rating"
			data-rating="<?php echo esc_attr( $rating ); ?>"></div>
		<input type="hidden" id="rcno_admin_rating" name="rcno_admin_rating"
			value="<?php echo esc_attr( $rating ); ?>"/>
		<?php
	}

	/* ---------------- Speichern ---------------- */

	/**
	 * Speichert alle Metabox-Felder. Nur Felder, deren Metabox
	 * tatsächlich übermittelt wurde (Bildschirm-Optionen können
	 * einzelne Metaboxen ausblenden), werden geschrieben.
	 */
	public static function save( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		self::save_description( $post_id );
		self::save_general_info( $post_id );
		self::save_review_score( $post_id );
		self::save_buy_links( $post_id );
		self::save_isbn( $post_id );
		self::save_cover( $post_id );
		self::save_rating( $post_id );
	}

	private static function save_description( int $post_id ): void {
		if ( ! isset( $_POST['rcno_book_description'] ) ) {
			return;
		}

		update_post_meta( $post_id, 'rcno_book_description', wp_kses_post( wp_unslash( $_POST['rcno_book_description'] ) ) );
	}

	private static function save_general_info( int $post_id ): void {
		$text_keys = array(
			'rcno_book_title',
			'rcno_book_illustrator',
			'rcno_book_pub_date',
			'rcno_book_pub_format',
			'rcno_book_pub_edition',
			'rcno_book_series_number',
			'rcno_book_page_count',
			'rcno_book_gr_id',
			'rcno_book_isbn13',
			'rcno_book_asin',
			'rcno_book_gr_rating',
		);

		foreach ( $text_keys as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}

		if ( isset( $_POST['rcno_book_gr_url'] ) ) {
			update_post_meta( $post_id, 'rcno_book_gr_url', esc_url_raw( wp_unslash( $_POST['rcno_book_gr_url'] ) ) );
		}

		// Eigene Metadaten-Felder (Erweiterung).
		foreach ( Extensions::custom_fields() as $field ) {
			if ( ! isset( $_POST[ $field['key'] ] ) ) {
				continue;
			}

			update_post_meta( $post_id, $field['key'], sanitize_text_field( wp_unslash( $_POST[ $field['key'] ] ) ) );
		}
	}

	private static function save_review_score( int $post_id ): void {
		// Optionen (Checkbox/Select) kommen aus der Score-Metabox.
		if ( isset( $_POST['rcno_review_score_enable'] ) ) {
			update_post_meta( $post_id, 'rcno_review_score_enable', '1' === $_POST['rcno_review_score_enable'] ? '1' : '0' );
		}

		if ( isset( $_POST['rcno_review_score_type'] ) && in_array( $_POST['rcno_review_score_type'], array( 'number', 'letter', 'stars' ), true ) ) {
			update_post_meta( $post_id, 'rcno_review_score_type', sanitize_key( wp_unslash( $_POST['rcno_review_score_type'] ) ) );
		}

		if ( isset( $_POST['rcno_review_score_position'] ) && in_array( $_POST['rcno_review_score_position'], array( 'top', 'bottom' ), true ) ) {
			update_post_meta( $post_id, 'rcno_review_score_position', sanitize_key( wp_unslash( $_POST['rcno_review_score_position'] ) ) );
		}

		// Kriterien-Tabelle.
		if ( ! isset( $_POST['label'], $_POST['score'] ) || ! is_array( $_POST['label'] ) || ! is_array( $_POST['score'] ) ) {
			return;
		}

		$criteria = array();
		$labels   = wp_unslash( $_POST['label'] );
		$scores   = wp_unslash( $_POST['score'] );

		foreach ( $labels as $index => $label ) {
			$label = sanitize_text_field( (string) $label );

			if ( '' === $label ) {
				continue;
			}

			$score = isset( $scores[ $index ] ) ? (float) $scores[ $index ] : 0.0;

			$criteria[] = array(
				'label' => $label,
				'score' => number_format( max( 0.0, min( 5.0, $score ) ), 1 ),
			);
		}

		if ( empty( $criteria ) ) {
			delete_post_meta( $post_id, 'rcno_review_score_criteria' );
			return;
		}

		update_post_meta( $post_id, 'rcno_review_score_criteria', $criteria );
	}

	private static function save_buy_links( int $post_id ): void {
		if ( ! isset( $_POST['store'], $_POST['link'] )
			|| ! is_array( $_POST['store'] ) || ! is_array( $_POST['link'] )
		) {
			return;
		}

		$stores = wp_unslash( $_POST['store'] );
		$links  = wp_unslash( $_POST['link'] );
		$saved  = array();

		foreach ( $links as $index => $link ) {
			$link = esc_url_raw( (string) $link );

			if ( '' === $link ) {
				continue;
			}

			// Freie Shop-Namen bleiben erhalten; vordefinierte Shops
			// werden wie im Original als Slug gespeichert.
			$store = isset( $stores[ $index ] ) ? sanitize_text_field( (string) $stores[ $index ] ) : '';
			$saved[] = array(
				'store' => $store,
				'link'  => $link,
			);
		}

		if ( empty( $saved ) ) {
			// Wichtig: Entfernen aller Zeilen löscht die gespeicherten
			// Kauflinks (z. B. den Legacy-Amazon-Eintrag) wirklich.
			delete_post_meta( $post_id, 'rcno_review_buy_links' );
			return;
		}

		update_post_meta( $post_id, 'rcno_review_buy_links', $saved );
	}

	private static function save_isbn( int $post_id ): void {
		if ( ! isset( $_POST['rcno_book_isbn'] ) ) {
			return;
		}

		update_post_meta( $post_id, 'rcno_book_isbn', sanitize_text_field( wp_unslash( $_POST['rcno_book_isbn'] ) ) );
	}

	private static function save_cover( int $post_id ): void {
		if ( isset( $_POST['rcno_reviews_book_cover_src'] ) ) {
			update_post_meta( $post_id, 'rcno_reviews_book_cover_src', esc_url_raw( wp_unslash( $_POST['rcno_reviews_book_cover_src'] ) ) );
		}

		if ( isset( $_POST['rcno_reviews_book_cover_alt'] ) ) {
			update_post_meta( $post_id, 'rcno_reviews_book_cover_alt', sanitize_text_field( wp_unslash( $_POST['rcno_reviews_book_cover_alt'] ) ) );
		}

		if ( isset( $_POST['rcno_reviews_book_cover_title'] ) ) {
			update_post_meta( $post_id, 'rcno_reviews_book_cover_title', sanitize_text_field( wp_unslash( $_POST['rcno_reviews_book_cover_title'] ) ) );
		}

		if ( isset( $_POST['rcno_reviews_book_cover_id'] ) ) {
			update_post_meta( $post_id, 'rcno_reviews_book_cover_id', absint( $_POST['rcno_reviews_book_cover_id'] ) );
		}

		if ( isset( $_POST['rcno_reviews_book_cover_url'] ) ) {
			update_post_meta( $post_id, 'rcno_reviews_book_cover_url', esc_url_raw( wp_unslash( $_POST['rcno_reviews_book_cover_url'] ) ) );
		}
	}

	private static function save_rating( int $post_id ): void {
		if ( ! isset( $_POST['rcno_admin_rating'] ) ) {
			return;
		}

		$rating = (float) $_POST['rcno_admin_rating'];
		$rating = max( 0.0, min( 5.0, $rating ) );

		update_post_meta( $post_id, 'rcno_admin_rating', number_format( $rating, 1 ) );
	}
}
