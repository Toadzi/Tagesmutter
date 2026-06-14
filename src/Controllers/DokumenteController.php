<?php

/**
 * Zweck: Dokumentenablage je Kind: Upload, Download und Löschen von Dateien.
 */

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use App\Services\FileService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DokumenteController
{
    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods',
        'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp',
    ];

    private const MIME_ICONS = [
        'application/pdf' => 'bi-file-earmark-pdf',
        'application/msword' => 'bi-file-earmark-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'bi-file-earmark-word',
        'application/vnd.ms-excel' => 'bi-file-earmark-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'bi-file-earmark-excel',
        'image/jpeg' => 'bi-file-earmark-image',
        'image/png' => 'bi-file-earmark-image',
        'image/gif' => 'bi-file-earmark-image',
        'image/webp' => 'bi-file-earmark-image',
        'text/plain' => 'bi-file-earmark-text',
    ];

    public function upload(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $db = Database::get();

        $files = $uploadedFiles['dokumente'] ?? [];
        if (!is_array($files)) {
            $files = [$files];
        }

        $dir = __DIR__ . '/../../data/uploads/dokumente/' . $kindId;
        FileService::ensureDir($dir);

        foreach ($files as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = $file->getClientFilename();
            $ext = FileService::extension($file);

            if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
                continue;
            }

            $safeFilename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $file->moveTo($dir . '/' . $safeFilename);

            $mime = mime_content_type($dir . '/' . $safeFilename) ?: 'application/octet-stream';
            $groesse = filesize($dir . '/' . $safeFilename);

            $stmt = $db->prepare("
                INSERT INTO dokumente (kind_id, dateiname, originalname, typ, groesse, beschreibung)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $kindId,
                'dokumente/' . $kindId . '/' . $safeFilename,
                $originalName,
                $mime,
                $groesse,
                $data['beschreibung'] ?? '',
            ]);
        }

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=stammdaten#dokumente');
    }

    public function download(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $stmt = $db->prepare("SELECT * FROM dokumente WHERE id = ? AND kind_id = ?");
        $stmt->execute([$id, $kindId]);
        $dok = $stmt->fetch();

        if (!$dok) {
            return $response->withStatus(404);
        }

        $filePath = __DIR__ . '/../../data/uploads/' . $dok['dateiname'];
        if (!file_exists($filePath)) {
            return $response->withStatus(404);
        }

        $response->getBody()->write(file_get_contents($filePath));
        return $response
            ->withHeader('Content-Type', $dok['typ'] ?: 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($dok['originalname']) . '"')
            ->withHeader('Content-Length', (string)$dok['groesse']);
    }

    public function delete(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $stmt = $db->prepare("SELECT dateiname FROM dokumente WHERE id = ? AND kind_id = ?");
        $stmt->execute([$id, $kindId]);
        $dok = $stmt->fetch();

        if ($dok) {
            $filePath = __DIR__ . '/../../data/uploads/' . $dok['dateiname'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $db->prepare("DELETE FROM dokumente WHERE id = ?")->execute([$id]);
        }

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=stammdaten#dokumente');
    }

    public static function getIcon(string $mime): string
    {
        return self::MIME_ICONS[$mime] ?? 'bi-file-earmark';
    }

    public static function formatGroesse(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
