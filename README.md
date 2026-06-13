## Kurzbeschreibung des Projekts

* **Modul:** Interaktive Medien 4 (FS26)
* **Themenfeld:** IoT-Applikation zum Thema Eltern mit kleinen Kindern
* **Name des Projekts:** *MiniSteps*
* **Team WebApp:** *MiniSteps*


* **Welches Problem im Alltag von Eltern mit kleinen Kindern wird gelöst?**

Das Projekt umfasst die Umsetzung einer schlanken und leicht bedienbaren Webapp für
Tagesmütter/Tagesväter zur Verwaltung der betreuten Kinder, der täglichen Beobachtungen
und der Kommunikation mit den Eltern. Ziel ist es, den administrativen Aufwand zu
reduzieren und gleichzeitig die pädagogische Dokumentation transparent und nachvollziehbar
zu machen. Eltern, die ihr Kind in Tagespflege geben, erhalten so jederzeit Einblick in
den Betreuungs- und Entwicklungsverlauf ihres Kindes.

* **Was ist der „Sinn und Zweck“ des Systems?**

Die Applikation bündelt Stammdaten, ein Tagebuch mit Fotos, Anwesenheitslisten,
Entwicklungs­meilensteine, Quartals-/Entwicklungsberichte, eine Dokumentenablage sowie ein
Unfallprotokoll in einer Lösung. Tagesmütter erhalten dadurch einen sauberen Überblick über
jedes Kind; Eltern können – nach Freischaltung – auf den eigenen Datenstand und den
Bildungsverlauf ihres Kindes zugreifen.

> Hinweis zu dieser Abgabe: Diese Version läuft mit der dateibasierten Datenbank **SQLite**
> (keine separate Datenbank-Installation nötig). Die Anwendung unterstützt optional auch
> MySQL/MariaDB; für die Abgabe wird jedoch die SQLite-Variante verwendet.


### UX & Konzeption

*In diesem Teil werden die gemeinsamen Schritte aus der UX-Abgabe dokumentiert, damit sich
hier alles vollständig an einem Ort befindet (betrifft WebApp).*

* **Figma:** https://www.figma.com/design/6cqeY5vYbL7rdDU2SYS8hm/IM-4-%E2%80%93-App-Konzeption---MiniSteps?node-id=78-325
* **User Flow + Screen Flow:**
<img width="4946" height="3056" alt="Tagesmutter" src="https://github.com/user-attachments/assets/54e94ea4-3798-44cb-9eb4-551bd7fc312a" />

*  **Moodboard:**
<img width="1600" height="1600" alt="Moodboard" src="https://github.com/user-attachments/assets/27730dbc-cdae-4f62-af7f-1038d506a398" />


* **Welche Features waren angedacht / wurden umgesetzt?**
  * Login mit Rollen (Tagesmutter/Admin, Eltern, Leserecht)
  * Kinder-Stammdaten inkl. Profilfoto und Kontakten (Eltern/Abholberechtigte)
  * Tagebuch mit Fotos, Stimmung und Bildungsbereichs-Kategorien
  * Foto-Portfolio je Kind inkl. PDF-Export
  * Anwesenheit mit Schnell-Check-in/-out, Monatsübersicht, PDF-/CSV-Export
  * Entwicklungs-Meilensteine
  * Entwicklungsberichte mit wiederverwendbaren Textbausteinen und PDF-Export
  * Dokumentenablage je Kind
  * Unfallprotokoll (je Kind und global) inkl. PDF-Export
  * Admin-Bereich: Einstellungen, Benutzerverwaltung, Textbausteine

* **Welche Features wurden nicht umgesetzt? (Warum)**
  * Push-/E-Mail-Benachrichtigungen an Eltern – aus Zeitgründen zurückgestellt.
  * Echtzeit-Kommunikation/Chat – Scope-Reduktion zugunsten der Kerndokumentation.


### Setup

* **WebApp (Demo):** https://web-app-server.de/ministep/login

#### Installationsanleitung WebApp (SQLite-Variante)

**1. Was benötige ich an Infrastruktur?**
Ein Webserver (Apache oder Nginx) mit **PHP ≥ 8.2** oder – für lokale Tests – der
eingebaute PHP-Entwicklungsserver. Eine **separate Datenbank ist nicht nötig**, da SQLite
dateibasiert arbeitet (die Datenbank liegt als Datei unter `data/app.sqlite`).

