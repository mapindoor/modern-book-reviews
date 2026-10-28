<?php
/**
 * Einstellungsseite (Template) – mit den Tabs des Original-Plugins:
 * Allgemein, Taxonomien, Komponenten, Erweitert.
 *
 * @package ModernBookReviews
 */

namespace ModernBookReviews;

defined( 'WPINC' ) || exit;

$options = get_option( Settings::OPTION_KEY, Settings::defaults() );

if ( ! is_array( $options ) ) {
	$options = Settings::defaults();
}

/**
 * Hilfsfunktionen für das Template.
 */
$opt = static fn( string $key, string $fallback = '' ): string
	=> isset( $options[ $key ] ) && '' !== (string) $options[ $key ] ? (string) $options[ $key ] : $fallback;

$opt_raw = static fn( string $key, string $fallback = '' ): string
	=> isset( $options[ $key ] ) ? (string) $options[ $key ] : $fallback;

$labels = is_array( $options['rcno_review_labels'] ?? null ) ? $options['rcno_review_labels'] : array();
$singular = (string) ( $labels['singular'] ?? 'Review' );
$plural = (string) ( $labels['plural'] ?? 'Reviews' );

/**
 * Checkbox mit Marker-Feld, damit „nicht angehakt" beim Speichern
 * erkannt wird (siehe Settings::sanitize()).
 */
$checkbox = static function ( string $key, string $checked ): string {
	$name = esc_attr( Settings::OPTION_KEY );
	$marker = sprintf(
		'<input type="hidden" name="%1$s[mbr_checkbox_markers][%2$s]" value="1" />',
		$name,
		esc_attr( $key )
	);

	return $marker . sprintf(
		'<input type="checkbox" name="%1$s[%2$s]" value="1" %3$s />',
		$name,
		esc_attr( $key ),
		checked( $checked, true, false )
	);
};

// Harte Checkbox: nur '1' gilt als angehakt (Legacy-'' = aus).
$hard = static fn( string $key ): string => '1' === $opt_raw( $key, '' ) ? '1' : '0';

// Komponenten-/Widget-Checkbox: fehlender Key fällt auf den Standard
// zurück (wie Settings::component_enabled()/widget_enabled()), '' = aus.
$defaults = Settings::defaults();
$comp = static function ( string $key ) use ( $options, $defaults ): string {
	$raw = array_key_exists( $key, $options ) ? (string) $options[ $key ] : (string) ( $defaults[ $key ] ?? '0' );
	return '1' === $raw ? '1' : '0';
};

// Weiche Checkbox: '' UND '1' gelten als angehakt (Legacy-'' = ein, erst '0' schaltet aus).
$soft = static fn( string $key ): string => '0' === $opt_raw( $key, '1' ) ? '0' : '1';

$tax_list = array(
	array( 'key' => 'author', 'name' => 'Autor', 'slug_key' => 'rcno_author_slug', 'slug_default' => 'author' ),
	array( 'key' => 'genre', 'name' => 'Genre', 'slug_key' => 'rcno_genre_slug', 'slug_default' => 'genre' ),
	array( 'key' => 'series', 'name' => 'Serie', 'slug_key' => 'rcno_series_slug', 'slug_default' => 'series' ),
	array( 'key' => 'publisher', 'name' => 'Verlag', 'slug_key' => 'rcno_publisher_slug', 'slug_default' => 'publisher' ),
);

