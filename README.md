# Bewertungsliste

Eine schlanke PHP-Webanwendung zum Erstellen, Verwalten und Bewerten von Listen mit 5-Sterne-Ratings und Kommentaren.  
**Hinweis:** Dieses Projekt wurde maßgeblich **mit ChatGPT 5** (Assistenz beim Konzipieren, Implementieren und Refactoring) entwickelt.

## Features

- **Nutzerkonten ohne E-Mail:** Anmeldung ausschließlich mit Nutzername + Passwort (Argon2id).
- **Listen & Items:** Eigene Listen anlegen (öffentlich/privat), Einträge hinzufügen.
- **Bewertungen:** 5-Sterne-Ratings pro Item, optionaler Kommentar, Live-Statistiken.
- **Sichtbarkeit:** Private Listen nur für Owner; seit **v0.5** sind **alle** Listen nur für angemeldete Nutzer sichtbar.
- **Account-Verwaltung:** Nutzername/Passwort ändern, Account löschen.
- **Admin-Bereich:** Nutzer und Listen verwalten (bearbeiten/löschen), Direktlinks in der Navigation (nur Admin).
- **Sicherheit:** CSRF-Schutz, sichere Sessions, Argon2id-Hashes.
- **UI/UX:** Responsives Layout (Materialize), Dark-Mode inkl. stabilen Formularen (Autofill-sicher).

## Tech-Stack

- **Backend:** PHP (entwickelt für **8.2.12**; getestet mit **8.3.6**), PDO (MariaDB/MySQL)
- **DB:** MariaDB
- **Webserver:** NGINX
- **Frontend:** Materialize CSS, Vanilla JS
- **Entwicklung:** VS Code via SSH (Ubuntu)

## Installation (Ubuntu, NGINX, MariaDB)

> Kurzüberblick – bitte an eure Umgebung anpassen.

1. **Repository klonen**
   ```bash
   git clone git@github.com:bfe0503/bewertungsliste.git
   cd bewertungsliste
   ```

2. **Dateirechte**
   - PHP-Sessions liegen unter `storage/sessions/`.  
     PHP-FPM-User braucht Schreibrechte.

3. **Konfiguration**
   - `config/config.php` prüfen/anpassen:
     ```php
     return [
       'app_env' => 'prod', // oder 'local'
       'db' => [
         'host'    => 'localhost',
         'port'    => 3306,
         'name'    => 'bewertungsliste',
         'charset' => 'utf8mb4',
         'user'    => 'bewertung_app',
         'pass'    => '***',
       ],
     ];
     ```

4. **Datenbank**
   - Schema einspielen (siehe `database/migrations/` – Tabellen: `users`, `lists`, `items`, `ratings`).
   - **Erststart/Seed:** Admin-Konto `admin` mit Passwort **`changeMe!23`** anlegen/prüfen (sofort ändern).

5. **NGINX**
   - Webroot auf `public/` setzen (Front-Controller `index.php`).
   - Nach Änderungen: `sudo nginx -t && sudo systemctl reload nginx`

6. **PHP-FPM**
   - Nach Deploy/Änderungen: `sudo systemctl reload php8.3-fpm`

## Entwicklung

- `app_env = 'local'` aktiviert ausführlichere Fehlerausgabe.
- Sessions werden in `storage/sessions/` gespeichert (eigenes Cookie, subfolder-safe).
- Routing via `public/index.php` (minimaler Router).

## Security-Hinweise

- Admin-Passwort **unbedingt** nach dem ersten Login ändern.
- Empfohlen: CSP/Security-Header in NGINX (CSP, Frame-Options, Referrer-Policy, Permissions-Policy, COOP/COEP).
- Argon2id-Parameter je nach Serverleistung ggf. erhöhen.

## Roadmap (Auszug)

- Brute-Force-Schutz am Login (Rate-Limit).
- Pagination/Infinite Scroll für große Listen.
- A11y-Verbesserungen (Screenreader-Feedback bei Sternen/Toasts).
- Export/Import (CSV/JSON) für Listen/Items/Ratings.

## Versionen

Siehe **CHANGELOG.md**.

Wichtige Meilensteine:
- **v0.5**: Login-Pflicht für alle Listen, Dark-Mode Formular-Fixes (Text/Caret, symmetrische Labels), Login-CSRF-Fix.
- **v0.4.0**: Username-only Auth, Account-Seite, Owner-CRUD für Listen, Admin-Bereich (Users/Lists).
- **v0.3.0**: UI-Verbesserungen (Dark-Mode-Kommentare), HEAD→GET-Router-Fix, Session-Härtung.
- **v0.1.0**: Erste deploybare Version.

## Lizenz

MIT (sofern nicht anders angegeben).

---

**Made with ❤️ & ChatGPT 5.**