**2. Was muss ich auf meinem Webserver installieren?**
* PHP ≥ 8.2 mit den Erweiterungen `pdo_sqlite`, `gd`, `fileinfo`, `mbstring`
* [Composer](https://getcomposer.org) (PHP-Paketmanager)

**3. Projekt klonen und einrichten**
```bash
# Repository klonen
git clone <REPO-URL> ministep
cd ministep

# PHP-Abhängigkeiten installieren
composer install

# Konfigurationsdatei anlegen
cp config.example.php config.php

# Datenverzeichnis beschreibbar machen
mkdir -p data/uploads/fotos data/uploads/thumbs data/backups
chmod -R 775 data/

# Lokal starten (oder Document-Root des Webservers auf den Ordner public/ setzen)
php -S 127.0.0.1:8080 -t public
```
Die App ist anschließend unter http://127.0.0.1:8080 erreichbar.

**4. Wie kann ich die Datenbank importieren?**
Es gibt zwei Wege:
* **Automatisch (empfohlen):** Beim ersten Aufruf legt die App das SQLite-Schema selbst an
  (Migrationen aus `migrations/`) inkl. Standard-Admin und Textbausteinen – es ist kein
  manueller Import nötig.
* **Aus der mitgelieferten SQL-Datei:** Alternativ kann die beigelegte Datenbank importiert
  werden:
  ```bash
  # leeres Schema mit Grunddaten (ohne Demodaten)
  sqlite3 data/app.sqlite < tagesmutter-datenbank-ohne-demodaten.sql
  # ODER inkl. Demodaten (Beispielkinder, Tagebuch, Anwesenheit …)
  sqlite3 data/app.sqlite < tagesmutter-datenbank.sql
  ```

**5. Wo muss ich die DB-Credentials eintragen?**
Bei der **SQLite-Variante sind keine Datenbank-Zugangsdaten nötig.** 

**6. Unterverzeichnis-Installation**
Läuft die App in einem Unterordner (z. B. `…/tagesmutter/`), in `config.php` den Basis-Pfad
setzen: `define('APP_BASE_PATH', '/tagesmutter');`


## Technische Details

**Tech-Stack:** PHP 8.2, [Slim 4](https://www.slimframework.com/) (Micro-Framework),
[Twig 3](https://twig.symfony.com/) (Templates), PDO **SQLite**, PHP-DI (Dependency
Injection), DomPDF (PDF-Export), Intervention Image / GD (Bildbearbeitung), Bootstrap 5.3
(Frontend). Autoloading per Composer/PSR-4 (`App\` → `src/`).

* **Projektstruktur / Code-Struktur:**
```
├── public/              # Web-Root (Document Root)
│   ├── index.php        # Front Controller: Session, DI-Container, Middleware, Migrationen
│   └── .htaccess
├── src/
│   ├── routes.php       # zentrale Routen-Definitionen (URL → Controller-Methode)
│   ├── Database.php     # PDO-Verbindung (SQLite/MySQL) + Migrationen + UPSERT-Helper
│   ├── Controllers/     # 14 Controller (Kinder, Tagebuch, Anwesenheit, Berichte, …)
│   ├── Repositories/    # gebündelte DB-Abfragen (KinderRepository, FotoRepository)
│   ├── Services/        # Fachlogik (FileService, ImageService, PdfService, BackupService)
│   ├── Config/          # Bildungsbereiche (zentrale Konstanten)
│   ├── Http/            # Redirect-Helper
│   └── Middleware/      # AuthMiddleware (Login-/Rollenprüfung)
├── templates/           # Twig-Templates (Views) + PDF-Vorlagen
├── migrations/          # SQL-Migrationen (SQLite); migrations/mysql/ für MySQL
├── data/                # SQLite-DB, Uploads, Backups (nicht im Web-Root)
├── docs/                # Doku: ERM, User Flow, Twig, Moodboard
└── config.php           # lokale Konfiguration (Basis-Pfad, DB-Treiber)
```

* **Weg der Daten (wie reden die Dateien miteinander):**
  1. Jede Anfrage trifft den Front Controller `public/index.php` (Session-Start,
     DI-Container, Twig mit globalen Variablen, Middleware-Kette, Migrationen).
  2. Die `AuthMiddleware` prüft Login und Rolle und leitet ggf. auf `/login` um.
  3. `src/routes.php` ordnet die URL einer **Controller**-Methode zu.
  4. Der Controller holt/schreibt Daten über **Repositories**/`Database` (PDO, Prepared
     Statements) und nutzt **Services** (Datei-Upload, Thumbnails, PDF, Backup).
  5. Das Ergebnis wird an ein **Twig-Template** übergeben und als HTML ausgeliefert;
     Exporte werden über DomPDF als PDF bzw. als CSV zurückgegeben.

* **ERM:**
  <img width="1600" height="1600" alt="ERM" src="https://github.com/user-attachments/assets/ed684555-42c4-4cd3-ba5e-fe105122cd16" />
  (Quelle: `docs/ERM.svg`).
  Zentrale Entität ist **`kinder`**. Daran hängen über den Fremdschlüssel `kind_id` jeweils
  **1:N** die Tabellen `kontakte`, `tagebuch`, `fotos`, `meilensteine`, `anwesenheit`,
  `berichte`, `dokumente` und `unfaelle`. Zusätzlich verweist `fotos.tagebuch_id` optional
  auf `tagebuch` (beim Löschen eines Tagebucheintrags `ON DELETE SET NULL`, das Foto bleibt
  im Portfolio). Ein **Benutzer** (`benutzer`) kann über `kind_id` mit genau einem Kind
  verknüpft sein (Eltern-Login). `textbausteine` und `einstellungen` stehen eigenständig.
  Das Löschen eines Kindes räumt per `ON DELETE CASCADE` alle abhängigen Datensätze mit auf.

* **Authentifizierung:**
  Session-basiert. Passwörter werden mit `password_hash()` (bcrypt) gespeichert und mit
  `password_verify()` geprüft. Nach erfolgreichem Login wird die Session-ID erneuert
  (`session_regenerate_id`, Schutz gegen Session-Fixation). Es gibt drei Rollen:
  **admin** (Tagesmutter, Vollzugriff), **eltern** und **leserecht** (nur lesend). Die
  zentrale `AuthMiddleware` erzwingt: nicht eingeloggt → Weiterleitung auf `/login`;
  `/admin/*` nur für Rolle `admin`; Eltern/Leserecht sehen ausschließlich das **eigene**
  Kind (Zugriff auf fremde Kind-Seiten wird auf das eigene Kind umgeleitet). Session-Cookies
  sind auf `HttpOnly` und `SameSite=Strict` gesetzt.


## Known bugs

* Es gibt aktuell **keinen CSRF-Token** für Formulare; Schutz erfolgt über `SameSite=Strict`-
  Cookies. Für einen produktiven Einsatz wäre ein CSRF-Token sinnvoll.
* Beim Betrieb über HTTPS muss `session.cookie_secure` in `public/index.php` noch aktiviert
  werden (im Code als Kommentar vorbereitet).


## Umsetzungsprozess

* **Reflexion / Erfahrung / Lernfortschritt:**
  Ich habe gelernt, eine vollständige CRUD-Webapplikation mit sauberer Schichtung
  (Routing → Controller → Repository/Service → Datenbank → Template) aufzubauen. Besonders
  wertvoll war das Refactoring hin zu wiederverwendbaren Repositories/Services, wodurch
  Code-Duplikate deutlich reduziert wurden. Zusätzlich habe ich mein Wissen mit dem Umgang mit 
  KI (präzises Prompting etc.) weiter ausbauen können

* **Herausforderungen & Lösungen:**
  * Trennung von Darstellung und Logik konsequent über Twig (automatisches Escaping als
    XSS-Schutz, zentrale Layout-Vererbung). Siehe `docs/Templating-Twig.md`.

* **KI-Einsatz:**
  Für Refactoring (Beseitigung von Code-Duplikaten, Einführung von Repositories/Services),
  sowie die Dokumentation (Twig, User Flow inkl. CRUD,
  ERM-Schaubild, Moodboard) und Beispiel-/Demodaten wurde **Claude Code (Anthropic)**
  eingesetzt. Nutzen: schnelleres, konsistentes Arbeiten und Code-Reviews; alle Ergebnisse
  wurden geprüft und getestet.

* **Fazit:**
  Es ist eine fokussierte, datensparsame Lösung, die den Dokumentationsalltag einer Tagesmutter
  abbildet und sich lokal wie auf einem einfachen Webserver betreiben lässt. Ich habe viel gelernt,
  besonders besser KI in meinen Workflow zu integrieren. Im Nachhinein sind mir noch ein paar UX 
  Kleinigkeiten aufgefallen die noch fehlen für eine flüssige User Erfahrung. Diese möchte ich später beheben.
