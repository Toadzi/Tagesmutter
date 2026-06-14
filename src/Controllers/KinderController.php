<?php

/**
 * Zweck: Kinder-Stammdaten: Liste, Anlegen, Detailansicht (Tabs), Bearbeiten, Löschen und Profilfoto-Upload.
 */

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use App\Repositories\FotoRepository;
use App\Repositories\KinderRepository;
use App\Services\FileService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class KinderController
{
    public function __construct(private Twig $twig)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $db = Database::get();
        $filter = $request->getQueryParams()['filter'] ?? 'aktiv';

        $where = $filter === 'alle' ? '1=1' : 'k.aktiv = 1';
        if ($filter === 'inaktiv') {
            $where = 'k.aktiv = 0';
        }

        $kinder = $db->query("
            SELECT k.*,
                (SELECT COUNT(*) FROM tagebuch t WHERE t.kind_id = k.id) as tagebuch_count
            FROM kinder k
            WHERE {$where}
            ORDER BY k.vorname
        ")->fetchAll();

        return $this->twig->render($response, 'kinder/index.twig', [
            'kinder' => $kinder,
            'filter' => $filter,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->twig->render($response, 'kinder/form.twig', [
            'kind' => [],
            'is_new' => true,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $db = Database::get();

        $stmt = $db->prepare("
            INSERT INTO kinder (vorname, nachname, geburtsdatum, geschlecht, adresse, plz, ort,
                muttersprache, betreuung_start, betreuung_ende, betreuungszeiten,
                allergien, medikamente, besonderheiten, arzt_name, arzt_telefon, foto_freigabe)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['vorname'],
            $data['nachname'],
            $data['geburtsdatum'],
            $data['geschlecht'] ?: null,
            $data['adresse'] ?? '',
            $data['plz'] ?? '',
            $data['ort'] ?? '',
            $data['muttersprache'] ?? '',
            $data['betreuung_start'] ?: null,
            $data['betreuung_ende'] ?: null,
            $data['betreuungszeiten'] ?? '',
            $data['allergien'] ?? '',
            $data['medikamente'] ?? '',
            $data['besonderheiten'] ?? '',
            $data['arzt_name'] ?? '',
            $data['arzt_telefon'] ?? '',
            isset($data['foto_freigabe']) ? 1 : 0,
        ]);

        $id = $db->lastInsertId();

        FileService::ensureDir(__DIR__ . '/../../data/uploads/fotos/' . $id);

        $_SESSION['success'] = 'Kind angelegt.';
        return Redirect::to($response, '/kinder/' . $id);
    }

    public function show(Request $request, Response $response, string $id): Response
    {
        $db = Database::get();
        $id = (int)$id;

        $kind = KinderRepository::find($id);
        if (!$kind) {
            return $response->withStatus(404);
        }

        $kontakte = $db->prepare("SELECT * FROM kontakte WHERE kind_id = ? ORDER BY rolle");
        $kontakte->execute([$id]);

        $tagebuch = $db->prepare("SELECT * FROM tagebuch WHERE kind_id = ? ORDER BY datum DESC LIMIT 5");
        $tagebuch->execute([$id]);
        $tagebuchRows = $tagebuch->fetchAll();

        // Fotos je Tagebucheintrag
        $fotosByTagebuch = FotoRepository::getGroupedByTagebuchIds(
            array_column($tagebuchRows, 'id')
        );

        $fotos = FotoRepository::getByKindId($id, 8);

        $meilensteine = $db->prepare("SELECT * FROM meilensteine WHERE kind_id = ? ORDER BY datum DESC, id DESC");
        $meilensteine->execute([$id]);

        $berichte = $db->prepare("SELECT * FROM berichte WHERE kind_id = ? ORDER BY erstellt_am DESC");
        $berichte->execute([$id]);

        $dokumente = $db->prepare("SELECT * FROM dokumente WHERE kind_id = ? ORDER BY hochgeladen_am DESC");
        $dokumente->execute([$id]);

        return $this->twig->render($response, 'kinder/show.twig', [
            'kind' => $kind,
            'kontakte' => $kontakte->fetchAll(),
            'tagebuch' => $tagebuchRows,
            'fotos_by_tagebuch' => $fotosByTagebuch,
            'fotos' => $fotos,
            'meilensteine' => $meilensteine->fetchAll(),
            'berichte' => $berichte->fetchAll(),
            'dokumente' => $dokumente->fetchAll(),
            'tab' => $request->getQueryParams()['tab'] ?? 'stammdaten',
        ]);
    }

    public function edit(Request $request, Response $response, string $id): Response
    {
        $kind = KinderRepository::find((int)$id);
        if (!$kind) {
            return $response->withStatus(404);
        }

        return $this->twig->render($response, 'kinder/form.twig', [
            'kind' => $kind,
            'is_new' => false,
        ]);
    }

    public function update(Request $request, Response $response, string $id): Response
    {
        $data = $request->getParsedBody();
        $db = Database::get();
        $id = (int)$id;

        $stmt = $db->prepare("
            UPDATE kinder SET
                vorname = ?, nachname = ?, geburtsdatum = ?, geschlecht = ?,
                adresse = ?, plz = ?, ort = ?, muttersprache = ?,
                betreuung_start = ?, betreuung_ende = ?, betreuungszeiten = ?,
                allergien = ?, medikamente = ?, besonderheiten = ?,
                arzt_name = ?, arzt_telefon = ?, foto_freigabe = ?, aktiv = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['vorname'],
            $data['nachname'],
            $data['geburtsdatum'],
            $data['geschlecht'] ?: null,
            $data['adresse'] ?? '',
            $data['plz'] ?? '',
            $data['ort'] ?? '',
            $data['muttersprache'] ?? '',
            $data['betreuung_start'] ?: null,
            $data['betreuung_ende'] ?: null,
            $data['betreuungszeiten'] ?? '',
            $data['allergien'] ?? '',
            $data['medikamente'] ?? '',
            $data['besonderheiten'] ?? '',
            $data['arzt_name'] ?? '',
            $data['arzt_telefon'] ?? '',
            isset($data['foto_freigabe']) ? 1 : 0,
            isset($data['aktiv']) ? 1 : 0,
            $id,
        ]);

        $_SESSION['success'] = 'Stammdaten gespeichert.';
        return Redirect::to($response, '/kinder/' . $id);
    }

    public function delete(Request $request, Response $response, string $id): Response
    {
        $db = Database::get();
        $id = (int)$id;

        $fotoDir = __DIR__ . '/../../data/uploads/fotos/' . $id;
        if (is_dir($fotoDir)) {
            $files = glob($fotoDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($fotoDir);
        }

        $stmt = $db->prepare("DELETE FROM kinder WHERE id = ?");
        $stmt->execute([$id]);

        return Redirect::to($response, '/kinder');
    }

    public function uploadFoto(Request $request, Response $response, string $id): Response
    {
        $id = (int)$id;
        $uploadedFiles = $request->getUploadedFiles();
        $foto = $uploadedFiles['foto'] ?? null;

        if ($foto && $foto->getError() === UPLOAD_ERR_OK) {
            $ext = FileService::extension($foto);
            if (FileService::isAllowedImage($ext)) {
                $filename = 'profil.' . $ext;
                $dir = __DIR__ . '/../../data/uploads/fotos/' . $id;
                FileService::ensureDir($dir);

                foreach (glob($dir . '/profil.*') as $old) {
                    unlink($old);
                }

                $foto->moveTo($dir . '/' . $filename);

                $db = Database::get();
                $stmt = $db->prepare("UPDATE kinder SET foto_pfad = ? WHERE id = ?");
                $stmt->execute(['fotos/' . $id . '/' . $filename, $id]);
            }
        }

        return Redirect::to($response, '/kinder/' . $id);
    }
}
