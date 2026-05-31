<?php

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use App\Repositories\KinderRepository;
use App\Services\PdfService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AnwesenheitController
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

        $monat = (int)($params['monat'] ?? date('m'));
        $jahr = (int)($params['jahr'] ?? date('Y'));

        $startDatum = sprintf('%04d-%02d-01', $jahr, $monat);
        $endDatum = date('Y-m-t', strtotime($startDatum));
        $tageImMonat = (int)date('t', strtotime($startDatum));

        $stmt = $db->prepare("SELECT * FROM anwesenheit WHERE kind_id = ? AND datum BETWEEN ? AND ? ORDER BY datum");
        $stmt->execute([$kindId, $startDatum, $endDatum]);
        $eintraege = $stmt->fetchAll();

        $anwesenheitByDate = [];
        foreach ($eintraege as $e) {
            $anwesenheitByDate[$e['datum']] = $e;
        }

        $gesamtStunden = 0;
        $anwesendTage = 0;
        $abwesentTage = 0;
        foreach ($eintraege as $e) {
            if ($e['abwesend']) {
                $abwesentTage++;
            } elseif ($e['ankunft'] && $e['abholung']) {
                $anwesendTage++;
                $start = strtotime($e['ankunft']);
                $end = strtotime($e['abholung']);
                if ($end > $start) {
                    $gesamtStunden += ($end - $start) / 3600;
                }
            }
        }

        return $this->twig->render($response, 'anwesenheit/index.twig', [
            'kind' => $kind,
            'monat' => $monat,
            'jahr' => $jahr,
            'tage_im_monat' => $tageImMonat,
            'anwesenheit' => $anwesenheitByDate,
            'gesamt_stunden' => round($gesamtStunden, 1),
            'anwesend_tage' => $anwesendTage,
            'abwesent_tage' => $abwesentTage,
        ]);
    }

    public function save(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $tage = $data['tage'] ?? [];

        foreach ($tage as $datum => $tag) {
            $ankunft = $tag['ankunft'] ?? '';
            $abholung = $tag['abholung'] ?? '';
            $abwesend = isset($tag['abwesend']) ? 1 : 0;
            $grund = $tag['grund'] ?? '';
            $notiz = $tag['notiz'] ?? '';

            if (!$ankunft && !$abholung && !$abwesend) {
                $stmt = $db->prepare("DELETE FROM anwesenheit WHERE kind_id = ? AND datum = ?");
                $stmt->execute([$kindId, $datum]);
                continue;
            }

            $stmt = $db->prepare("
                INSERT INTO anwesenheit (kind_id, datum, ankunft, abholung, abwesend, abwesenheitsgrund, notiz)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(kind_id, datum) DO UPDATE SET
                    ankunft = excluded.ankunft,
                    abholung = excluded.abholung,
                    abwesend = excluded.abwesend,
                    abwesenheitsgrund = excluded.abwesenheitsgrund,
                    notiz = excluded.notiz
            ");

            $stmt->execute([
                $kindId,
                $datum,
                $ankunft ?: null,
                $abholung ?: null,
                $abwesend,
                $grund,
                $notiz,
            ]);
        }

        $monat = $data['monat'] ?? date('m');
        $jahr  = $data['jahr']  ?? date('Y');

        $_SESSION['success'] = 'Anwesenheit gespeichert.';
        return Redirect::to($response, '/kinder/' . $kindId . '/anwesenheit?monat=' . $monat . '&jahr=' . $jahr);
    }

    public function quickAction(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $db     = Database::get();
        $heute  = date('Y-m-d');
        $jetzt  = date('H:i');
        $action = $request->getParsedBody()['action'] ?? 'einchecken';

        switch ($action) {
            case 'einchecken':
                $db->prepare("
                    INSERT INTO anwesenheit (kind_id, datum, ankunft, abwesend)
                    VALUES (?,?,?,0)
                    ON CONFLICT(kind_id,datum) DO UPDATE SET ankunft=excluded.ankunft, abwesend=0, abholung=NULL
                ")->execute([$kindId, $heute, $jetzt]);
                $_SESSION['success'] = 'Ankunft um ' . $jetzt . ' Uhr gespeichert.';
                break;

            case 'auschecken':
                $db->prepare("
                    UPDATE anwesenheit SET abholung=? WHERE kind_id=? AND datum=?
                ")->execute([$jetzt, $kindId, $heute]);
                $_SESSION['success'] = 'Abholung um ' . $jetzt . ' Uhr gespeichert.';
                break;

            case 'abwesend':
                $db->prepare("
                    INSERT INTO anwesenheit (kind_id, datum, abwesend)
                    VALUES (?,?,1)
                    ON CONFLICT(kind_id,datum) DO UPDATE SET abwesend=1, ankunft=NULL, abholung=NULL
                ")->execute([$kindId, $heute]);
                $_SESSION['success'] = 'Als abwesend eingetragen.';
                break;

            case 'zuruecksetzen':
                $db->prepare("DELETE FROM anwesenheit WHERE kind_id=? AND datum=?")->execute([$kindId, $heute]);
                $_SESSION['success'] = 'Eintrag zurückgesetzt.';
                break;
        }

        // Nur interne Weiterleitungen erlauben (kein Open Redirect)
        $redirect = $request->getParsedBody()['redirect'] ?? APP_BASE_PATH . '/';
        if (!str_starts_with($redirect, '/')) {
            $redirect = APP_BASE_PATH . '/';
        }
        return Redirect::toUrl($response, $redirect);
    }

    public function exportPdf(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $params = $request->getQueryParams();
        $db = Database::get();

        $kind = KinderRepository::find($kindId);

        $monat = (int)($params['monat'] ?? date('m'));
        $jahr = (int)($params['jahr'] ?? date('Y'));

        $startDatum = sprintf('%04d-%02d-01', $jahr, $monat);
        $endDatum = date('Y-m-t', strtotime($startDatum));

        $stmt = $db->prepare("SELECT * FROM anwesenheit WHERE kind_id = ? AND datum BETWEEN ? AND ? ORDER BY datum");
        $stmt->execute([$kindId, $startDatum, $endDatum]);

        $monatsname = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'][$monat];

        $html = PdfService::renderTemplate('anwesenheit_pdf', [
            'kind' => $kind,
            'eintraege' => $stmt->fetchAll(),
            'monat' => $monat,
            'jahr' => $jahr,
            'monatsname' => $monatsname,
        ]);

        return PdfService::generateResponse($response, $html, 'Anwesenheit_' . $kind['vorname'] . '_' . $monatsname . '_' . $jahr . '.pdf');
    }

    public function exportCsv(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $params = $request->getQueryParams();
        $db = Database::get();

        $kind = KinderRepository::find($kindId);

        $monat = (int)($params['monat'] ?? date('m'));
        $jahr = (int)($params['jahr'] ?? date('Y'));

        $startDatum = sprintf('%04d-%02d-01', $jahr, $monat);
        $endDatum = date('Y-m-t', strtotime($startDatum));

        $stmt = $db->prepare("SELECT * FROM anwesenheit WHERE kind_id = ? AND datum BETWEEN ? AND ? ORDER BY datum");
        $stmt->execute([$kindId, $startDatum, $endDatum]);

        $csv = "Datum;Ankunft;Abholung;Abwesend;Grund;Notiz\n";
        foreach ($stmt->fetchAll() as $row) {
            $csv .= implode(';', [
                $row['datum'],
                $row['ankunft'] ?? '',
                $row['abholung'] ?? '',
                $row['abwesend'] ? 'Ja' : 'Nein',
                $row['abwesenheitsgrund'] ?? '',
                $row['notiz'] ?? '',
            ]) . "\n";
        }

        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="Anwesenheit_' . $kind['vorname'] . '_' . $monat . '_' . $jahr . '.csv"');
    }
}
