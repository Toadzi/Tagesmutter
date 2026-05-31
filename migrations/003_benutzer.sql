CREATE TABLE IF NOT EXISTS benutzer (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    benutzername TEXT NOT NULL UNIQUE,
    passwort_hash TEXT NOT NULL,
    rolle TEXT NOT NULL DEFAULT 'eltern' CHECK(rolle IN ('admin','eltern','leserecht')),
    kind_id INTEGER,
    aktiv INTEGER DEFAULT 1,
    letzter_login DATETIME,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kind_id) REFERENCES kinder(id) ON DELETE SET NULL
);

-- Standard-Admin (Passwort: admin123 — bitte sofort ändern!)
INSERT OR IGNORE INTO benutzer (name, benutzername, passwort_hash, rolle)
VALUES ('Tagesmutter', 'admin', '$2y$12$q5c4DStcZlXljjoy9znphu1NKlgnu08tLdTYEbH75UrunnXhF6NDq', 'admin');

CREATE INDEX IF NOT EXISTS idx_benutzer_benutzername ON benutzer(benutzername);
