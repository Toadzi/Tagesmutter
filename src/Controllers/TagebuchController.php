<?php

/**
 * Zweck: Tagebuch je Kind: Liste/Filter, Eintrag anlegen/bearbeiten/löschen, Foto-Upload, optional als Meilenstein.
 */

namespace App\Controllers;

use App\Config\Bildungsbereiche;
use App\Database;
use App\Http\Redirect;
use App\Repositories\FotoRepository;
use App\Repositories\KinderRepository;
use App\Services\FileService;
use App\Services\ImageService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class TagebuchController
{
    public function __construct(private Twig $twig)
    {
    }

    public function index(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $db = Database::get();
        $params = $request->getQueryParams();

        $kind = KinderRepository::find($kindId);
        if (!$kind) {
            return $response->withStatus(404);
        }

        $where = "kind_id = ?";
        $bindings = [$kindId];

        if (!empty($params['kategorie'])) {
            $where .= " AND kategorien LIKE ?";
            $bindings[] = '%"' . $params['kategorie'] . '"%';
        }
        if (!empty($params['von'])) {
            $where .= " AND datum >= ?";
            $bindings[] = $params['von'];
        }
        if (!empty($params['bis'])) {
            $where .= " AND datum <= ?";
            $bindings[] = $params['bis'];
        }

        $stmt = $db->prepare("SELECT * FROM tagebuch WHERE {$where} ORDER BY datum DESC, erstellt_am DESC");
        $stmt->execute($bindings);
        $eintraege = $stmt->fetchAll();

        // Fotos je Eintrag nachladen
        $fotos = FotoRepository::getGroupedByTagebuchIds(
            array_column($eintraege, 'id')
        );

        return $this->twig->render($response, 'tagebuch/index.twig', [
            'kind' => $kind,
            'eintraege' => $eintraege,
            'fotos_by_eintrag' => $fotos,
            'filter' => $params,
            'kategorien' => Bildungsbereiche::KEYS,
        ]);
    }

    public function store(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $kategorien = isset($data['kategorien']) ? json_encode($data['kategorien']) : '[]';

        $stmt = $db->prepare("
            INSERT INTO tagebuch (kind_id, datum, titel, text, stimmung, kategorien)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $kindId,
            $data['datum'] ?: date('Y-m-d'),
            $data['titel'] ?? '',
            $data['text'],
            $data['stimmung'] ?: null,
            $kategorien,
        ]);

        $tagebuchId = (int)$db->lastInsertId();
        $this->handleFotoUploads($request, $kindId, $tagebuchId, $data['aufnahme_datum'] ?? date('Y-m-d'));

        if (!empty($data['als_meilenstein'])) {
            $this->createMeilenstein($db, $kindId, $data);
            $_SESSION['success'] = 'Eintrag und Meilenstein gespeichert.';
        } else {
            $_SESSION['success'] = 'Tagebucheintrag gespeichert.';
        }

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=tagebuch');
    }

    public function edit(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $kind = KinderRepository::find($kindId);

        $eintrag = $db->prepare("SELECT * FROM tagebuch WHERE id = ? AND kind_id = ?");
        $eintrag->execute([$id, $kindId]);

        return $this->twig->render($response, 'tagebuch/edit.twig', [
            'kind' => $kind,
            'eintrag' => $eintrag->fetch(),
            'kategorien' => Bildungsbereiche::KEYS,
        ]);
    }

    public function update(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $kategorien = isset($data['kategorien']) ? json_encode($data['kategorien']) : '[]';

        $stmt = $db->prepare("
            UPDATE tagebuch SET datum = ?, titel = ?, text = ?, stimmung = ?, kategorien = ?
            WHERE id = ? AND kind_id = ?
        ");
        $stmt->execute([
            $data['datum'] ?: date('Y-m-d'),
            $data['titel'] ?? '',
            $data['text'],
            $data['stimmung'] ?: null,
            $kategorien,
            $id,
            $kindId,
        ]);

        $this->handleFotoUploads($request, $kindId, $id, $data['aufnahme_datum'] ?? date('Y-m-d'));

        if (!empty($data['als_meilenstein'])) {
            $this->createMeilenstein($db, $kindId, $data);
            $_SESSION['success'] = 'Eintrag und Meilenstein aktualisiert.';
        } else {
            $_SESSION['success'] = 'Eintrag aktualisiert.';
        }

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=tagebuch');
    }

    public function delete(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        // Fotos des Eintrags → tagebuch_id auf NULL setzen (Fotos bleiben im Portfolio)
        $db->prepare("UPDATE fotos SET tagebuch_id = NULL WHERE tagebuch_id = ? AND kind_id = ?")
           ->execute([$id, $kindId]);

        $stmt = $db->prepare("DELETE FROM tagebuch WHERE id = ? AND kind_id = ?");
        $stmt->execute([$id, $kindId]);

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=tagebuch');
    }

    private function createMeilenstein(\PDO $db, int $kindId, array $data): void
    {
        // Bildungsbereich: erste gewählte Kategorie übernehmen
        $bildungsbereich = isset($data['kategorien'][0]) ? $data['kategorien'][0] : '';

        // Beschreibung: Titel bevorzugen, sonst ersten 250 Zeichen des Textes
        $beschreibung = !empty($data['titel'])
            ? $data['titel']
            : mb_substr($data['text'], 0, 250);

        $db->prepare("INSERT INTO meilensteine (kind_id, datum, bildungsbereich, beschreibung) VALUES (?,?,?,?)")
           ->execute([
               $kindId,
               $data['datum'] ?: date('Y-m-d'),
               $bildungsbereich,
               $beschreibung,
           ]);
    }

    private function handleFotoUploads(Request $request, int $kindId, int $tagebuchId, string $datum): void
    {
        $uploadedFiles = $request->getUploadedFiles();
        $files = $uploadedFiles['tagebuch_fotos'] ?? [];
        if (!is_array($files)) {
            $files = [$files];
        }

        $db = Database::get();
        $dir = __DIR__ . '/../../data/uploads/fotos/' . $kindId;
        $thumbDir = __DIR__ . '/../../data/uploads/thumbs';

        FileService::ensureDir($dir);
        FileService::ensureDir($thumbDir);

        foreach ($files as $file) {
            if (!$file || $file->getError() !== UPLOAD_ERR_OK) continue;
            $ext = FileService::extension($file);
            if (!FileService::isAllowedImage($ext)) continue;

            $filename = uniqid() . '.' . $ext;
            $file->moveTo($dir . '/' . $filename);

            $thumbName = 'thumb_' . $filename;
            ImageService::createThumbnail($dir . '/' . $filename, $thumbDir . '/' . $thumbName);

            $db->prepare("INSERT INTO fotos (kind_id, tagebuch_id, dateiname, thumbnail, aufnahme_datum) VALUES (?,?,?,?,?)")
               ->execute([$kindId, $tagebuchId, 'fotos/' . $kindId . '/' . $filename, 'thumbs/' . $thumbName, $datum]);
        }
    }

}
