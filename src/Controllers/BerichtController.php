<?php

/**
 * Zweck: Entwicklungsberichte: Liste, Erstellen (mit Textbausteinen/Tagebuch/Meilensteinen), Bearbeiten und PDF-Export.
 */

namespace App\Controllers;

use App\Config\Bildungsbereiche;
use App\Database;
use App\Http\Redirect;
use App\Repositories\KinderRepository;
use App\Services\PdfService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class BerichtController
{
    public function __construct(private Twig $twig)
    {
    }

    public function index(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $db = Database::get();

        $kind = KinderRepository::find($kindId);

        $berichte = $db->prepare("SELECT * FROM berichte WHERE kind_id = ? ORDER BY erstellt_am DESC");
        $berichte->execute([$kindId]);

        return $this->twig->render($response, 'berichte/index.twig', [
            'kind' => $kind,
            'berichte' => $berichte->fetchAll(),
        ]);
    }

    public function create(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $db = Database::get();
        $params = $request->getQueryParams();

        $kind = KinderRepository::find($kindId);

        $monate = (int)($db->query("SELECT wert FROM einstellungen WHERE schluessel='bericht_zeitraum_monate'")->fetchColumn() ?: 6);
        $von = $params['von'] ?? date('Y-m-d', strtotime("-{$monate} months"));
        $bis = $params['bis'] ?? date('Y-m-d');

        $eintraege = $db->prepare("SELECT * FROM tagebuch WHERE kind_id = ? AND datum BETWEEN ? AND ? ORDER BY datum");
        $eintraege->execute([$kindId, $von, $bis]);
        $allEntries = $eintraege->fetchAll();

        $grouped = [];
        foreach ($allEntries as $e) {
            $kats = json_decode($e['kategorien'] ?? '[]', true) ?: ['sonstiges'];
            foreach ($kats as $kat) {
                $grouped[$kat][] = $e;
            }
        }

        $meilensteine = $db->prepare("SELECT * FROM meilensteine WHERE kind_id = ? AND datum BETWEEN ? AND ? ORDER BY datum");
        $meilensteine->execute([$kindId, $von, $bis]);

        // Letzten Bericht als mögliche Vorlage laden
        $letzter = $db->prepare("SELECT * FROM berichte WHERE kind_id = ? ORDER BY erstellt_am DESC LIMIT 1");
        $letzter->execute([$kindId]);
        $letzterBericht = $letzter->fetch();
        if ($letzterBericht) {
            $letzterBericht['inhalt_parsed'] = json_decode($letzterBericht['inhalt'] ?? '{}', true) ?: [];
        }

        return $this->twig->render($response, 'berichte/form.twig', [
            'kind' => $kind,
            'bericht' => [],
            'is_new' => true,
            'von' => $von,
            'bis' => $bis,
            'grouped_entries' => $grouped,
            'meilensteine' => $meilensteine->fetchAll(),
            'bildungsbereiche' => Bildungsbereiche::KEYS,
            'letzter_bericht' => $letzterBericht ?: null,
            'textbausteine' => $this->loadTextbausteine($db),
        ]);
    }

    public function store(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $bereiche = $data['bereiche'] ?? [];
        $bereiche['_gesamteinschaetzung'] = $data['gesamteinschaetzung'] ?? '';
        $inhalt = json_encode($bereiche, JSON_UNESCAPED_UNICODE);

        $stmt = $db->prepare("
            INSERT INTO berichte (kind_id, titel, zeitraum_von, zeitraum_bis, inhalt, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $kindId,
            $data['titel'] ?? 'Entwicklungsbericht',
            $data['zeitraum_von'],
            $data['zeitraum_bis'],
            $inhalt,
            $data['status'] ?? 'entwurf',
        ]);

        $id = $db->lastInsertId();

        return Redirect::to($response, '/kinder/' . $kindId . '/berichte/' . $id);
    }

    public function show(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $kind = KinderRepository::find($kindId);

        $bericht = $db->prepare("SELECT * FROM berichte WHERE id = ? AND kind_id = ?");
        $bericht->execute([$id, $kindId]);
        $bericht = $bericht->fetch();

        if (!$bericht) {
            return $response->withStatus(404);
        }

        $bericht['inhalt_parsed'] = json_decode($bericht['inhalt'] ?? '{}', true) ?: [];

        return $this->twig->render($response, 'berichte/show.twig', [
            'kind' => $kind,
            'bericht' => $bericht,
            'bildungsbereiche' => Bildungsbereiche::KEYS,
        ]);
    }

    public function edit(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $kind = KinderRepository::find($kindId);

        $bericht = $db->prepare("SELECT * FROM berichte WHERE id = ? AND kind_id = ?");
        $bericht->execute([$id, $kindId]);
        $bericht = $bericht->fetch();

        if (!$bericht) {
            return $response->withStatus(404);
        }

        $bericht['inhalt_parsed'] = json_decode($bericht['inhalt'] ?? '{}', true) ?: [];

        $von = $bericht['zeitraum_von'];
        $bis = $bericht['zeitraum_bis'];

        $eintraege = $db->prepare("SELECT * FROM tagebuch WHERE kind_id = ? AND datum BETWEEN ? AND ? ORDER BY datum");
        $eintraege->execute([$kindId, $von, $bis]);

        $grouped = [];
        foreach ($eintraege->fetchAll() as $e) {
            $kats = json_decode($e['kategorien'] ?? '[]', true) ?: ['sonstiges'];
            foreach ($kats as $kat) {
                $grouped[$kat][] = $e;
            }
        }

        $meilensteine = $db->prepare("SELECT * FROM meilensteine WHERE kind_id = ? AND datum BETWEEN ? AND ? ORDER BY datum");
        $meilensteine->execute([$kindId, $von, $bis]);

        return $this->twig->render($response, 'berichte/form.twig', [
            'kind' => $kind,
            'bericht' => $bericht,
            'is_new' => false,
            'von' => $von,
            'bis' => $bis,
            'grouped_entries' => $grouped,
            'meilensteine' => $meilensteine->fetchAll(),
            'bildungsbereiche' => Bildungsbereiche::KEYS,
            'letzter_bericht' => null,
            'textbausteine' => $this->loadTextbausteine($db),
        ]);
    }

    public function update(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $bereiche = $data['bereiche'] ?? [];
        $bereiche['_gesamteinschaetzung'] = $data['gesamteinschaetzung'] ?? '';
        $inhalt = json_encode($bereiche, JSON_UNESCAPED_UNICODE);

        $stmt = $db->prepare("
            UPDATE berichte SET titel = ?, zeitraum_von = ?, zeitraum_bis = ?, inhalt = ?, status = ?
            WHERE id = ? AND kind_id = ?
        ");

        $stmt->execute([
            $data['titel'] ?? 'Entwicklungsbericht',
            $data['zeitraum_von'],
            $data['zeitraum_bis'],
            $inhalt,
            $data['status'] ?? 'entwurf',
            $id,
            $kindId,
        ]);

        return Redirect::to($response, '/kinder/' . $kindId . '/berichte/' . $id);
    }

    public function delete(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $stmt = $db->prepare("DELETE FROM berichte WHERE id = ? AND kind_id = ?");
        $stmt->execute([$id, $kindId]);

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=berichte');
    }

    private function loadTextbausteine(\PDO $db): array
    {
        $rows = $db->query("SELECT bildungsbereich, text FROM textbausteine WHERE aktiv = 1 ORDER BY bildungsbereich, sort_order, id")->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['bildungsbereich']][] = $r['text'];
        }
        return $grouped;
    }

    public function exportPdf(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $kind = KinderRepository::find($kindId);

        $bericht = $db->prepare("SELECT * FROM berichte WHERE id = ? AND kind_id = ?");
        $bericht->execute([$id, $kindId]);
        $bericht = $bericht->fetch();

        $bericht['inhalt_parsed'] = json_decode($bericht['inhalt'] ?? '{}', true) ?: [];

        $html = PdfService::renderTemplate('bericht_pdf', [
            'kind' => $kind,
            'bericht' => $bericht,
            'bildungsbereiche' => Bildungsbereiche::LABELS,
        ]);

        return PdfService::generateResponse($response, $html, 'Bericht_' . $kind['vorname'] . '_' . $kind['nachname'] . '.pdf');
    }
}
