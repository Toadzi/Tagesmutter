<?php

/**
 * Zweck: Unfallprotokoll je Kind und global: Liste, Anlegen, Anzeigen, Bearbeiten, Löschen und PDF-Export.
 */

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use App\Repositories\KinderRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Dompdf\Dompdf;
use Dompdf\Options;

class UnfallController
{
    public function __construct(private Twig $twig)
    {
    }

    // Globale Liste aller Unfälle (alle Kinder)
    public function globalIndex(Request $request, Response $response): Response
    {
        $db     = Database::get();
        $params = $request->getQueryParams();

        $where    = ['1=1'];
        $bindings = [];

        if (!empty($params['kind_id'])) {
            $where[]    = 'u.kind_id = ?';
            $bindings[] = (int)$params['kind_id'];
        }
        if (!empty($params['status'])) {
            $where[]    = 'u.status = ?';
            $bindings[] = $params['status'];
        }
        if (!empty($params['von'])) {
            $where[]    = 'u.datum >= ?';
            $bindings[] = $params['von'];
        }
        if (!empty($params['bis'])) {
            $where[]    = 'u.datum <= ?';
            $bindings[] = $params['bis'];
        }

        $whereStr = implode(' AND ', $where);
        $stmt = $db->prepare("
            SELECT u.*, k.vorname, k.nachname, k.foto_pfad
            FROM unfaelle u
            JOIN kinder k ON k.id = u.kind_id
            WHERE {$whereStr}
            ORDER BY u.datum DESC, u.erstellt_am DESC
        ");
        $stmt->execute($bindings);
        $unfaelle = $stmt->fetchAll();

        $kinder = $db->query("SELECT id, vorname, nachname FROM kinder WHERE aktiv = 1 ORDER BY vorname")->fetchAll();

        return $this->twig->render($response, 'unfaelle/global.twig', [
            'unfaelle' => $unfaelle,
            'kinder'   => $kinder,
            'filter'   => $params,
        ]);
    }

    // Liste je Kind
    public function index(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $db     = Database::get();

        $kind = KinderRepository::find($kindId);
        if (!$kind) return $response->withStatus(404);

        $stmt = $db->prepare("SELECT * FROM unfaelle WHERE kind_id = ? ORDER BY datum DESC, erstellt_am DESC");
        $stmt->execute([$kindId]);

        return $this->twig->render($response, 'unfaelle/index.twig', [
            'kind'     => $kind,
            'unfaelle' => $stmt->fetchAll(),
        ]);
    }

    // Einzelansicht
    public function show(Request $request, Response $response, string $kind_id, string $id): Response
    {
        [$kind, $unfall] = $this->loadOrFail($kind_id, $id, $response);
        if (!$unfall) return $response->withStatus(404);

        return $this->twig->render($response, 'unfaelle/show.twig', [
            'kind'   => $kind,
            'unfall' => $unfall,
            'massnahmen' => json_decode($unfall['massnahmen'] ?? '[]', true),
        ]);
    }

    // Formular anlegen
    public function create(Request $request, Response $response, string $kind_id): Response
    {
        $kind = KinderRepository::find((int)$kind_id);
        if (!$kind) return $response->withStatus(404);

        return $this->twig->render($response, 'unfaelle/form.twig', [
            'kind'   => $kind,
            'unfall' => [],
            'is_new' => true,
            'massnahmen' => [],
        ]);
    }

    // Speichern
    public function store(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $data   = $request->getParsedBody();
        $db     = Database::get();

        $massnahmen = json_encode($data['massnahmen'] ?? []);

        $stmt = $db->prepare("
            INSERT INTO unfaelle (
                kind_id, datum, uhrzeit, ort, gemeldet_am,
                einzelheiten, verletzungen, massnahmen, massnahmen_sonstige, praevention,
                zeuge1_name, zeuge1_kontakt, zeuge2_name, zeuge2_kontakt, zeuge3_name, zeuge3_kontakt,
                ausgefuellt_von, ausgefuellt_datum, genehmigt_von, genehmigt_datum,
                unterschrift_stempel, unfallkasse_gemeldet, status
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $kindId,
            $data['datum'] ?: date('Y-m-d'),
            $data['uhrzeit'] ?? '',
            $data['ort'] ?? '',
            $data['gemeldet_am'] ?? '',
            $data['einzelheiten'] ?? '',
            $data['verletzungen'] ?? '',
            $massnahmen,
            $data['massnahmen_sonstige'] ?? '',
            $data['praevention'] ?? '',
            $data['zeuge1_name'] ?? '',
            $data['zeuge1_kontakt'] ?? '',
            $data['zeuge2_name'] ?? '',
            $data['zeuge2_kontakt'] ?? '',
            $data['zeuge3_name'] ?? '',
            $data['zeuge3_kontakt'] ?? '',
            $data['ausgefuellt_von'] ?? '',
            $data['ausgefuellt_datum'] ?? '',
            $data['genehmigt_von'] ?? '',
            $data['genehmigt_datum'] ?? '',
            $data['unterschrift_stempel'] ?? '',
            isset($data['unfallkasse_gemeldet']) ? 1 : 0,
            $data['status'] ?? 'entwurf',
        ]);

        $_SESSION['success'] = 'Unfall gespeichert.';
        return Redirect::to($response, '/kinder/' . $kindId . '/unfaelle/' . $db->lastInsertId());
    }

    // Bearbeitungsformular
    public function edit(Request $request, Response $response, string $kind_id, string $id): Response
    {
        [$kind, $unfall] = $this->loadOrFail($kind_id, $id, $response);
        if (!$unfall) return $response->withStatus(404);

        return $this->twig->render($response, 'unfaelle/form.twig', [
            'kind'       => $kind,
            'unfall'     => $unfall,
            'is_new'     => false,
            'massnahmen' => json_decode($unfall['massnahmen'] ?? '[]', true),
        ]);
    }

    // Aktualisieren
    public function update(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id     = (int)$id;
        $data   = $request->getParsedBody();
        $db     = Database::get();

        $massnahmen = json_encode($data['massnahmen'] ?? []);

        $stmt = $db->prepare("
            UPDATE unfaelle SET
                datum=?, uhrzeit=?, ort=?, gemeldet_am=?,
                einzelheiten=?, verletzungen=?, massnahmen=?, massnahmen_sonstige=?, praevention=?,
                zeuge1_name=?, zeuge1_kontakt=?, zeuge2_name=?, zeuge2_kontakt=?,
                zeuge3_name=?, zeuge3_kontakt=?,
                ausgefuellt_von=?, ausgefuellt_datum=?, genehmigt_von=?, genehmigt_datum=?,
                unterschrift_stempel=?, unfallkasse_gemeldet=?, status=?
            WHERE id=? AND kind_id=?
        ");
        $stmt->execute([
            $data['datum'] ?: date('Y-m-d'),
            $data['uhrzeit'] ?? '',
            $data['ort'] ?? '',
            $data['gemeldet_am'] ?? '',
            $data['einzelheiten'] ?? '',
            $data['verletzungen'] ?? '',
            $massnahmen,
            $data['massnahmen_sonstige'] ?? '',
            $data['praevention'] ?? '',
            $data['zeuge1_name'] ?? '',
            $data['zeuge1_kontakt'] ?? '',
            $data['zeuge2_name'] ?? '',
            $data['zeuge2_kontakt'] ?? '',
            $data['zeuge3_name'] ?? '',
            $data['zeuge3_kontakt'] ?? '',
            $data['ausgefuellt_von'] ?? '',
            $data['ausgefuellt_datum'] ?? '',
            $data['genehmigt_von'] ?? '',
            $data['genehmigt_datum'] ?? '',
            $data['unterschrift_stempel'] ?? '',
            isset($data['unfallkasse_gemeldet']) ? 1 : 0,
            $data['status'] ?? 'entwurf',
            $id,
            $kindId,
        ]);

        $_SESSION['success'] = 'Unfall aktualisiert.';
        return Redirect::to($response, '/kinder/' . $kindId . '/unfaelle/' . $id);
    }

    // Löschen
    public function delete(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $db     = Database::get();
        $db->prepare("DELETE FROM unfaelle WHERE id = ? AND kind_id = ?")->execute([(int)$id, $kindId]);

        $_SESSION['success'] = 'Unfalleintrag gelöscht.';
        return Redirect::to($response, '/kinder/' . $kindId . '/unfaelle');
    }

    // PDF-Export
    public function exportPdf(Request $request, Response $response, string $kind_id, string $id): Response
    {
        [$kind, $unfall] = $this->loadOrFail($kind_id, $id, $response);
        if (!$unfall) return $response->withStatus(404);

        $massnahmen = json_decode($unfall['massnahmen'] ?? '[]', true);

        $html = $this->twig->getEnvironment()->render('unfaelle/pdf.twig', [
            'kind'       => $kind,
            'unfall'     => $unfall,
            'massnahmen' => $massnahmen,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'unfall_' . $kind['vorname'] . '_' . $unfall['datum'] . '.pdf';

        $response->getBody()->write($dompdf->output());
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    // Hilfsmethode
    private function loadOrFail(string $kind_id, string $id, Response $response): array
    {
        $kind = KinderRepository::find((int)$kind_id);

        $unfall = Database::get()->prepare("SELECT * FROM unfaelle WHERE id = ? AND kind_id = ?");
        $unfall->execute([(int)$id, (int)$kind_id]);
        $unfall = $unfall->fetch();

        return [$kind, $unfall];
    }
}
