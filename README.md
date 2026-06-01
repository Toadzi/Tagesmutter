# Tagesmutter-Verwaltung

Eine einfach zu bedienende Webanwendung zur Verwaltung von Tageskindern — für Tagesmütter und Tagesväter.

## Funktionen

- **Kinderverwaltung** — Stammdaten, Profilfoto, Notfallkontakte, Dokumente
- **Tagebuch** — Einträge mit Fotos, Kategorien und Stimmungserfassung; Einträge optional als Meilenstein speichern
- **Meilensteine** — Entwicklungsschritte nach Bildungsbereichen dokumentieren
- **Anwesenheit** — Tägliches Einchecken/Auschecken mit Monatsübersicht, PDF- und CSV-Export
- **Portfolio** — Fotogalerie je Kind mit PDF-Export
- **Entwicklungsberichte** — Freie und strukturierte Berichte mit konfigurierbaren Textbausteinen
- **Dashboard** — Tagesübersicht mit Anwesenheitsstatus, Geburtstags-Erinnerungen und letzten Tagebucheinträgen
- **Benutzerverwaltung** — Rollen: Admin, Eltern (nur eigenes Kind), Leserecht
- **Admin-Bereich** — Einstellungen, Benutzerverwaltung, Textbausteine anpassen

## Technologie

| Komponente | Version |
|-----------|---------|
| PHP | ≥ 8.2 |
| Slim Framework | 4.x |
| Datenbank | SQLite (über PDO) |
| Templates | Twig 3 |
| CSS-Framework | Bootstrap 5.3 |
| PDF-Export | DomPDF |
| Abhängigkeiten | PHP-DI, Intervention Image |

## Installation

### Voraussetzungen

- PHP ≥ 8.2 mit den Erweiterungen `pdo_sqlite`, `gd`, `fileinfo`

### Schritte

```bash
# 1. Repository klonen
git clone https://github.com/Toadzi/Tagesmutter.git
cd tagesmutter

# 2. Konfigurationsdatei anlegen
cp config.example.php config.php
# config.php nach Bedarf anpassen (z.B. Unterverzeichnis-Installation)

# 3. Datenverzeichnis anlegen und Schreibrechte setzen
mkdir -p data/uploads/fotos data/uploads/thumbs data/backups
chmod -R 775 data/

### Erster Login

Beim ersten Start wird automatisch ein Admin-Benutzer angelegt:

| Feld | Wert |
|------|------|
| Benutzername | `admin` |
| Passwort | `admin` |

**Passwort nach dem ersten Login im Admin-Bereich ändern.**

## Deployment auf einem Server

### Root-Installation (`https://example.com/`)

`config.php`:
```php
define('APP_BASE_PATH', '');
```

`public/.htaccess` ist bereits enthalten. Für Nginx siehe `deploy/nginx-root.conf`.

### Unterverzeichnis-Installation (`https://example.com/tagesmutter/`)

`config.php`:
```php
define('APP_BASE_PATH', '/tagesmutter');
```

Für Apache `public/.htaccess` anpassen (siehe `deploy/htaccess-subdir.txt`),
für Nginx siehe `deploy/nginx-subdir.conf`.

### Wichtige Hinweise

- Das `data/`-Verzeichnis enthält Datenbank und Uploads — **nicht öffentlich zugänglich** machen
- Bei HTTPS-Betrieb `session.cookie_secure` in `public/index.php` aktivieren
- `config.php` ist in `.gitignore` und wird nicht eingecheckt

## Verzeichnisstruktur

```
├── config.example.php     # Konfigurationsvorlage
├── config.php             # Lokale Konfiguration (nicht im Repo)
├── data/                  # Datenbank, Uploads, Backups (nicht im Repo)
│   ├── tagesmutter.db
│   └── uploads/
├── deploy/                # Webserver-Konfigurationsvorlagen
├── migrations/            # SQL-Migrationsskripte
├── public/                # Web-Root (Document Root des Webservers)
│   ├── index.php
│   └── .htaccess
├── src/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Database.php
│   └── routes.php
├── templates/             # Twig-Templates
└── vendor/                # Composer-Abhängigkeiten (nicht im Repo)
```

## Benutzerverwaltung

| Rolle | Rechte |
|-------|--------|
| `admin` | Vollzugriff, Adminbereich, alle Kinder |
| `eltern` | Lesezugriff auf das eigene Kind (kein Schreiben) |
| `leserecht` | Lesezugriff auf alle Kinder |

## Lizenz

Privates Projekt — alle Rechte vorbehalten.
