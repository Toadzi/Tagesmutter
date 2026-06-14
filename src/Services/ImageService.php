<?php

/**
 * Zweck: Bildverarbeitung: Erzeugen von Thumbnails aus hochgeladenen Fotos.
 */

namespace App\Services;

/**
 * Zentraler Service für Bild-Operationen (Thumbnails).
 */
class ImageService
{
    /**
     * Erstellt ein verkleinertes JPEG-Thumbnail.
     * Bei nicht unterstützten Formaten oder Fehlern wird die Originaldatei kopiert.
     *
     * @param string $source  Pfad zur Originaldatei
     * @param string $dest    Zielpfad für das Thumbnail
     * @param int    $maxSize maximale Kantenlänge (längste Seite)
     */
    public static function createThumbnail(string $source, string $dest, int $maxSize = 400): void
    {
        $info = @getimagesize($source);
        if (!$info) {
            copy($source, $dest);
            return;
        }

        $mime = $info['mime'];
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png'  => @imagecreatefrompng($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : null,
            default      => null,
        };

        if (!$image) {
            copy($source, $dest);
            return;
        }

        $origW = imagesx($image);
        $origH = imagesy($image);

        $ratio = min($maxSize / $origW, $maxSize / $origH);
        $newW  = max(1, (int)($origW * $ratio));
        $newH  = max(1, (int)($origH * $ratio));

        $thumb = imagecreatetruecolor($newW, $newH);

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagejpeg($thumb, $dest, 85);

        imagedestroy($image);
        imagedestroy($thumb);
    }
}
