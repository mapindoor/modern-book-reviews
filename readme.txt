=== Modern Book Reviews ===
Contributors: mapindoor
Tags: book, review, rezension, buch, schema, isbn, rating
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 1.1.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Moderner, datenkompatibler Ersatz für das eingestellte Recencio Book Reviews.

== Beschreibung ==

Modern Book Reviews ist eine vollständige Neuentwicklung für Buchrezensionen
auf WordPress und ersetzt das nicht mehr gepflegte "Recencio Book Reviews"
(wzy Media / Kemory Grubb), dessen Hersteller nicht mehr existiert.

**Datenkompatibilität:** Das Plugin nutzt dieselben internen Bezeichner wie
das Original – Post Type `rcno_review`, Taxonomien `rcno_author`,
`rcno_genre`, `rcno_series`, `rcno_publisher` und alle Meta-Keys
(`rcno_book_isbn`, `rcno_admin_rating`, `rcno_review_buy_links` …).
Bestehende Rezensionen, Permalinks und Einstellungen bleiben beim Umstieg
vollständig erhalten. Ein Rollback zum alten Plugin ist jederzeit möglich.

**Funktionen (Kernumfang):**

* Custom Post Type für Rezensionen mit Gutenberg-Unterstützung (Block Editor)
* Buchdaten-Metaboxen im Editor (Original-Layout): Buchbeschreibung,
  Allgemeine Information, Bewertung der Besprechung, Kauflinks,
  ISBN-Abruf, Buchdeckel, 5-Sterne-Bewertung
* Automatischer Buchdaten-Abruf per ISBN über Open Library und Google Books
  (serverseitig, REST-Endpoint, 12h Cache, keine API-Keys nötig)
* Redakteurs-Bewertung (0–5 Sterne, halbe Sterne) und Kriterien-Box
  (Buchstaben-Noten A–F oder Zahlen 0–10)
* Kauf-/Affiliate-Links pro Rezension (Multi-Store)
* Besucher-Bewertung über das Kommentarformular mit Sterne-Auswahl
* Strukturierte Daten: JSON-LD für Book- und Review-Schema (Google Rich
  Results kompatibel)
* Rezensionsbox auf der Rezensionsseite (oben/unten positionierbar)
* Shortcodes: `[mbr-review-box]`, `[mbr-reviews-grid]`,
  `[mbr-purchase-links]` – plus Legacy-Aliase `[rcno-review-box]`,
  `[rcno-reviews-grid]`, `[rcno-purchase-links]` für bestehende Inhalte
* Einstellungsseite als Untermenüpunkt „Einstellungen“ im Reviews-Menü

**Sicherheit (Unterschiede zum Original):**

* PHP 8.2+ mit `declare`-strenger Typisierung, Namespaces, PSR-4-Autoloader
* Kein direkter `$_POST`/`$_GET`-Zugriff im Editor: alle Buchdaten laufen über
  die WordPress-REST-API (register_post_meta mit Schema, Sanitize-Callbacks
  und `auth_callback`-Capability-Prüfung)
* CVE-2024-33648 (Stored XSS): Kauflinks und alle Textausgaben werden
  konsequent über `esc_url_raw`/`sanitize_text_field` beim Speichern und
  `esc_html`/`esc_url` bei der Ausgabe behandelt
* Farb-Einstellungen werden als Hex-Werte validiert und nur als
  CSS-Variablen ausgegeben (keine Inline-Style-Injection)
* ISBN-Endpoint nur für `edit_posts`-Berechtigte, feste Host-Allow-List,
  `wp_safe_remote_get`, Transient-Caching
* Kommentar-Bewertung mit Nonce-Schutz und Wertebereichsprüfung (1–5)
* Uninstall löscht keine Inhaltsdaten

== Installation ==

1. Altes Plugin **deaktivieren** (nicht löschen – es kann als Fallback
   erhalten bleiben; beide Plugins sollten nicht gleichzeitig aktiv sein,
   da sie denselben Post Type registrieren).
2. Diesen Ordner nach `wp-content/plugins/modern-book-reviews/` kopieren.
3. Plugin aktivieren.
4. Optional: Einstellungen unter *Einstellungen → Buchrezensionen* prüfen.
5. Alte Rezensionen erscheinen sofort im Menüpunkt des Post Types; alte
   Shortcodes funktionieren über die Legacy-Aliase weiter.

== Changelog ==

= 1.1.2 =
* Buchbox: Der Buchtitel wird nicht mehr ein zweites Mal als Überschrift
  innerhalb der Box angezeigt – auf der Rezensionsseite steht der Titel
  nur noch einmal als Beitragstitel. Das schema.org-Book-Markup der Box
  behält den Namen über ein unsichtbares meta-Tag

