<?php

/**
 * Zweck: Automatisches Datenbank-Backup (Kopie der SQLite-Datei) inkl. Aufräumen alter Backups.
 */

namespace App\Services;

class BackupService
{
    private string $dataDir;
    private string $backupDir;
    private int $keepDays;

    public function __construct(int $keepDays = 14)
    {
        $this->dataDir = __DIR__ . '/../../data';
        $this->backupDir = $this->dataDir . '/backups';
        $this->keepDays = $keepDays;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function run(): void
    {
        $today = date('Y-m-d');
        $backupFile = $this->backupDir . '/backup_' . $today . '.sqlite';

        // Only one backup per day
        if (file_exists($backupFile)) {
            return;
        }

        $dbFile = $this->dataDir . '/app.sqlite';
        if (!file_exists($dbFile)) {
            return;
        }

        copy($dbFile, $backupFile);
        $this->cleanup();
    }

    private function cleanup(): void
    {
        $cutoff = strtotime('-' . $this->keepDays . ' days');
        $files = glob($this->backupDir . '/backup_*.sqlite');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
