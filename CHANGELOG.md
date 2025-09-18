# Changelog
Alle nennenswerten Änderungen an diesem Projekt werden in dieser Datei dokumentiert.  
Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.0.0/).

## [Unreleased]

## [v0.4.3] – Admin-Login-Redirect & Globales Admin-Menü
### Hinzugefügt
- Admins werden nach dem Login automatisch auf **/admin** weitergeleitet.
- Globales Admin-Menü (Desktop & Mobil) nur für Admins: **Admin**, **Users**, **Lists**.

### Geändert
- Admin-Dashboard enthält Quicklinks zu **/admin/users** und **/admin/lists**.

### Migration
- Keine Datenbank-Änderungen erforderlich.

## [v0.4.2] – Owner-Username in UI
### Hinzugefügt
- Anzeige des **Owner-Usernames** in:
  - **/lists** (Public Lists und eigene Listen),
  - **/lists/{id}** (Listendetail),
  - **Admin → Lists**.

### Migration
- Keine Datenbank-Änderungen erforderlich.

## [v0.4.1] – 5-Sterne-Bewertung & Kommentare (AJAX)
### Hinzugefügt
- 5-Sterne-Bewertung pro Item inkl. Live-Aktualisierung von Ø-Wert und Anzahl.
- Optionaler **Kommentar** je Bewertung; Kommentare können entfernt werden.
- AJAX-Endpoint `/items/{id}/rate` mit CSRF-Handling und Antwort mit neuem Token.
- Anzeige der **letzten Kommentare** pro Item.

### Migration
- `ratings` um Spalte `comment TEXT NULL` erweitert.

## [v0.4.0] – Username-only Auth, Account & Admin
### Hinzugefügt
- Authentifizierung nur mit **Username + Passwort** (ohne E-Mail).
- **Account-Seite**: Username ändern, Passwort ändern.
- **Listen**: Owner kann eigene Listen erstellen, bearbeiten, löschen (Public/Private).
- **Admin-Panel**:
  - **Users**: Passwörter setzen, Nutzer löschen.
  - **Lists**: Listen einsehen und löschen (kaskadierendes Entfernen von Items/Ratings).

### Geändert
- Models/Views an DB-Schema angepasst (`user_id`, `is_public`, `items`, `ratings`).

### Migration
- DB-Schema wie im Repo beschrieben (Foreign Keys, `is_public` als Flag).

---

Links:
- [Releases](../../releases)
- [Vergleiche/PRs](../../pulls)
## [v0.5] — 2025-09-18
### Added
- **Login-Pflicht für alle Listen**: `/lists` und `/lists/{id}` sind nur noch für angemeldete Nutzer sichtbar. Gäste werden nach `/login` umgeleitet.

### Fixed
- **Login-Formular**: fehlendes `csrf`-Hidden-Feld ergänzt (verhinderte Logins durch CSRF-Fehler).

### Improved
- **Dark Mode Formulare**: verlässlich dunkle Eingabefelder (auch bei Browser-Autofill), gut lesbare Text- & Caret-Farben.
- **Form-Layout**: Labels als Block mit einheitlichem Abstand → symmetrische, ruhigere Karten.
