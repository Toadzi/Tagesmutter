-- UNIQUE-Index als Schutz gegen künftige Duplikate
CREATE UNIQUE INDEX IF NOT EXISTS idx_textbausteine_unique ON textbausteine(bildungsbereich, text);
