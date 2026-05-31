-- Kinder (Stammdaten)
CREATE TABLE IF NOT EXISTS kinder (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vorname TEXT NOT NULL,
    nachname TEXT NOT NULL,
    geburtsdatum DATE NOT NULL,
    geschlecht TEXT CHECK(geschlecht IN ('m','w','d')),
    adresse TEXT,
    plz TEXT,
    ort TEXT,
    foto_pfad TEXT,
    muttersprache TEXT,
    betreuung_start DATE,
    betreuung_ende DATE,
    betreuungszeiten TEXT,
    allergien TEXT,
    medikamente TEXT,
    besonderheiten TEXT,
    arzt_name TEXT,
    arzt_telefon TEXT,
    foto_freigabe INTEGER DEFAULT 0,
    aktiv INTEGER DEFAULT 1,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Eltern / Sorgeberechtigte / Abholberechtigte
CREATE TABLE IF NOT EXISTS kontakte (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind_id INTEGER NOT NULL,
    rolle TEXT,
    name TEXT NOT NULL,
    telefon TEXT,
    mobil TEXT,
    email TEXT,
    notfall INTEGER DEFAULT 0,
    abholberechtigt INTEGER DEFAULT 0,
    FOREIGN KEY (kind_id) REFERENCES kinder(id) ON DELETE CASCADE
);

-- Tagebuch-Einträge
CREATE TABLE IF NOT EXISTS tagebuch (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind_id INTEGER NOT NULL,
    datum DATE NOT NULL,
    titel TEXT,
    text TEXT NOT NULL,
    stimmung INTEGER,
    kategorien TEXT,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kind_id) REFERENCES kinder(id) ON DELETE CASCADE
);

-- Foto-Portfolio
CREATE TABLE IF NOT EXISTS fotos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind_id INTEGER NOT NULL,
    tagebuch_id INTEGER,
    dateiname TEXT NOT NULL,
    thumbnail TEXT,
    beschreibung TEXT,
    aufnahme_datum DATE,
    hochgeladen_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kind_id) REFERENCES kinder(id) ON DELETE CASCADE,
    FOREIGN KEY (tagebuch_id) REFERENCES tagebuch(id) ON DELETE SET NULL
);

-- Meilensteine
CREATE TABLE IF NOT EXISTS meilensteine (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind_id INTEGER NOT NULL,
    datum DATE NOT NULL,
    bildungsbereich TEXT,
    beschreibung TEXT NOT NULL,
    FOREIGN KEY (kind_id) REFERENCES kinder(id) ON DELETE CASCADE
);

-- Anwesenheit
CREATE TABLE IF NOT EXISTS anwesenheit (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind_id INTEGER NOT NULL,
    datum DATE NOT NULL,
    ankunft TIME,
    abholung TIME,
    abwesend INTEGER DEFAULT 0,
    abwesenheitsgrund TEXT,
    notiz TEXT,
    FOREIGN KEY (kind_id) REFERENCES kinder(id) ON DELETE CASCADE,
    UNIQUE(kind_id, datum)
);

-- Entwicklungsberichte
CREATE TABLE IF NOT EXISTS berichte (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind_id INTEGER NOT NULL,
    titel TEXT,
    zeitraum_von DATE,
    zeitraum_bis DATE,
    inhalt TEXT,
    status TEXT DEFAULT 'entwurf',
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kind_id) REFERENCES kinder(id) ON DELETE CASCADE
);

-- Indizes
CREATE INDEX IF NOT EXISTS idx_tagebuch_kind_datum ON tagebuch(kind_id, datum DESC);
CREATE INDEX IF NOT EXISTS idx_anwesenheit_kind_datum ON anwesenheit(kind_id, datum DESC);
CREATE INDEX IF NOT EXISTS idx_fotos_kind ON fotos(kind_id);
CREATE INDEX IF NOT EXISTS idx_kontakte_kind ON kontakte(kind_id);
CREATE INDEX IF NOT EXISTS idx_meilensteine_kind ON meilensteine(kind_id);
