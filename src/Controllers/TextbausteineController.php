<?php

namespace App\Controllers;

use App\Config\Bildungsbereiche;
use App\Database;
use App\Http\Redirect;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class TextbausteineController
{
    public function __construct(private Twig $twig) {}

    public function index(Request $request, Response $response): Response
    {
        $db = Database::get();
        $rows = $db->query("SELECT * FROM textbausteine ORDER BY bildungsbereich, sort_order, id")->fetchAll();

        $grouped = [];
        foreach (Bildungsbereiche::KEYS as $b) {
            $grouped[$b] = [];
        }
        foreach ($rows as $r) {
            $grouped[$r['bildungsbereich']][] = $r;
        }

        return $this->twig->render($response, 'admin/textbausteine.twig', [
            'grouped' => $grouped,
            'bereiche' => Bildungsbereiche::KEYS,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $db = Database::get();

        // Max sort_order im Bereich bestimmen
        $maxSort = $db->prepare("SELECT COALESCE(MAX(sort_order),0) FROM textbausteine WHERE bildungsbereich = ?");
        $maxSort->execute([$data['bildungsbereich']]);

        $db->prepare("INSERT INTO textbausteine (bildungsbereich, text, sort_order, aktiv) VALUES (?,?,?,1)")
           ->execute([$data['bildungsbereich'], trim($data['text']), (int)$maxSort->fetchColumn() + 1]);

        $_SESSION['success'] = 'Baustein hinzugefügt.';
        return Redirect::to($response, '/admin/textbausteine#' . $data['bildungsbereich']);
    }

    public function update(Request $request, Response $response, string $id): Response
    {
        $data = $request->getParsedBody();
        $db = Database::get();

        $db->prepare("UPDATE textbausteine SET text = ?, aktiv = ? WHERE id = ?")
           ->execute([trim($data['text']), isset($data['aktiv']) ? 1 : 0, (int)$id]);

        $_SESSION['success'] = 'Baustein gespeichert.';
        $bereich = $data['bildungsbereich'] ?? '';
        return Redirect::to($response, '/admin/textbausteine#' . $bereich);
    }

    public function delete(Request $request, Response $response, string $id): Response
    {
        $db = Database::get();
        $row = $db->prepare("SELECT bildungsbereich FROM textbausteine WHERE id = ?");
        $row->execute([(int)$id]);
        $bereich = $row->fetchColumn();

        $db->prepare("DELETE FROM textbausteine WHERE id = ?")->execute([(int)$id]);

        $_SESSION['success'] = 'Baustein gelöscht.';
        return Redirect::to($response, '/admin/textbausteine#' . $bereich);
    }

    public function sort(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $ids = $data['ids'] ?? [];
        $db = Database::get();

        $stmt = $db->prepare("UPDATE textbausteine SET sort_order = ? WHERE id = ?");
        foreach ($ids as $order => $id) {
            $stmt->execute([(int)$order + 1, (int)$id]);
        }

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function export(Request $request, Response $response): Response
    {
        $db = Database::get();
        $rows = $db->query("SELECT bildungsbereich, text, sort_order, aktiv FROM textbausteine ORDER BY bildungsbereich, sort_order")->fetchAll();

        $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Content-Disposition', 'attachment; filename="textbausteine.json"');
    }

    public function import(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['import_file'] ?? null;

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            $_SESSION['success'] = 'Fehler beim Upload.';
            return Redirect::to($response, '/admin/textbausteine');
        }

        $json = (string)$file->getStream();
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $_SESSION['success'] = 'Ungültiges JSON-Format.';
            return Redirect::to($response, '/admin/textbausteine');
        }

        $db = Database::get();
        $db->exec("DELETE FROM textbausteine");
        $stmt = $db->prepare("INSERT INTO textbausteine (bildungsbereich, text, sort_order, aktiv) VALUES (?,?,?,?)");
        foreach ($data as $row) {
            if (!empty($row['bildungsbereich']) && !empty($row['text'])) {
                $stmt->execute([$row['bildungsbereich'], $row['text'], (int)($row['sort_order'] ?? 0), (int)($row['aktiv'] ?? 1)]);
            }
        }

        $_SESSION['success'] = count($data) . ' Bausteine importiert.';
        return Redirect::to($response, '/admin/textbausteine');
    }
}
