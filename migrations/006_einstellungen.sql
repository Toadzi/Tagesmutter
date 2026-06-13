CREATE TABLE IF NOT EXISTS einstellungen (
    schluessel TEXT PRIMARY KEY,
    wert TEXT NOT NULL,
    beschreibung TEXT,
    aktualisiert_am DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO einstellungen (schluessel, wert, beschreibung) VALUES
('app_name',                  'MiniStep',             'Name der App (Sidebar & Titel)'),
('app_subtitle',              'Verwaltung',           'Untertitel in der Sidebar'),
('bericht_zeitraum_monate',   '6',                    'Standardzeitraum für neue Entwicklungsberichte (Monate)'),
('backup_aktiv',              '1',                    'Automatisches Datenbank-Backup aktiviert'),
('backup_tage_aufbewahren',   '14',                   'Backup-Dateien für X Tage aufbewahren'),
('geburtstag_erinnerung_tage','14',                   'Geburtstags-Reminder X Tage im Voraus anzeigen');