= 1.1.1 =
* Das Roh-Panel „Individuelle Felder“ (kryptische rcno_-Kodierungen
  ohne Beschriftung) erscheint nicht mehr im Rezensions-Editor – wie
  im Original-Plugin. Alle Buchdaten werden über die beschrifteten
  Metaboxen gepflegt; die gespeicherten Werte bleiben unverändert

= 1.1.0 =
* Neue Seite „Erweiterungen“ im Reviews-Menü (wie im Original) mit
  fünf nativ integrierten Erweiterungen, je einzeln aktivierbar:
  Autorenbox (mit Social-Media-Feldern im User-Profil), Eigene
  Metadaten-Felder für die Buchdetails, Buchdeckel automatisch per
  ISBN laden, Beiträge <-> Rezensionen wandeln und Social Media
  Sharing (Facebook, X, WhatsApp, Telegram, Pinterest, E-Mail;
  Position wählbar)
* Bewertungskriterien-Box: Bewertungstyp „Sterne“ wird jetzt als
  Sterne angezeigt, und die Position „Oben/Unten“ wird respektiert

= 1.0.11 =
* Layout der Buchbox: ISBN, ISBN-13 und Buch-URL erscheinen jetzt
  kleiner und dezent unterhalb der Buchbeschreibung (statt in
  normaler Größe zwischen Autoren und Beschreibung); lange URLs
  brechen sauber um

= 1.0.10 =
* Einstellungsseite jetzt als Untermenüpunkt „Einstellungen“ direkt im
  Reviews-Menü (statt unter den allgemeinen WordPress-Einstellungen)
* Kauflinks: über „Anderer Shop …“ lässt sich jetzt ein beliebiger
  Shop als freier Text eintragen (z. B. eigene Buchhandlungen wie
  „Neue Erde“ oder „Lehmkuhl“) – nicht mehr nur die drei vordefinierten
* Behoben: Der Buch-Link (Buch-URL) erschien in der Buchbox nicht, wenn
  die übernommene Reihenfolge-Einstellung des Original-Plugins das
  Feld nicht enthielt. Fehlende Felder werden jetzt am Ende ergänzt
  statt ausgeblendet
* Shop-Slugs („amazon“) werden im Frontend ansehnlich ausgegeben

= 1.0.9 =
* Der Rezensions-Editor zeigt jetzt wieder Metaboxen im Original-Layout:
  „Buchbeschreibung/Synopse“, „Allgemeine Information“ (Flex-Raster mit
  Titel, Illustrator, Veröffentlichungsdatum, Format, Ausgabe, Band,
  Seitenzahl, GoodReads ID, ISBN13, ASIN, Durchschnitt, Buch-URL),
  „Bewertung der Besprechung“ (Aktivieren, Bewertungstyp, Position,
  Kriterien-Tabelle), „Kauflinks“ (Shop/Link-Tabelle mit
  „Link hinzufügen“), seitlich „ISBN“ mit Abruf-Button, „Buchdeckel“
  (Mediathek + eigene URL) und „5-Sterne-Bewertung“
* Entfernen aller Kauflink-Zeilen + Speichern löscht die gespeicherten
  Kauflinks (damit lässt sich der Legacy-Amazon-Eintrag endgültig
  entfernen)
* Die Gutenberg-Sidebar wurde durch die Metaboxen ersetzt

= 1.0.8 =
* Behoben: Die Gutenberg-Sidebar war unsichtbar, weil sie ohne den
  PluginSidebar-Container registriert wurde. Jetzt erscheint oben rechts
  im Editor ein Buch-Symbol; darüber öffnet sich das Panel
  „Buchrezensionen“ mit Buchdaten, Bewertung und Kauf-/Affiliate-Links
* Legacy-Alias: Installationen, die die Goodreads-Bewertung unter
  „rcno_book_gr_review“ statt „rcno_book_gr_rating“ gespeichert haben,
  zeigen den Wert jetzt korrekt in den Buchdetails an

= 1.0.7 =
* Buch-URL (rcno_book_gr_url) wird in den Buchdetails jetzt wie im
  Original-Plugin als klickbarer Link ausgegeben (z. B. zur Shop-Seite
  des Buches bei deiner Buchhandlung)

= 1.0.6 =
* Erweitert-Tab wie im Original komplett: Auswahl der externen Buch-API
  (rcno_external_book_api: Keine/Google Books®/Goodreads®), Aktivierung
  und API-Key bzw. API-Secret (rcno_enable_googlebooks, rcno_googlebooks_key,
  rcno_enable_goodreads, rcno_goodreads_key, rcno_goodreads_secret)
* ISBN-Abruf folgt der API-Einstellung: Google Books mit Key bzw. Goodreads
  mit Key werden bevorzugt; „Keine“ nutzt die freie Quelle Open Library
* Unzählbare Wörter (rcno_no_pluralization) und Mehrzahlbildung abschalten
  (rcno_disable_pluralization): steuert jetzt die automatische Ein-/Mehrzahl-
  Beschriftung eigener Taxonomien, wie im Original-Plugin
