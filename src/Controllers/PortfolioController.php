<?php

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use App\Repositories\FotoRepository;
use App\Repositories\KinderRepository;
use App\Services\FileService;
use App\Services\ImageService;
use App\Services\PdfService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class PortfolioController
{
    public function __construct(private Twig $twig)
    {
    }

    public function index(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $db = Database::get();

        $kind = KinderRepository::find($kindId);
        if (!$kind) {
            return $response->withStatus(404);
        }

        $tagebuchEintraege = $db->prepare("SELECT id, datum, titel FROM tagebuch WHERE kind_id = ? ORDER BY datum DESC");
        $tagebuchEintraege->execute([$kindId]);

        return $this->twig->render($response, 'portfolio/index.twig', [
            'kind' => $kind,
            'fotos' => FotoRepository::getByKindIdWithTagebuchTitel($kindId),
            'tagebuch_eintraege' => $tagebuchEintraege->fetchAll(),
        ]);
    }

    public function upload(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $db = Database::get();

        $files = $uploadedFiles['fotos'] ?? [];
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                continue;
            }

            $ext = FileService::extension($file);
            if (!FileService::isAllowedImage($ext)) {
                continue;
            }

            $filename = uniqid() . '.' . $ext;
            $dir = __DIR__ . '/../../data/uploads/fotos/' . $kindId;
            FileService::ensureDir($dir);

            $file->moveTo($dir . '/' . $filename);

            // Create thumbnail
            $thumbDir = __DIR__ . '/../../data/uploads/thumbs';
            FileService::ensureDir($thumbDir);
            $thumbName = 'thumb_' . $filename;
            ImageService::createThumbnail($dir . '/' . $filename, $thumbDir . '/' . $thumbName, 300);

            $stmt = $db->prepare("
                INSERT INTO fotos (kind_id, tagebuch_id, dateiname, thumbnail, beschreibung, aufnahme_datum)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $kindId,
                !empty($data['tagebuch_id']) ? (int)$data['tagebuch_id'] : null,
                'fotos/' . $kindId . '/' . $filename,
                'thumbs/' . $thumbName,
                $data['beschreibung'] ?? '',
                $data['aufnahme_datum'] ?: date('Y-m-d'),
            ]);
        }

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=portfolio');
    }

    public function update(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $stmt = $db->prepare("UPDATE fotos SET beschreibung = ?, tagebuch_id = ? WHERE id = ? AND kind_id = ?");
        $stmt->execute([
            $data['beschreibung'] ?? '',
            !empty($data['tagebuch_id']) ? (int)$data['tagebuch_id'] : null,
            $id,
            $kindId,
        ]);

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=portfolio');
    }

    public function delete(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $stmt = $db->prepare("SELECT dateiname, thumbnail FROM fotos WHERE id = ? AND kind_id = ?");
        $stmt->execute([$id, $kindId]);
        $foto = $stmt->fetch();

        if ($foto) {
            $base = __DIR__ . '/../../data/uploads/';
            if (file_exists($base . $foto['dateiname'])) {
                unlink($base . $foto['dateiname']);
            }
            if ($foto['thumbnail'] && file_exists($base . $foto['thumbnail'])) {
                unlink($base . $foto['thumbnail']);
            }

            $stmt = $db->prepare("DELETE FROM fotos WHERE id = ?");
            $stmt->execute([$id]);
        }

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=portfolio');
    }

    public function exportPdf(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;

        $kind = KinderRepository::find($kindId);

        $html = PdfService::renderTemplate('portfolio_pdf', [
            'kind' => $kind,
            'fotos' => FotoRepository::getByKindIdForExport($kindId),
            'uploads_path' => __DIR__ . '/../../data/uploads/',
        ]);

        return PdfService::generateResponse($response, $html, 'Portfolio_' . $kind['vorname'] . '_' . $kind['nachname'] . '.pdf');
    }

}
