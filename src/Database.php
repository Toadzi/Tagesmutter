<?php

/**
 * Zweck: Zentrale PDO-Datenbankverbindung (SQLite) und Migrations-Runner für das Schema.
 */

namespace App;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $dbPath = __DIR__ . '/../data/app.sqlite';
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            self::$instance = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            self::$instance->exec('PRAGMA journal_mode=WAL');
            self::$instance->exec('PRAGMA foreign_keys=ON');
        }

        return self::$instance;
    }

    public static function migrate(): void
    {
        $db = self::get();

        // Migrations-Tracking-Tabelle anlegen (einmalig)
        $db->exec("
            CREATE TABLE IF NOT EXISTS _migrations (
                filename TEXT PRIMARY KEY,
                ran_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $migrationsDir = __DIR__ . '/../migrations';
        $files = glob($migrationsDir . '/*.sql');
        sort($files);

        $check = $db->prepare("SELECT COUNT(*) FROM _migrations WHERE filename = ?");

        foreach ($files as $file) {
            $name = basename($file);
            $check->execute([$name]);
            if ($check->fetchColumn() > 0) {
                continue; // bereits ausgeführt
            }

            $sql = file_get_contents($file);
            $db->exec($sql);

            $db->prepare("INSERT INTO _migrations (filename) VALUES (?)")->execute([$name]);
        }
    }
}