* Import/Export der Einstellungen als JSON-Datei mit Nonce- und
  Berechtigungsprüfung, Import läuft durch die normale Sanitize-Logik

= 1.0.5 =
* Einstellungsseite: neue Tabs „Extras" (Widgets, Index-Seite,
  Sortierung der Autorennamen) und „Vorlagen" (Box-Vorlagen basic/default/
  metamor/insert/simple/inverse, Standard-Cover, eingebettete Titel-Links,
  Reihenfolge der Buchdetails, eigenes CSS) – wie im Original-Plugin
* Komponenten-Tab: 12 Buchdetail-Felder einzeln schaltbar (rcno_show_*),
  Shop-Liste und Button-Farben der Kauf-Links, Sternen-Hintergrundfarbe,
  Farben der Kriterien-Box, feste Bewertungskriterien
* 5 Widgets wie im Original: Neueste Rezensionen, Buchraster, Buch-Slider,
  Schlagwort-Wolke, Taxonomie-Liste (Legacy-Schalter rcno_show_*_widget;
  „Currently Reading" und „Kalender" bewusst nicht umgesetzt)
* Neuer Shortcode [mbr-reviews-index] / [rcno-reviews-index]: A–Z-Index
  mit alphabetischen Überschriften, Covern, Artikel-Filter
* Gutenberg-Sidebar folgt jetzt den Komponenten-Einstellungen: Felder
  werden ausgeblendet, Shops als Auswahlliste, feste Kriterien ohne
  freie Labels, neue Felder Goodreads-ID/URL/Bewertung und Cover-URL
* Rezensionsbox: Standard-Cover-Fallback, Reihenfolge der Detail-Zeilen
  aus rcno_book_details_meta, Goodreads-Zeilen
* Fix: beschädigte Meta-Registrierung (rcno_admin_rating) repariert

= 1.0.0 =
* Erste Version: vollständiger datenkompatibler Neubau des Kernumfangs von
  Recencio Book Reviews mit moderner Architektur und härterem
  Sicherheitsmodell.

= 1.0.1 =
* Admin-Übersicht: WordPress-„Autor"-Spalte entfernt (wie im Original);
  Taxonomie-Spalten folgen jetzt den Einstellungen (rcno_author_show etc.)
* Taxonomie-Beschriftungen werden aus den bestehenden Einstellungen gelesen

= 1.0.2 =
* Rezensionsbox: Buchbeschreibung (rcno_book_description) wird jetzt wie im
  Original innerhalb der Box angezeigt (75 Wörter, per Filter anpassbar)
* Sternebewertung des Redakteurs steht jetzt unter dem Cover (wie im Original)

= 1.0.4 =
* „Einstellungen"-Link in der Aktionsleiste des Plugin-Eintrags
  (plugins.php) wie im Original-Plugin

= 1.0.3 =
* Einstellungsseite komplett neu mit den Tabs des Original-Plugins:
  Allgemein, Taxonomien, Komponenten, Erweitert
* Allgemein: Rezensionen auf der Homepage (rcno_reviews_on_homepage), im
  RSS-Feed (rcno_reviews_in_rss), in der REST-API (rcno_reviews_in_rest),
  Archiv-Darstellung ganzer Text/Auszug (rcno_reviews_archive) und
  Gutenberg-Unterstützung (rcno_reviews_in_gutenberg) greifen jetzt –
  die vorhanden Werte der alten Recencio-Installation werden übernommen
* Taxonomien: Beschriftungen, Hierarchisch, In Tabellen anzeigen, Filter
  anzeigen und Mit Beiträgen nutzen sind pro Taxonomie einstellbar
  (Legacy-Keys rcno_{tax}_labels/_hierarchical/_show/_filter/_posts)
* Eigene Zusatz-Taxonomien aus der Recencio-„Taxonomie-Auswahl"
  (rcno_taxonomy_selection) werden registriert und sind konfigurierbar
* Komponenten: 5-Sterne-Bewertung, Sternfarbe, Kauf-Links + Beschriftung,
  Kriterien-Box und Besucher-Bewertung (Label/Sternfarbe) nutzen jetzt
  die Original-Options-Schlüssel (rcno_*)
* Archiv-Auszug mit Cover und Weiterlesen-Link (rcno_excerpt_word_count,
  rcno_excerpt_read_more); Buchdetail-Reihenfolge folgt
  rcno_book_details_meta
* Erweitert: Book-/Review-JSON-LD-Schema einzeln deaktivierbar
  (rcno_disable_book_schema/rcno_disable_review_schema) und
  Einstellungen-Zurücksetzen-Button wie im Original
* Wichtig: Beim Speichern wird die bestehende Options-Struktur jetzt
  erhalten (Merge statt Ersatz); alte Schlüssel wie rcno_book_details_meta
  oder API-Keys gehen nicht mehr verloren
