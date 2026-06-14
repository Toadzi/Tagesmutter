<?php

/**
 * Zweck: Datenzugriff für Fotos: Abfragen je Kind bzw. Tagebucheintrag, inkl. Gruppierung.
 */

namespace App\Repositories;

use App\Database;

/**
 * Zentraler Zugriff auf die Tabelle `fotos`.
 * Ersetzt in-Controller-Queries und konsolidiert die "Fotos je Tagebucheintrag"-Grouping-Logik.
 */
class FotoRepository
{
    /**
     * Fotos eines Kindes, sortiert nach Upload-Datum absteigend.
     *
     * @param int $kindId
     * @param int $limit   0 = kein Limit
     * @return array<int,array<string,mixed>>
     */
    public static function getByKindId(int $kindId, int $limit = 0): array
    {
        $sql = "SELECT * FROM fotos WHERE kind_id = ? ORDER BY hochgeladen_am DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }
        $stmt = Database::get()->prepare($sql);
        $stmt->execute([$kindId]);
        return $stmt->fetchAll();
    }

    /**
     * Fotos eines Kindes, sortiert nach Aufnahme-Datum aufsteigend (für PDF-Export).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getByKindIdForExport(int $kindId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM fotos WHERE kind_id = ? ORDER BY aufnahme_datum ASC"
        );
        $stmt->execute([$kindId]);
        return $stmt->fetchAll();
    }

    /**
     * Fotos eines Kindes inkl. Titel des zugehörigen Tagebucheintrags (Portfolio-Ansicht).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getByKindIdWithTagebuchTitel(int $kindId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT f.*, t.titel as tagebuch_titel
             FROM fotos f
             LEFT JOIN tagebuch t ON t.id = f.tagebuch_id
             WHERE f.kind_id = ?
             ORDER BY f.aufnahme_datum DESC, f.hochgeladen_am DESC"
        );
        $stmt->execute([$kindId]);
        return $stmt->fetchAll();
    }

    /**
     * Fotos zu mehreren Tagebucheinträgen, gruppiert nach tagebuch_id.
     * Nutzt Prepared Statements mit Platzhaltern (kein String-Concat in SQL).
     *
     * @param int[] $tagebuchIds
     * @return array<int,array<int,array<string,mixed>>> tagebuch_id => Liste von Fotos
     */
    public static function getGroupedByTagebuchIds(array $tagebuchIds): array
    {
        if (!$tagebuchIds) {
            return [];
        }
        // Nur Integer-IDs zulassen
        $tagebuchIds = array_map('intval', $tagebuchIds);
        $placeholders = implode(',', array_fill(0, count($tagebuchIds), '?'));

        $stmt = Database::get()->prepare(
            "SELECT * FROM fotos WHERE tagebuch_id IN ({$placeholders}) ORDER BY hochgeladen_am"
        );
        $stmt->execute($tagebuchIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $f) {
            $grouped[$f['tagebuch_id']][] = $f;
        }
        return $grouped;
    }
}
