<?php
/**
 * Deinstallation.
 *
 * Konservatives Verhalten: Rezensionen, Meta-Daten und Taxonomien
 * bleiben in jedem Fall erhalten (auch die Legacy-Daten des alten
 * Recencio-Plugins). Gelöscht werden nur die Plugin-eigenen Optionen.
 *
 * @package ModernBookReviews
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'mbr_version' );

/*
 * Die Rezensions-Einstellungen (`rcno_reviews_settings`) werden bewusst
 * NICHT gelöscht: Falls noch eine Installation des alten Recencio-Plugins
 * existiert oder später wieder aktiviert wird, gehen dadurch keine
 * Konfigurationen verloren.
 */