foreach ( Settings::custom_taxonomies() as $custom ) {
	$tax_list[] = array(
		'key' => $custom['key'],
		'name' => $custom['name'],
		'slug_key' => null,
		'slug_default' => '',
	);
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Buchrezensionen – Einstellungen', 'modern-book-reviews' ); ?></h1>

	<?php settings_errors(); ?>

	<p>
		<?php
		printf(
			/* translators: %s: Plugin-Name */
			esc_html__( '%s übernimmt alle Einstellungen der alten Recencio-Einstellungsseite (gleiche Options-Schlüssel). Bestehende Rezensionen, Taxonomien und URLs bleiben erhalten.', 'modern-book-reviews' ),
			'<strong>Modern Book Reviews</strong>'
		);
		?>
	</p>

	<nav class="nav-tab-wrapper mbr-tabs">
		<a href="#mbr-tab-allgemein" class="nav-tab nav-tab-active"><?php esc_html_e( 'Allgemein', 'modern-book-reviews' ); ?></a>
		<a href="#mbr-tab-taxonomien" class="nav-tab"><?php esc_html_e( 'Taxonomien', 'modern-book-reviews' ); ?></a>
		<a href="#mbr-tab-komponenten" class="nav-tab"><?php esc_html_e( 'Komponenten', 'modern-book-reviews' ); ?></a>
		<a href="#mbr-tab-extras" class="nav-tab"><?php esc_html_e( 'Extras', 'modern-book-reviews' ); ?></a>
		<a href="#mbr-tab-vorlagen" class="nav-tab"><?php esc_html_e( 'Vorlagen', 'modern-book-reviews' ); ?></a>
		<a href="#mbr-tab-erweitert" class="nav-tab"><?php esc_html_e( 'Erweitert', 'modern-book-reviews' ); ?></a>
	</nav>

	<form method="post" action="options.php">
		<?php settings_fields( SettingsPage::SETTINGS_GROUP ); ?>

		<div id="mbr-tab-allgemein" class="mbr-tab-panel is-active">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Beschriftungen', 'modern-book-reviews' ); ?></th>
					<td>
						<label for="mbr-singular"><?php esc_html_e( 'Singular', 'modern-book-reviews' ); ?></label>
						<input id="mbr-singular" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_review_labels][singular]"
							value="<?php echo esc_attr( $singular ); ?>" class="regular-text" />
						&nbsp;
						<label for="mbr-plural"><?php esc_html_e( 'Plural', 'modern-book-reviews' ); ?></label>
						<input id="mbr-plural" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_review_labels][plural]"
							value="<?php echo esc_attr( $plural ); ?>" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'URLs', 'modern-book-reviews' ); ?></th>
					<td>
						<p class="description">
							<?php
							printf(
								/* translators: 1: Archiv-URL, 2: Einzel-URL */
								esc_html__( 'Deine Rezensionen liegen unter %1$s und eine einzelne Buchbesprechung unter %2$s (Beispiel).', 'modern-book-reviews' ),
								'<code>' . esc_html( home_url( '/' ) . Settings::sanitize_taxonomy_key( $plural ) . '/' ) . '</code>',
								'<code>' . esc_html( home_url( '/' ) . Settings::sanitize_taxonomy_key( $singular ) . '/a-sample-book-review' ) . '</code>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Rezensionen auf der Homepage?', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_reviews_on_homepage', $hard( 'rcno_reviews_on_homepage' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Sollen Rezensionen auf der Homepage erscheinen?', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Rezensionen in RSS?', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_reviews_in_rss', $hard( 'rcno_reviews_in_rss' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Sollen Rezensionen im RSS-Feed erscheinen?', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Rezensionen in der REST-API?', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_reviews_in_rest', $soft( 'rcno_reviews_in_rest' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Aktiviert einfache Unterstützung für Rezensionen in der REST-API von WordPress.', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Archivseite für Rezensionen', 'modern-book-reviews' ); ?></th>
					<td>
						<p class="description"><?php esc_html_e( 'Ganzen Text oder Auszug auf Archivseiten zeigen.', 'modern-book-reviews' ); ?></p>
						<label>
							<input type="radio"
								name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_reviews_archive]"
								value="archive_display_full" <?php checked( 'archive_display_full' === Settings::archive_mode() ); ?> />
							<?php esc_html_e( 'Die ganze Rezension', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label>
							<input type="radio"
								name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_reviews_archive]"
								value="archive_display_excerpt" <?php checked( 'archive_display_excerpt' === Settings::archive_mode() ); ?> />
							<?php esc_html_e( 'Nur ein Auszug der Rezension', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Unterstützung für Gutenberg', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_reviews_in_gutenberg', $soft( 'rcno_reviews_in_gutenberg' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Aktiviert den Gutenberg-Editor und die Rezensions-Sidebar für Rezensionen.', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div id="mbr-tab-taxonomien" class="mbr-tab-panel">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mbr-taxonomy-selection"><?php esc_html_e( 'Zusätzliche Taxonomien', 'modern-book-reviews' ); ?></label>
					</th>
					<td>
						<input id="mbr-taxonomy-selection" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_taxonomy_selection]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_taxonomy_selection', '' ) ); ?>"
							class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Kommaseparierte Liste eigener Taxonomien (wie „Taxonomie-Auswahl" im Original), z. B. „Bewertung, Format". Nach dem Speichern erscheinen unten passende Einstellungsabschnitte.', 'modern-book-reviews' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Standard-WP-Taxonomien', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_enable_builtin_taxonomy', $hard( 'rcno_enable_builtin_taxonomy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( '„Kategorien" und „Schlagwörter" von WordPress für Rezensionen aktivieren.', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php foreach ( $tax_list as $tax ) : ?>
				<?php
				$tax_key = $tax['key'];
				$tax_labels = Settings::tax_labels( $tax_key, $tax['name'], $tax['name'] );
				?>
				<h2 class="title"><?php echo esc_html( $tax_labels['plural'] ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Beschriftungen', 'modern-book-reviews' ); ?></th>
						<td>
							<label for="mbr-<?php echo esc_attr( $tax_key ); ?>-singular"><?php esc_html_e( 'Singular', 'modern-book-reviews' ); ?></label>
							<input id="mbr-<?php echo esc_attr( $tax_key ); ?>-singular" type="text"
								name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_<?php echo esc_attr( $tax_key ); ?>_labels][singular]"
								value="<?php echo esc_attr( $tax_labels['singular'] ); ?>" class="regular-text" />
							&nbsp;
							<label for="mbr-<?php echo esc_attr( $tax_key ); ?>-plural"><?php esc_html_e( 'Plural', 'modern-book-reviews' ); ?></label>
							<input id="mbr-<?php echo esc_attr( $tax_key ); ?>-plural" type="text"
								name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_<?php echo esc_attr( $tax_key ); ?>_labels][plural]"
								value="<?php echo esc_attr( $tax_labels['plural'] ); ?>" class="regular-text" />
						</td>
					</tr>
					<?php if ( null !== $tax['slug_key'] ) : ?>
						<tr>
							<th scope="row">
								<label for="mbr-<?php echo esc_attr( $tax_key ); ?>-slug"><?php esc_html_e( 'URL-Slug', 'modern-book-reviews' ); ?></label>
							</th>
							<td>
								<code><?php echo esc_html( home_url( '/' ) ); ?></code>
								<input id="mbr-<?php echo esc_attr( $tax_key ); ?>-slug" type="text"
									name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[<?php echo esc_attr( $tax['slug_key'] ); ?>]"
									value="<?php echo esc_attr( $opt_raw( $tax['slug_key'], $tax['slug_default'] ) ); ?>" />
								<p class="description">
									<?php esc_html_e( 'Nur Kleinbuchstaben, Zahlen und Bindestriche. Änderungen aktualisieren die Permalinks automatisch.', 'modern-book-reviews' ); ?>
								</p>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Optionen', 'modern-book-reviews' ); ?></th>
						<td>
							<label>
								<?php echo $checkbox( 'rcno_' . $tax_key . '_hierarchical', Settings::tax_flag( $tax_key, 'hierarchical', '0' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
								<?php esc_html_e( 'Hierarchisch (wie Kategorien)', 'modern-book-reviews' ); ?>
							</label>
							<br />
							<label>
								<?php echo $checkbox( 'rcno_' . $tax_key . '_show', Settings::tax_flag( $tax_key, 'show', '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
								<?php esc_html_e( 'In Tabellen anzeigen (Spalte in der Rezensions-Übersicht)', 'modern-book-reviews' ); ?>
							</label>
							<br />
							<label>
								<?php echo $checkbox( 'rcno_' . $tax_key . '_filter', Settings::tax_flag( $tax_key, 'filter', '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
								<?php esc_html_e( 'Filter anzeigen (Dropdown-Filter in der Übersicht)', 'modern-book-reviews' ); ?>
							</label>
							<br />
							<label>
								<?php echo $checkbox( 'rcno_' . $tax_key . '_posts', Settings::tax_flag( $tax_key, 'posts', '0' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
								<?php esc_html_e( 'Mit normalen Beiträgen nutzen', 'modern-book-reviews' ); ?>
							</label>
						</td>
					</tr>
				</table>
			<?php endforeach; ?>
		</div>

		<div id="mbr-tab-komponenten" class="mbr-tab-panel">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Rezensionsbox', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'mbr_show_review_box', $hard( 'mbr_show_review_box' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Rezensionsbox auf Rezensionsseiten anzeigen', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-position"><?php esc_html_e( 'Position', 'modern-book-reviews' ); ?></label>
						<select id="mbr-position"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[mbr_review_box_position]">
							<option value="top" <?php selected( $opt_raw( 'mbr_review_box_position', 'top' ), 'top' ); ?>>
								<?php esc_html_e( 'Über dem Text', 'modern-book-reviews' ); ?>
							</option>
							<option value="bottom" <?php selected( $opt_raw( 'mbr_review_box_position', 'top' ), 'bottom' ); ?>>
								<?php esc_html_e( 'Unter dem Text', 'modern-book-reviews' ); ?>
							</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Buchdetails in der Box', 'modern-book-reviews' ); ?></th>
					<td>
						<p class="description"><?php esc_html_e( 'Welche Buchfelder als Detail-Zeilen unter dem Cover erscheinen:', 'modern-book-reviews' ); ?></p>
						<?php
						$book_fields = array(
							'rcno_show_isbn'        => __( 'ISBN', 'modern-book-reviews' ),
							'rcno_show_isbn13'      => __( 'ISBN-13', 'modern-book-reviews' ),
							'rcno_show_asin'        => __( 'ASIN', 'modern-book-reviews' ),
							'rcno_show_gr_id'       => __( 'Goodreads-ID', 'modern-book-reviews' ),
							'rcno_show_gr_url'      => __( 'Buch-URL', 'modern-book-reviews' ),
							'rcno_show_gr_rating'  => __( 'Goodreads-Bewertung', 'modern-book-reviews' ),
							'rcno_show_illustrator' => __( 'Illustration', 'modern-book-reviews' ),
							'rcno_show_pub_date'   => __( 'Erscheinungsdatum', 'modern-book-reviews' ),
							'rcno_show_pub_format' => __( 'Format', 'modern-book-reviews' ),
							'rcno_show_pub_edition' => __( 'Ausgabe/Edition', 'modern-book-reviews' ),
							'rcno_show_series_number' => __( 'Serienband', 'modern-book-reviews' ),
							'rcno_show_page_count' => __( 'Seitenzahl', 'modern-book-reviews' ),
						);

						foreach ( $book_fields as $field_key => $field_label ) {
							?>
							<label style="display:inline-block; margin-right:1.2em;">
								<?php echo $checkbox( $field_key, $comp( $field_key ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
								<?php echo esc_html( $field_label ); ?>
							</label>
							<?php
						}
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '5-Sterne-Bewertung', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_enable_star_rating_box', $hard( 'rcno_enable_star_rating_box' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Sterne-Bewertung des Buches in der Box anzeigen', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-star-color"><?php esc_html_e( 'Sternfarbe', 'modern-book-reviews' ); ?></label>
						<input id="mbr-star-color" type="color"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_star_rating_color]"
							value="<?php echo esc_attr( Settings::effective( 'rcno_star_rating_color', 'mbr_star_color', '#f5a623' ) ); ?>" />
						<br />
						<label for="mbr-star-bg"><?php esc_html_e( 'Hintergrundfarbe der Sterne', 'modern-book-reviews' ); ?></label>
						<input id="mbr-star-bg" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_star_background_color]"
							value="<?php echo esc_attr( Settings::color_value( 'rcno_star_background_color', '' ) ); ?>"
							placeholder="#ffffff – leer lassen für transparent" class="text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Kauf-Links', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_enable_purchase_links', $hard( 'rcno_enable_purchase_links' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Affiliate-/Kauflinks anzeigen', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-purchase-label"><?php esc_html_e( 'Beschriftung', 'modern-book-reviews' ); ?></label>
						<input id="mbr-purchase-label" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_store_purchase_links_label]"
							value="<?php echo esc_attr( Settings::effective( 'rcno_store_purchase_links_label', 'mbr_purchase_links_label', 'Kaufen bei:' ) ); ?>"
							class="regular-text" />
						<br />
						<label for="mbr-stores"><?php esc_html_e( 'Shops (kommagetrennt, als Auswahl im Editor)', 'modern-book-reviews' ); ?></label>
						<input id="mbr-stores" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_store_purchase_links]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_store_purchase_links', 'Amazon,Barnes & Noble,Kobo' ) ); ?>"
							class="regular-text" />
						<br />
						<label for="mbr-purchase-text-color"><?php esc_html_e( 'Button-Textfarbe', 'modern-book-reviews' ); ?></label>
						<input id="mbr-purchase-text-color" type="color"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_store_purchase_link_text_color]"
							value="<?php echo esc_attr( Settings::color_value( 'rcno_store_purchase_link_text_color', '#171717' ) ); ?>" />
						<label for="mbr-purchase-bg" style="margin-left:1em;"><?php esc_html_e( 'Button-Hintergrundfarbe', 'modern-book-reviews' ); ?></label>
						<input id="mbr-purchase-bg" type="color"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_store_purchase_link_background]"
							value="<?php echo esc_attr( Settings::color_value( 'rcno_store_purchase_link_background', '#ededed' ) ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Bewertungskriterien-Box', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_show_review_score_box', $hard( 'rcno_show_review_score_box' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Kriterien-Box (Noten/Zahlen) anzeigen, wenn pro Rezension aktiviert', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-score-bg"><?php esc_html_e( 'Hintergrundfarbe', 'modern-book-reviews' ); ?></label>
						<input id="mbr-score-bg" type="color"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_show_review_score_box_background]"
							value="<?php echo esc_attr( Settings::color_value( 'rcno_show_review_score_box_background', '#ffffff' ) ); ?>" />
						<label for="mbr-score-accent" style="margin-left:1em;"><?php esc_html_e( 'Akzentfarbe 1', 'modern-book-reviews' ); ?></label>
						<input id="mbr-score-accent" type="color"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_show_review_score_box_accent]"
							value="<?php echo esc_attr( Settings::color_value( 'rcno_show_review_score_box_accent', '#212121' ) ); ?>" />
						<label for="mbr-score-accent-2" style="margin-left:1em;"><?php esc_html_e( 'Akzentfarbe 2', 'modern-book-reviews' ); ?></label>
						<input id="mbr-score-accent-2" type="color"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_show_review_score_box_accent_2]"
							value="<?php echo esc_attr( Settings::color_value( 'rcno_show_review_score_box_accent_2', '#f5a623' ) ); ?>" />
						<br />
						<label>
							<?php echo $checkbox( 'rcno_enable_custom_review_score_criteria', $comp( 'rcno_enable_custom_review_score_criteria' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Feste Bewertungskriterien vorgeben (Labels sind dann im Editor nicht mehr frei editierbar)', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-fixed-criteria"><?php esc_html_e( 'Kriterien (kommagetrennt)', 'modern-book-reviews' ); ?></label>
						<input id="mbr-fixed-criteria" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_custom_review_score_criteria]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_custom_review_score_criteria', 'Plot,Characters,World' ) ); ?>"
							class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Besucher-Bewertung', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_enable_comment_ratings', $hard( 'rcno_enable_comment_ratings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Sterne-Bewertung im Kommentarformular aktivieren und anzeigen', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-comment-label"><?php esc_html_e( 'Beschriftung', 'modern-book-reviews' ); ?></label>
						<input id="mbr-comment-label" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_comment_rating_label]"
							value="<?php echo esc_attr( Settings::effective( 'rcno_comment_rating_label', 'mbr_comment_rating_label', 'Bewerte dieses Buch' ) ); ?>"
							class="regular-text" />
						<br />
						<label for="mbr-comment-color"><?php esc_html_e( 'Sternfarbe der Besucher-Bewertung', 'modern-book-reviews' ); ?></label>
						<input id="mbr-comment-color" type="color"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_comment_rating_star_color]"
							value="<?php echo esc_attr( Settings::effective( 'rcno_comment_rating_star_color', 'mbr_comment_rating_color', '#f5a623' ) ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mbr-excerpt-words"><?php esc_html_e( 'Auszug auf Archivseiten', 'modern-book-reviews' ); ?></label>
					</th>
					<td>
						<input id="mbr-excerpt-words" type="number" min="20" max="500" step="1"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_excerpt_word_count]"
							value="<?php echo esc_attr( (string) (int) $opt_raw( 'rcno_excerpt_word_count', '55' ) ); ?>" class="small-text" />
						<?php esc_html_e( 'Wörter (max. 500, min. 20)', 'modern-book-reviews' ); ?>
						<br />
						<label for="mbr-read-more"><?php esc_html_e( 'Weiterlesen-Text', 'modern-book-reviews' ); ?></label>
						<input id="mbr-read-more" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_excerpt_read_more]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_excerpt_read_more', 'Weiterlesen' ) ); ?>" class="regular-text" />
					</td>
				</tr>
			</table>
		</div>

		<div id="mbr-tab-extras" class="mbr-tab-panel">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Widgets', 'modern-book-reviews' ); ?></th>
					<td>
						<p class="description"><?php esc_html_e( 'Welche Widgets unter „Design → Widgets“ verfügbar sind:', 'modern-book-reviews' ); ?></p>
						<?php
						$widget_flags = array(
							'rcno_show_recent_reviews_widget' => __( 'Neueste Rezensionen', 'modern-book-reviews' ),
							'rcno_show_book_grid_widget'      => __( 'Buchraster', 'modern-book-reviews' ),
							'rcno_show_book_slider_widget'    => __( 'Buch-Slider', 'modern-book-reviews' ),
							'rcno_show_tag_cloud_widget'      => __( 'Schlagwort-Wolke', 'modern-book-reviews' ),
							'rcno_show_taxonomy_list_widget'  => __( 'Taxonomie-Liste', 'modern-book-reviews' ),
						);

						foreach ( $widget_flags as $widget_key => $widget_label ) {
							?>
							<label style="display:block; margin-bottom:0.4em;">
								<?php echo $checkbox( $widget_key, $comp( $widget_key ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
								<?php echo esc_html( $widget_label ); ?>
							</label>
							<?php
						}
						?>
						<p class="description">
							<?php esc_html_e( 'Hinweis: Die Original-Widgets „Currently Reading“ und „Rezensions-Kalender“ werden von diesem Neubau nicht angeboten.', 'modern-book-reviews' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Rezensions-Index-Seite', 'modern-book-reviews' ); ?></th>
					<td>
						<p class="description">
							<?php
							printf(
								/* translators: %s: Shortcode */
								esc_html__( 'Index aller Rezensionen per Shortcode %s einbinden (A–Z-Gruppierung wie im Original).', 'modern-book-reviews' ),
								'<code>[mbr-reviews-index]</code>'
							);
							?>
						</p>
						<label>
							<?php echo $checkbox( 'rcno_reviews_index_headers', $comp( 'rcno_reviews_index_headers' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Alphabetische Überschriften (A, B, C …) anzeigen', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label>
							<?php echo $checkbox( 'rcno_show_book_covers_index', $comp( 'rcno_show_book_covers_index' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Cover im Index anzeigen', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label>
							<?php echo $checkbox( 'rcno_reviews_ignore_articles', $comp( 'rcno_reviews_ignore_articles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Artikel beim Sortieren ignorieren (z. B. „The Hobbit“ unter H)', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-articles"><?php esc_html_e( 'Zu ignorierende Artikel (kommagetrennt)', 'modern-book-reviews' ); ?></label>
						<input id="mbr-articles" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_reviews_ignored_articles_list]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_reviews_ignored_articles_list', 'The,A,An' ) ); ?>"
							class="regular-text" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Sortierung der Autorennamen', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<input type="radio"
								name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_reviews_sort_names]"
								value="first_name_last_name" <?php checked( $opt_raw( 'rcno_reviews_sort_names', 'first_name_last_name' ), 'first_name_last_name' ); ?> />
							<?php esc_html_e( 'Vorname Nachname („Norbert Vokal“)', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label>
							<input type="radio"
								name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_reviews_sort_names]"
								value="last_name_first_name" <?php checked( $opt_raw( 'rcno_reviews_sort_names', 'first_name_last_name' ), 'last_name_first_name' ); ?> />
							<?php esc_html_e( 'Nachname, Vorname („Vokal, Norbert“)', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<div id="mbr-tab-vorlagen" class="mbr-tab-panel">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mbr-template"><?php esc_html_e( 'Rezensionsbox-Vorlage', 'modern-book-reviews' ); ?></label>
					</th>
					<td>
						<select id="mbr-template"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_review_template]">
							<option value="basic" <?php selected( $opt_raw( 'rcno_review_template', 'default' ), 'basic' ); ?>>
								<?php esc_html_e( 'Basic – minimal, ohne Rahmen', 'modern-book-reviews' ); ?>
							</option>
							<option value="default" <?php selected( $opt_raw( 'rcno_review_template', 'default' ), 'default' ); ?>>
								<?php esc_html_e( 'Default – klassische Box mit Rahmen', 'modern-book-reviews' ); ?>
							</option>
							<option value="metamor" <?php selected( $opt_raw( 'rcno_review_template', 'default' ), 'metamor' ); ?>>
								<?php esc_html_e( 'Metamor – Karte mit farbigem Kopfstreifen', 'modern-book-reviews' ); ?>
							</option>
							<option value="insert" <?php selected( $opt_raw( 'rcno_review_template', 'default' ), 'insert' ); ?>>
								<?php esc_html_e( 'Insert – transparent, im Textfluss', 'modern-book-reviews' ); ?>
							</option>
							<option value="simple" <?php selected( $opt_raw( 'rcno_review_template', 'default' ), 'simple' ); ?>>
								<?php esc_html_e( 'Simple – weiße Karte mit Schatten', 'modern-book-reviews' ); ?>
							</option>
							<option value="inverse" <?php selected( $opt_raw( 'rcno_review_template', 'default' ), 'inverse' ); ?>>
								<?php esc_html_e( 'Inverse – dunkle Box, heller Text', 'modern-book-reviews' ); ?>
							</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mbr-default-cover"><?php esc_html_e( 'Standard-Cover', 'modern-book-reviews' ); ?></label>
					</th>
					<td>
						<input id="mbr-default-cover" type="url"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_default_cover]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_default_cover', '' ) ); ?>"
							class="regular-text" placeholder="https://…" />
						<p class="description">
							<?php esc_html_e( 'URL eines Ersatzbildes, das angezeigt wird, wenn ein Buch kein eigenes Cover hat.', 'modern-book-reviews' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Eingebettete Listen', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_reviews_embedded_title_links', $comp( 'rcno_reviews_embedded_title_links' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Titel in Raster/Index mit der Rezension verlinken', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mbr-details-meta"><?php esc_html_e( 'Reihenfolge der Buchdetails', 'modern-book-reviews' ); ?></label>
					</th>
					<td>
						<input id="mbr-details-meta" type="text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_book_details_meta]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_book_details_meta', '' ) ); ?>"
							class="regular-text" placeholder="z. B. rcno_book_isbn,rcno_book_pub_date,rcno_publisher" />
						<p class="description">
							<?php esc_html_e( 'Kommagetrennte Meta-Keys. Leer = Standardreihenfolge. Steuert Auswahl und Reihenfolge der Detail-Zeilen in der Box.', 'modern-book-reviews' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mbr-custom-css"><?php esc_html_e( 'Eigenes CSS', 'modern-book-reviews' ); ?></label>
					</th>
					<td>
						<textarea id="mbr-custom-css" rows="8" class="large-text code"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_custom_styling]"><?php echo esc_textarea( (string) $opt_raw( 'rcno_custom_styling', '' ) ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Wird zusätzlich zum Plugin-Stylesheet geladen (ohne <style>-Tags, nur CSS-Regeln).', 'modern-book-reviews' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</div>

		<div id="mbr-tab-erweitert" class="mbr-tab-panel">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mbr-external-api"><?php esc_html_e( 'Externe Buch-API', 'modern-book-reviews' ); ?></label>
					</th>
					<td>
						<?php
						$api_value = $opt_raw( 'rcno_external_book_api', 'no-3rd-party' );
						$api_choices = array(
							'no-3rd-party' => __( 'Keine', 'modern-book-reviews' ),
							'google-books' => __( 'Google Books® API', 'modern-book-reviews' ),
							'good-reads'   => __( 'Goodreads® API', 'modern-book-reviews' ),
						);

						foreach ( $api_choices as $api_key => $api_label ) {
							?>
							<label style="display:block; margin-bottom:0.4em;">
								<input type="radio"
									name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_external_book_api]"
									value="<?php echo esc_attr( $api_key ); ?>" <?php checked( $api_value, $api_key ); ?> />
								<?php echo esc_html( $api_label ); ?>
							</label>
							<?php
						}
						?>
						<p class="description">
							<?php
							printf(
								/* translators: %s: freie Datenquelle */
								esc_html__( 'Wähle aus, welche Drittanbieter-API zum Holen von Buchdaten benutzt wird. Die entsprechende Option muss unten zusätzlich aktiviert werden. „Keine“ nutzt die freie Quelle Open Library ohne API-Key.', 'modern-book-reviews' )
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Einstellungen für Google Books®', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_enable_googlebooks', $comp( 'rcno_enable_googlebooks' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Externen Zugang zur Google Books® API aktivieren', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-googlebooks-key"><?php esc_html_e( 'Google Books® API Key', 'modern-book-reviews' ); ?></label>
						<input id="mbr-googlebooks-key" type="password" autocomplete="new-password"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_googlebooks_key]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_googlebooks_key', '' ) ); ?>"
							class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Verfügbar bei:', 'modern-book-reviews' ); ?>
							<a href="https://console.developers.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">https://console.developers.google.com/apis/credentials</a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Goodreads®-Einstellungen', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_enable_goodreads', $comp( 'rcno_enable_goodreads' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Zugang zur externen Goodreads® API aktivieren', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label for="mbr-goodreads-key"><?php esc_html_e( 'Goodreads® API Key', 'modern-book-reviews' ); ?></label>
						<input id="mbr-goodreads-key" type="password" autocomplete="new-password"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_goodreads_key]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_goodreads_key', '' ) ); ?>"
							class="regular-text" />
						<br />
						<label for="mbr-goodreads-secret"><?php esc_html_e( 'Goodreads® API Secret', 'modern-book-reviews' ); ?></label>
						<input id="mbr-goodreads-secret" type="password" autocomplete="new-password"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_goodreads_secret]"
							value="<?php echo esc_attr( $opt_raw( 'rcno_goodreads_secret', '' ) ); ?>"
							class="regular-text" />
						<p class="description">
							<?php esc_html_e( 'Verfügbar bei:', 'modern-book-reviews' ); ?>
							<a href="https://www.goodreads.com/api/keys" target="_blank" rel="noopener noreferrer">https://www.goodreads.com/api/keys</a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'JSON-LD-Schemata', 'modern-book-reviews' ); ?></th>
					<td>
						<label>
							<?php echo $checkbox( 'rcno_disable_book_schema', $hard( 'rcno_disable_book_schema' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Book-Schema deaktivieren (Ausgabe des JSON-LD-Markups für Bücher)', 'modern-book-reviews' ); ?>
						</label>
						<br />
						<label>
							<?php echo $checkbox( 'rcno_disable_review_schema', $hard( 'rcno_disable_review_schema' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Review-Schema deaktivieren (Ausgabe des JSON-LD-Markups für Rezensionen)', 'modern-book-reviews' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mbr-no-pluralization"><?php esc_html_e( 'Unzählbare Wörter', 'modern-book-reviews' ); ?></label>
					</th>
					<td>
						<textarea id="mbr-no-pluralization" rows="3" class="large-text"
							name="<?php echo esc_attr( Settings::OPTION_KEY ); ?>[rcno_no_pluralization]"><?php echo esc_textarea( (string) $opt_raw( 'rcno_no_pluralization', '' ) ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Erstelle eine Liste von Wörtern, für die es keine Ein- oder Mehrzahl gibt, z. B. „Butter“, „Leute“ (kommagetrennt).', 'modern-book-reviews' ); ?>
						</p>
						<br />
						<label>
							<?php echo $checkbox( 'rcno_disable_pluralization', $comp( 'rcno_disable_pluralization' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- intern escaped. ?>
							<?php esc_html_e( 'Automatische Mehrzahlbildung von Wörtern in den Einstellungen abschalten', 'modern-book-reviews' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Betrifft die automatische Beschriftung eigener Taxonomien (Ein-/Mehrzahl der Labels).', 'modern-book-reviews' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Import/Export-Einstellungen', 'modern-book-reviews' ); ?></th>
					<td>
						<label for="mbr-settings-file"><?php esc_html_e( 'Einstellungen importieren', 'modern-book-reviews' ); ?></label><br />
						<input id="mbr-settings-file" type="file" name="mbr_settings_file" accept=".json,application/json" form="mbr-import-form" />
						<button type="submit" class="button" form="mbr-import-form" name="mbr_import_settings" value="1">
							<?php esc_html_e( 'Einstellungen importieren', 'modern-book-reviews' ); ?>
						</button>
						<p class="description"><?php esc_html_e( 'Vorher gespeicherte Plugin-Einstellungen importieren.', 'modern-book-reviews' ); ?></p>
						<br />
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=' . SettingsPage::MENU_SLUG . '&mbr_export=1' ), 'mbr_export_settings' ) ); ?>">
							<?php esc_html_e( 'Einstellungen sichern', 'modern-book-reviews' ); ?>
						</a>
						<p class="description"><?php esc_html_e( 'Plugin-Einstellungen in einer Datei speichern (Download als JSON).', 'modern-book-reviews' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

	<?php submit_button(); ?>
	</form>

	<!-- Verstecktes Formular für den Einstellungs-Import (Erweitert-Tab).
	     Die Felder im Erweitert-Tab sind per form-Attribut zugeordnet. -->
	<form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=' . SettingsPage::MENU_SLUG ) ); ?>" enctype="multipart/form-data" id="mbr-import-form" style="display:none;">
		<?php wp_nonce_field( 'mbr_import_settings', '_mbr_import_nonce' ); ?>
		<input type="hidden" name="mbr_import_settings" value="1" />
	</form>

	<form method="post" action="" onsubmit="return confirm('<?php echo esc_js( __( 'Wirklich alle Einstellungen zurücksetzen?', 'modern-book-reviews' ) ); ?>');">
		<?php wp_nonce_field( 'mbr_reset_settings', '_mbr_reset_nonce' ); ?>
		<button type="submit" class="button button-link-delete" name="mbr_reset_settings" value="1">
			<?php esc_html_e( 'Einstellungen zurücksetzen', 'modern-book-reviews' ); ?>
		</button>
		<p class="description">
			<?php esc_html_e( 'Achtung: Mit dem Zurücksetzen gehen alle Plugin-Einstellungen verloren (die Rezensionen selbst bleiben erhalten).', 'modern-book-reviews' ); ?>
		</p>
	</form>

	<script>
		( function () {
			var links = document.querySelectorAll( '.mbr-tabs a' );
			var panels = document.querySelectorAll( '.mbr-tab-panel' );

			links.forEach( function ( link ) {
				link.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					links.forEach( function ( other ) { other.classList.remove( 'nav-tab-active' ); } );
					panels.forEach( function ( panel ) { panel.classList.remove( 'is-active' ); } );
					link.classList.add( 'nav-tab-active' );
					document.querySelector( link.getAttribute( 'href' ) ).classList.add( 'is-active' );
				} );
			} );
		} )();
	</script>
	<style>
		.mbr-tab-panel { display: none; margin-top: 1em; }
		.mbr-tab-panel.is-active { display: block; }
	</style>
</div>
