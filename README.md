# Modern Book Reviews

**Modern Book Reviews** ist ein WordPress-Plugin für Buchrezensionen – datenkompatibler Nachfolger von *Recencio Book Reviews* (1.66.0). Bestehende Rezensionen, Buchdaten und Einstellungen werden beim Umstieg vollständig übernommen.

## Funktionen

- **Rezensions-Post-Typ** „Rezensionen" mit eigenem Admin-Menü
- **Buchdetails-Metaboxen**: Buchbeschreibung/Synopse, allgemeine Informationen (Titel, Autor, Verlag, Erscheinungsjahr, Seiten, ISBN/ISBN-13 …), Bewertung (0–5 Sterne, positionierbar oben/unten), Kauflinks (inkl. Freitext „Anderer Shop …")
- **Buchdeckel** über die WordPress-Mediathek, **ISBN-Abruf** per OpenLibrary/Google Books über die integrierte REST-Schnittstelle `/mbr/v1/isbn/`
- **Erweiterungen-Seite** (Reviews → Erweiterungen) mit 5 nativen Erweiterungen:
  - Autorenbox mit Social-Media-Profilen (Facebook, Twitter/X, Instagram, Goodreads)
  - Eigene Metadaten-Felder (`Schlüssel::Label`)
  - Automatischer Buchdeckel per ISBN
  - Konverter: Beiträge ↔ Rezensionen
  - Social-Sharing-Buttons (Netzwerk- und Positionsauswahl)
- **Einstellungen** als Untermenü unter „Reviews"
- **Shortcodes und Widgets** zur Anzeige von Rezensionen
- **SEO**: Automatisiertes `BookReview`-Schema-Markup (schema.org)
- Kommentarbewertungen optional aktivierbar

## Installation

### Per ZIP (empfohlen)

1. `Releases` oben rechts aufrufen, das neueste `modern-book-reviews.zip` herunterladen
2. In WordPress: **Plugins → Installieren → Hochladen** → ZIP auswählen → **Jetzt installieren**
3. Falls Recencio Book Reviews aktiv war: dieses **deaktivieren** (nicht löschen!), dann Modern Book Reviews **aktivieren**

### Per Git

```bash
cd wp-content/plugins
git clone https://github.com/mapindoor/modern-book-reviews.git
```

Anschließend in WordPress aktivieren. Updates künftig per `git pull` im Plugin-Ordner.

## Umstieg von Recencio Book Reviews

Das Plugin liest alle Recencio-Meta-Felder (`rcno_review_*`) und Einstellungen direkt weiter. Ein Export/Import ist nicht nötig; die Daten bleiben in der WordPress-Datenbank.

## Updates

- **Per ZIP**: Neue Version aus den [Releases](../../releases) herunterladen und in WordPress hochladen (überschreibt nur Dateien, Daten bleiben erhalten).
- **Per Git**: `git pull` im Plugin-Ordner ausführen.
- Neue Releases entstehen automatisch: Wird ein Versions-Tag `v*` (z. B. `v1.1.1`) gesetzt, baut die GitHub-Action ein installierbares ZIP und hängt es an den Release.

## Voraussetzungen

- WordPress 6.0 oder höher
- PHP 7.4 oder höher (empfohlen: 8.x)

## Lizenz

GPL-2.0-or-later – wie WordPress selbst.
