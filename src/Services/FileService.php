<?php

/**
 * Zweck: Helfer für Datei-Uploads: Verzeichnisse anlegen, Endungen ermitteln und erlaubte Bildtypen prüfen.
 */

namespace App\Services;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Zentraler Helper für Datei-Uploads:
 * - erlaubte Erweiterungen
 * - Verzeichnis-Erzeugung
 * - sichere Extension-Ermittlung
 */
class FileService
{
    /** @var string[] Erlaubte Bild-Dateitypen (Fotos, Portfolio, Tagebuch) */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Legt ein Verzeichnis rekursiv an, falls es noch nicht existiert.
     */
    public static function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Liefert die Dateiendung einer hochgeladenen Datei in Kleinbuchstaben.
     */
    public static function extension(UploadedFileInterface $file): string
    {
        return strtolower(pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION));
    }

    /**
     * Prüft, ob die Erweiterung zu einem zulässigen Bildformat gehört.
     */
    public static function isAllowedImage(string $ext): bool
    {
        return in_array(strtolower($ext), self::IMAGE_EXTENSIONS, true);
    }
}
