<?php
/**
 * Erweiterungen – gleiche Seite wie das Original-Plugin
 * („Recencio Erweiterungen“), jedoch nativ im Plugin integriert:
 *
 *  - Autorenbox: Autoren-Infos unter der Rezension, Social-Media-
 *    Angaben aus dem User-Profil
 *  - Eigene Metadaten-Felder: eigene Felder für die Buchdetails
 *  - Buchdeckel automatisch laden: Cover per ISBN beim Speichern
 *  - Beiträge ↔ Rezensionen wandeln
 *  - Social Media Sharing: Teilen-Buttons für Rezensionen
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

final class Extensions {

	public const OPTION_KEY = 'mbr_extensions';
	public const PAGE_SLUG  = 'mbr-extensions';
	private const NONCE      = 'mbr_extensions_nonce';

	/**
	 * Definitionen der Erweiterungen (Beschreibungen wie im Original).
	 *
	 * @return array<string, array{title: string, description: string}>
	 */
	public static function definitions(): array {
		return array(
			'author_box'    => array(
				'title'       => __( 'Autorenbox', 'modern-book-reviews' ),
				'description' => __( 'Fügt eine Autorenbox zu deinen veröffentlichten Rezensionen hinzu. Bitte trage in deinem User-Profil deine Social-Media-Informationen ein.', 'modern-book-reviews' ),
			),
			'custom_fields' => array(
				'title'       => __( 'Eigene Metadaten-Felder', 'modern-book-reviews' ),
				'description' => __( 'Füge deine eigenen Metadaten-Felder zu den Buchdetails von Rezensionen hinzu. Die Felder erscheinen im Editor und in der Buchbox.', 'modern-book-reviews' ),
			),
			'auto_cover'    => array(
				'title'       => __( 'Buchdeckel laden', 'modern-book-reviews' ),
				'description' => __( 'Lädt automatisch einen Buchdeckel per ISBN und fügt ihn zur Rezension hinzu (falls noch kein Cover gesetzt ist).', 'modern-book-reviews' ),
			),
			'converter'     => array(
				'title'       => __( 'Beitrag in Rezension wandeln', 'modern-book-reviews' ),
				'description' => __( 'Wandelt WordPress-Beiträge in Rezensionen um – und umgekehrt.', 'modern-book-reviews' ),
			),
			'social'        => array(
				'title'       => __( 'Social Media Sharing', 'modern-book-reviews' ),
				'description' => __( 'Leichte Teilen-Buttons für deine Rezensionen. Wähle die Netzwerke und die Position der Buttons.', 'modern-book-reviews' ),
			),
		);
	}

	/* ---------------- Registrierung ---------------- */

	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		// Frontend: Sharing oben (läuft nach ReviewBox, gleiche Priorität).
		add_filter( 'the_content', array( __CLASS__, 'prepend_sharing' ), 15 );
		// Frontend: Sharing unten + Autorenbox.
		add_filter( 'the_content', array( __CLASS__, 'append_frontend' ), 25 );

		// Buchdeckel automatisch laden (nach dem Metabox-Speichern).
		add_action( 'save_post_' . PostTypes::POST_TYPE, array( __CLASS__, 'auto_cover' ), 20, 2 );

		// Social-Media-Profilfelder für die Autorenbox.
		add_action( 'show_user_profile', array( __CLASS__, 'profile_fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'profile_fields' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile_fields' ) );
	}

	/* ---------------- Zustand ---------------- */

	/**
	 * Gespeicherte Erweiterungs-Einstellungen.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$option = get_option( self::OPTION_KEY, array() );

		return is_array( $option ) ? $option : array();
	}

	public static function enabled( string $key ): bool {
		return '1' === (string) ( self::all()[ $key ] ?? '0' );
	}

	/**
	 * Konfigurierte eigene Metadaten-Felder.
	 * Format je Zeile: „schluessel::Beschriftung“ (Beschriftung optional).
	 *
	 * @return array<int, array{key: string, label: string}>
	 */
	public static function custom_fields(): array {
		if ( ! self::enabled( 'custom_fields' ) ) {
			return array();
		}

		$defs   = (string) ( self::all()['custom_field_defs'] ?? '' );
		$fields = array();

		foreach ( preg_split( '/\r\n|\r|\n/', $defs ) ?: array() as $line ) {
			$line = trim( (string) $line );

			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '::', $line, 2 ) );
			$key   = sanitize_key( $parts[0] );

			if ( '' === $key ) {
				continue;
			}

			$fields[] = array(
				'key'   => $key,
				'label' => $parts[1] ?? $key,
			);
		}

		return $fields;
	}

	/* ---------------- Menü ---------------- */

	public static function add_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . PostTypes::POST_TYPE,
			__( 'Buchrezensionen – Erweiterungen', 'modern-book-reviews' ),
			__( 'Erweiterungen', 'modern-book-reviews' ),
			SettingsPage::CAPABILITY,
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, self::PAGE_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'mbr-admin-extensions',
			MBR_PLUGIN_URL . 'assets/admin/extensions.css',
			array(),
			MBR_VERSION
		);
	}

	/* ---------------- Aktionen ---------------- */

	public static function handle_actions(): void {
		if ( ! isset( $_POST[ self::NONCE ] ) ) {
			return;
		}

		if ( ! current_user_can( SettingsPage::CAPABILITY )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE )
		) {
			return;
		}

		$options = self::all();

		// Erweiterung aktivieren/deaktivieren.
		if ( isset( $_POST['mbr_ext_toggle'] ) ) {
			$key                          = sanitize_key( (string) wp_unslash( $_POST['mbr_ext_toggle'] ) );
			$options[ $key ]              = isset( $options[ $key ] ) && '1' === (string) $options[ $key ] ? '0' : '1';
			$_POST['mbr_ext_notice']      = $key;
		}

		// Sharing-Einstellungen.
		if ( isset( $_POST['mbr_ext_social'] ) && is_array( $_POST['mbr_ext_social'] ) ) {
			$social                       = wp_unslash( $_POST['mbr_ext_social'] );
			$networks                     = isset( $social['networks'] ) && is_array( $social['networks'] )
				? array_values( array_map( 'sanitize_key', (array) $social['networks'] ) )
				: array();
			$position                     = isset( $social['position'] ) && in_array( $social['position'], array( 'top', 'bottom', 'both' ), true )
				? (string) $social['position']
				: 'bottom';

			$options['social_networks']   = $networks;
			$options['social_position']   = $position;
			$_POST['mbr_ext_notice']      = 'social_saved';
		}

		// Eigene Metadaten-Felder.
		if ( isset( $_POST['mbr_ext_custom_fields'] ) ) {
			$options['custom_field_defs'] = sanitize_textarea_field( wp_unslash( (string) $_POST['mbr_ext_custom_fields'] ) );
			$_POST['mbr_ext_notice']      = 'custom_fields_saved';
		}

		// Beiträge ↔ Rezensionen wandeln.
		if ( isset( $_POST['mbr_convert_post'] ) ) {
			$post_id = absint( wp_unslash( $_POST['mbr_convert_post'] ) );

			if ( $post_id > 0 && 'post' === (string) get_post_type( $post_id ) ) {
				wp_update_post(
					array(
						'ID'        => $post_id,
						'post_type' => PostTypes::POST_TYPE,
					)
				);
				flush_rewrite_rules( false );
				$_POST['mbr_ext_notice'] = 'converted';
			}
		}

		if ( isset( $_POST['mbr_convert_review'] ) ) {
			$review_id = absint( wp_unslash( $_POST['mbr_convert_review'] ) );

			if ( $review_id > 0 && PostTypes::POST_TYPE === (string) get_post_type( $review_id ) ) {
				wp_update_post(
					array(
						'ID'        => $review_id,
						'post_type' => 'post',
					)
				);
				flush_rewrite_rules( false );
				$_POST['mbr_ext_notice'] = 'converted';
			}
		}

		update_option( self::OPTION_KEY, $options, false );

		wp_safe_redirect(
			add_query_arg(
				'mbr-ext-notice',
				sanitize_key( (string) ( $_POST['mbr_ext_notice'] ?? '' ) ),
				admin_url( 'edit.php?post_type=' . PostTypes::POST_TYPE . '&page=' . self::PAGE_SLUG )
			)
		);
		exit;
	}

	/* ---------------- Seite ---------------- */

	public static function render_page(): void {
		if ( ! current_user_can( SettingsPage::CAPABILITY ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Seite.', 'modern-book-reviews' ) );
		}

		$all     = self::all();
		$networks = array(
			'facebook'  => 'Facebook',
			'x'         => 'X (Twitter)',
			'whatsapp'  => 'WhatsApp',
			'telegram'  => 'Telegram',
			'pinterest' => 'Pinterest',
			'email'     => 'E-Mail',
		);
		$social_networks = isset( $all['social_networks'] ) && is_array( $all['social_networks'] ) ? $all['social_networks'] : array( 'facebook', 'x' );
		$social_position = (string) ( $all['social_position'] ?? 'bottom' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Erweiterungen', 'modern-book-reviews' ); ?></h1>

			<?php if ( isset( $_GET['mbr-ext-notice'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reiner Anzeige-Hinweis. ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Erweiterung gespeichert.', 'modern-book-reviews' ); ?></p>
				</div>
			<?php endif; ?>


			<?php foreach ( self::definitions() as $key => $ext ) : ?>
				<div class="mbr-ext-card">
					<h2>
						<?php echo esc_html( $ext['title'] ); ?>
						<span class="mbr-ext-badge <?php echo self::enabled( $key ) ? 'mbr-ext-on' : 'mbr-ext-off'; ?>">
							<?php echo self::enabled( $key ) ? esc_html__( 'Aktiv', 'modern-book-reviews' ) : esc_html__( 'Aus', 'modern-book-reviews' ); ?>
						</span>
					</h2>
					<p><?php echo esc_html( $ext['description'] ); ?></p>

					<form method="post">
						<?php wp_nonce_field( self::NONCE, self::NONCE ); ?>
						<input type="hidden" name="mbr_ext_toggle" value="<?php echo esc_attr( $key ); ?>"/>
						<button type="submit" class="button <?php echo self::enabled( $key ) ? '' : 'button-primary'; ?>">
							<?php echo self::enabled( $key ) ? esc_html__( 'Deaktivieren', 'modern-book-reviews' ) : esc_html__( 'Aktiviere', 'modern-book-reviews' ); ?>
						</button>
					</form>

					<?php if ( 'social' === $key && self::enabled( $key ) ) : ?>
						<form method="post" style="margin-top:14px;">
							<?php wp_nonce_field( self::NONCE, self::NONCE ); ?>
							<h3><?php esc_html_e( 'Netzwerke', 'modern-book-reviews' ); ?></h3>
							<?php foreach ( $networks as $slug => $name ) : ?>
								<label style="display:inline-block; margin-right:14px;">
									<input type="checkbox" name="mbr_ext_social[networks][]"
										value="<?php echo esc_attr( $slug ); ?>"
										<?php checked( in_array( $slug, $social_networks, true ) ); ?>/>
									<?php echo esc_html( $name ); ?>
								</label>
							<?php endforeach; ?>

							<h3><?php esc_html_e( 'Position', 'modern-book-reviews' ); ?></h3>
							<select name="mbr_ext_social[position]">
								<option value="top" <?php selected( $social_position, 'top' ); ?>><?php esc_html_e( 'Oberhalb der Rezensionsbox', 'modern-book-reviews' ); ?></option>
								<option value="bottom" <?php selected( $social_position, 'bottom' ); ?>><?php esc_html_e( 'Unterhalb der Rezension', 'modern-book-reviews' ); ?></option>
								<option value="both" <?php selected( $social_position, 'both' ); ?>><?php esc_html_e( 'Oben und unten', 'modern-book-reviews' ); ?></option>
							</select>

							<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Sharing speichern', 'modern-book-reviews' ); ?></button></p>
						</form>
					<?php endif; ?>

					<?php if ( 'custom_fields' === $key && self::enabled( $key ) ) : ?>
						<form method="post" style="margin-top:14px;">
							<?php wp_nonce_field( self::NONCE, self::NONCE ); ?>
							<h3><?php esc_html_e( 'Eigene Felder', 'modern-book-reviews' ); ?></h3>
							<p class="description">
								<?php esc_html_e( 'Eine Definition pro Zeile: schluessel::Beschriftung (Beschriftung optional). Die Felder erscheinen unter „Allgemeine Information“ im Editor und in den Buchdetails.', 'modern-book-reviews' ); ?>
							</p>
							<textarea name="mbr_ext_custom_fields" rows="5" class="large-text"><?php
								echo esc_textarea( (string) ( $all['custom_field_defs'] ?? '' ) );
							?></textarea>
							<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Felder speichern', 'modern-book-reviews' ); ?></button></p>
						</form>
					<?php endif; ?>

					<?php if ( 'converter' === $key && self::enabled( $key ) ) : ?>
						<?php $posts = get_posts( array( 'post_type' => 'post', 'numberposts' => 50, 'post_status' => 'any', 'orderby' => 'date', 'order' => 'DESC' ) ); ?>
						<?php $reviews = get_posts( array( 'post_type' => PostTypes::POST_TYPE, 'numberposts' => 50, 'post_status' => 'any', 'orderby' => 'date', 'order' => 'DESC' ) ); ?>
						<div style="margin-top:14px; display:flex; gap:24px; flex-wrap:wrap;">
							<form method="post">
								<?php wp_nonce_field( self::NONCE, self::NONCE ); ?>
								<h3><?php esc_html_e( 'Beitrag → Rezension', 'modern-book-reviews' ); ?></h3>
								<select name="mbr_convert_post">
									<?php foreach ( $posts as $p ) : ?>
										<option value="<?php echo esc_attr( (string) $p->ID ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></option>
									<?php endforeach; ?>
								</select>
								<button type="submit" class="button"><?php esc_html_e( 'Wandeln', 'modern-book-reviews' ); ?></button>
							</form>

							<form method="post">
								<?php wp_nonce_field( self::NONCE, self::NONCE ); ?>
								<h3><?php esc_html_e( 'Rezension → Beitrag', 'modern-book-reviews' ); ?></h3>
								<select name="mbr_convert_review">
									<?php foreach ( $reviews as $r ) : ?>
										<option value="<?php echo esc_attr( (string) $r->ID ); ?>"><?php echo esc_html( get_the_title( $r ) ); ?></option>
									<?php endforeach; ?>
								</select>
								<button type="submit" class="button"><?php esc_html_e( 'Wandeln', 'modern-book-reviews' ); ?></button>
							</form>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/* ---------------- Social Media Sharing ---------------- */

	/**
	 * Sharing-Netzwerke mit URLs.
	 *
	 * @return array<string, array{label: string, url: string, color: string}>
	 */
	private static function share_targets( string $url, string $title ): array {
		$url   = rawurlencode( $url );
		$title = rawurlencode( $title );

		return array(
			'facebook'  => array(
				'label' => 'Facebook',
				'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
				'color' => '#1877f2',
			),
			'x'         => array(
				'label' => 'X',
				'url'   => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
				'color' => '#0f1419',
			),
			'whatsapp'  => array(
				'label' => 'WhatsApp',
				'url'   => 'https://wa.me/?text=' . $title . '%20' . $url,
				'color' => '#25d366',
			),
			'telegram'  => array(
				'label' => 'Telegram',
				'url'   => 'https://t.me/share/url?url=' . $url . '&text=' . $title,
				'color' => '#2aabee',
			),
			'pinterest' => array(
				'label' => 'Pinterest',
				'url'   => 'https://pinterest.com/pin/create/button/?url=' . $url . '&description=' . $title,
				'color' => '#e60023',
			),
			'email'     => array(
				'label' => 'E-Mail',
				'url'   => 'mailto:?subject=' . $title . '&body=' . $url,
				'color' => '#6c7789',
			),
		);
	}

	private static function sharing_html(): string {
		if ( ! self::enabled( 'social' ) ) {
			return '';
		}

		$networks = self::all()['social_networks'] ?? array( 'facebook', 'x' );
		$networks = is_array( $networks ) ? $networks : array();

		if ( array() === $networks ) {
			return '';
		}

		$targets = self::share_targets( (string) get_permalink(), (string) get_the_title() );

		$html = '<div class="mbr-share-buttons">';

		foreach ( $targets as $slug => $target ) {
			if ( ! in_array( $slug, $networks, true ) ) {
				continue;
			}

			$html .= sprintf(
				'<a class="mbr-share-btn mbr-share-%s" href="%s" target="_blank" rel="noopener" style="--mbr-share-color:%s">%s</a>',
				esc_attr( $slug ),
				esc_url( $target['url'] ),
				esc_attr( $target['color'] ),
				esc_html( $target['label'] )
			);
		}

		return $html . '</div>';
	}

	/**
	 * Sharing oberhalb der Rezensionsbox (läuft direkt nach ReviewBox,
	 * gleiche Filter-Priorität, später registriert).
	 */
	public static function prepend_sharing( string $content ): string {
		global $post;

		if ( ! $post instanceof \WP_Post
			|| PostTypes::POST_TYPE !== $post->post_type
			|| ! is_singular( PostTypes::POST_TYPE )
			|| ! in_the_loop()
			|| ! is_main_query()
		) {
			return $content;
		}

		$position = (string) ( self::all()['social_position'] ?? 'bottom' );

		if ( ! in_array( $position, array( 'top', 'both' ), true ) ) {
			return $content;
		}

		return self::sharing_html() . $content;
	}

	/* ---------------- Autorenbox + Sharing unten ---------------- */

	public static function append_frontend( string $content ): string {
		global $post;

		if ( ! $post instanceof \WP_Post
			|| PostTypes::POST_TYPE !== $post->post_type
			|| ! is_singular( PostTypes::POST_TYPE )
			|| ! in_the_loop()
			|| ! is_main_query()
		) {
			return $content;
		}

		$position = (string) ( self::all()['social_position'] ?? 'bottom' );

		if ( in_array( $position, array( 'bottom', 'both' ), true ) ) {
			$content .= self::sharing_html();
		}

		if ( self::enabled( 'author_box' ) ) {
			$content .= self::author_box_html( (int) $post->post_author );
		}

		return $content;
	}

	private static function author_box_html( int $user_id ): string {
		$user = get_userdata( $user_id );

		if ( ! $user instanceof \WP_User ) {
			return '';
		}

		$social = array(
			'facebook'  => array( 'meta' => 'mbr_facebook', 'url' => 'https://www.facebook.com/%s' ),
			'x'         => array( 'meta' => 'mbr_twitter', 'url' => 'https://x.com/%s' ),
			'instagram' => array( 'meta' => 'mbr_instagram', 'url' => 'https://www.instagram.com/%s' ),
			'goodreads' => array( 'meta' => 'mbr_goodreads', 'url' => 'https://www.goodreads.com/%s' ),
		);

		$links = '';

		foreach ( $social as $slug => $data ) {
			$handle = get_user_meta( $user_id, $data['meta'], true );

			if ( is_string( $handle ) && '' !== trim( $handle ) ) {
				$handle = preg_replace( '#^https?://[^/]+/#', '', trim( $handle ) );

				$links .= sprintf(
					'<a class="mbr-author-link mbr-author-%s" href="%s" target="_blank" rel="noopener">%s</a>',
					esc_attr( $slug ),
					esc_url( sprintf( $data['url'], rawurlencode( (string) $handle ) ) ),
					esc_html( ucfirst( (string) $slug ) )
				);
			}
		}

		if ( '' !== (string) $user->user_url ) {
			$links .= sprintf(
				'<a class="mbr-author-link mbr-author-website" href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( (string) $user->user_url ),
				esc_html__( 'Webseite', 'modern-book-reviews' )
			);
		}

		ob_start();
		?>
		<div class="mbr-author-box">
			<?php echo get_avatar( $user_id, 72, '', '', array( 'class' => 'mbr-author-avatar' ) ); ?>
			<div class="mbr-author-info">
				<h3 class="mbr-author-name"><?php echo esc_html( $user->display_name ); ?></h3>
				<?php if ( '' !== (string) $user->description ) : ?>
					<p class="mbr-author-bio"><?php echo esc_html( (string) $user->description ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $links ) : ?>
					<p class="mbr-author-links"><?php echo $links; // phpcs:ignore WordPress.Security.EscapeOutput -- intern vollständig escaped. ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/* ---------------- Profilfelder ---------------- */

	public static function profile_fields( \WP_User $user ): void {
		?>
		<h2><?php esc_html_e( 'Buchrezensionen – Social Media', 'modern-book-reviews' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="mbr_facebook"><?php esc_html_e( 'Facebook', 'modern-book-reviews' ); ?></label></th>
				<td><input type="text" name="mbr_facebook" id="mbr_facebook" class="regular-text"
					value="<?php echo esc_attr( (string) get_user_meta( $user->ID, 'mbr_facebook', true ) ); ?>"/></td>
			</tr>
			<tr>
				<th><label for="mbr_twitter"><?php esc_html_e( 'X (Twitter)', 'modern-book-reviews' ); ?></label></th>
				<td><input type="text" name="mbr_twitter" id="mbr_twitter" class="regular-text"
					value="<?php echo esc_attr( (string) get_user_meta( $user->ID, 'mbr_twitter', true ) ); ?>"/></td>
			</tr>
			<tr>
				<th><label for="mbr_instagram"><?php esc_html_e( 'Instagram', 'modern-book-reviews' ); ?></label></th>
				<td><input type="text" name="mbr_instagram" id="mbr_instagram" class="regular-text"
					value="<?php echo esc_attr( (string) get_user_meta( $user->ID, 'mbr_instagram', true ) ); ?>"/></td>
			</tr>
			<tr>
				<th><label for="mbr_goodreads"><?php esc_html_e( 'Goodreads', 'modern-book-reviews' ); ?></label></th>
				<td><input type="text" name="mbr_goodreads" id="mbr_goodreads" class="regular-text"
					value="<?php echo esc_attr( (string) get_user_meta( $user->ID, 'mbr_goodreads', true ) ); ?>"/></td>
			</tr>
		</table>
		<?php
	}

	public static function save_profile_fields( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		foreach ( array( 'mbr_facebook', 'mbr_twitter', 'mbr_instagram', 'mbr_goodreads' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_user_meta( $user_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
	}

	/* ---------------- Buchdeckel automatisch laden ---------------- */

	public static function auto_cover( int $post_id, \WP_Post $post ): void {
		if ( ! self::enabled( 'auto_cover' ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id )
		) {
			return;
		}

		// Bereits vorhandenes Cover nicht überschreiben.
		if ( has_post_thumbnail( $post_id )
			|| '' !== (string) get_post_meta( $post_id, 'rcno_reviews_book_cover_src', true )
			|| (int) get_post_meta( $post_id, 'rcno_reviews_book_cover_id', true ) > 0
		) {
			return;
		}

		$isbn = Meta::get_string( $post_id, 'rcno_book_isbn' );

		if ( '' === $isbn ) {
			return;
		}

		$book = IsbnLookup::lookup( $isbn );

		if ( null === $book || empty( $book['cover_url'] ) ) {
			return;
		}

		update_post_meta( $post_id, 'rcno_reviews_book_cover_src', esc_url_raw( (string) $book['cover_url'] ) );
	}
}
