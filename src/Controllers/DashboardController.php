<?php

/**
 * Zweck: Startseite/Dashboard: Übersicht, Geburtstags-Reminder und Kennzahlen.
 */

namespace App\Controllers;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController
{
    public function __construct(private Twig $twig)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $db = Database::get();

        $kinder = $db->query("
            SELECT k.*,
                (SELECT COUNT(*) FROM tagebuch t WHERE t.kind_id = k.id) as tagebuch_count,
                (SELECT COUNT(*) FROM fotos f WHERE f.kind_id = k.id) as foto_count
            FROM kinder k
            WHERE k.aktiv = 1
            ORDER BY k.vorname
        ")->fetchAll();

        $heute = date('Y-m-d');

        // Heutige Anwesenheit je Kind (als assoziatives Array)
        $anwesenheitHeute = [];
        if ($kinder) {
            $ids = implode(',', array_column($kinder, 'id'));
            $rows = $db->query("SELECT * FROM anwesenheit WHERE datum = '{$heute}' AND kind_id IN ({$ids})")->fetchAll();
            foreach ($rows as $r) {
                $anwesenheitHeute[$r['kind_id']] = $r;
            }
        }

        // Geburtstags-Reminder
        $erinnerungTage = (int)($db->query("SELECT wert FROM einstellungen WHERE schluessel='geburtstag_erinnerung_tage'")->fetchColumn() ?: 14);
        $geburtstage = [];
        $jetzt = new \DateTime($heute);
        foreach ($kinder as $k) {
            $geb = new \DateTime($k['geburtsdatum']);
            $diesJahr = new \DateTime(date('Y') . '-' . $geb->format('m-d'));
            if ($diesJahr < $jetzt) $diesJahr->modify('+1 year');
            $diffTage = (int)$jetzt->diff($diesJahr)->days;
            if ($diffTage <= $erinnerungTage) {
                $geburtstage[] = [
                    'id'         => $k['id'],
                    'vorname'    => $k['vorname'],
                    'nachname'   => $k['nachname'],
                    'datum'      => $diesJahr->format('Y-m-d'),
                    'diff_tage'  => $diffTage,
                    'wird_jahre' => (int)$diesJahr->format('Y') - (int)(new \DateTime($k['geburtsdatum']))->format('Y'),
                ];
            }
        }
        usort($geburtstage, fn($a, $b) => $a['diff_tage'] <=> $b['diff_tage']);

        // Letzte Tagebucheinträge über alle Kinder
        $recentEntries = $db->query("
            SELECT t.id, t.datum, t.titel, t.text, t.stimmung, t.kind_id,
                   k.vorname, k.nachname, k.foto_pfad
            FROM tagebuch t
            JOIN kinder k ON k.id = t.kind_id
            WHERE k.aktiv = 1
            ORDER BY t.datum DESC, t.erstellt_am DESC
            LIMIT 4
        ")->fetchAll();

        // Offene Unfälle (Entwurf) als Dashboard-Hinweis
        $offeneUnfaelle = $db->query("
            SELECT u.id, u.datum, u.kind_id, k.vorname, k.nachname
            FROM unfaelle u
            JOIN kinder k ON k.id = u.kind_id
            WHERE u.status = 'entwurf'
            ORDER BY u.datum DESC
        ")->fetchAll();

        return $this->twig->render($response, 'dashboard.twig', [
            'kinder'           => $kinder,
            'anwesenheit_heute'=> $anwesenheitHeute,
            'geburtstage'      => $geburtstage,
            'recent_entries'   => $recentEntries,
            'offene_unfaelle'  => $offeneUnfaelle,
            'heute'            => $heute,
        ]);
    }
}
