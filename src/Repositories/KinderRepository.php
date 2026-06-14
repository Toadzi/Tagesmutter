<?php

/**
 * Zweck: Datenzugriff für Kinder (z. B. einzelnen Datensatz per ID laden).
 */

namespace App\Repositories;

use App\Database;

/**
 * Zentraler Zugriff auf die Tabelle `kinder`.
 */
class KinderRepository
{
    /**
     * Lädt einen Kind-Datensatz anhand der ID.
     *
     * @return array<string,mixed>|null Datensatz oder null, wenn nicht gefunden
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare("SELECT * FROM kinder WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
