-- Dokumente (Stammdaten)
CREATE TABLE IF NOT EXISTS dokumente (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind_id INTEGER NOT NULL,
    dateiname TEXT NOT NULL,
    originalname TEXT NOT NULL,
    typ TEXT,
    groesse INTEGER,
    beschreibung TEXT,
    hochgeladen_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kind_id) REFERENCES kinder(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_dokumente_kind ON dokumente(kind_id);
