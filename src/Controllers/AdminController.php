<?php

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AdminController
{
    public function __construct(private Twig $twig) {}

    public function index(Request $request, Response $response): Response
    {
        $db = Database::get();

        $stats = [
            'benutzer'      => $db->query("SELECT COUNT(*) FROM benutzer WHERE aktiv = 1")->fetchColumn(),
            'textbausteine' => $db->query("SELECT COUNT(*) FROM textbausteine WHERE aktiv = 1")->fetchColumn(),
            'letzter_login' => $db->query("SELECT MAX(letzter_login) FROM benutzer")->fetchColumn(),
        ];

        return $this->twig->render($response, 'admin/index.twig', ['stats' => $stats]);
    }

    public function einstellungen(Request $request, Response $response): Response
    {
        $db = Database::get();
        $rows = $db->query("SELECT * FROM einstellungen ORDER BY schluessel")->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['schluessel']] = $r;
        }

        return $this->twig->render($response, 'admin/einstellungen.twig', ['settings' => $settings]);
    }

    public function einstellungenSave(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $db = Database::get();

        $stmt = $db->prepare("
            INSERT INTO einstellungen (schluessel, wert, aktualisiert_am)
            VALUES (?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT(schluessel) DO UPDATE SET wert = excluded.wert, aktualisiert_am = CURRENT_TIMESTAMP
        ");

        foreach ($data['settings'] ?? [] as $key => $value) {
            $stmt->execute([$key, trim($value)]);
        }

        // Checkboxen die nicht übermittelt werden = 0
        foreach (['backup_aktiv'] as $checkbox) {
            if (!isset($data['settings'][$checkbox])) {
                $stmt->execute([$checkbox, '0']);
            }
        }

        $_SESSION['success'] = 'Einstellungen gespeichert.';
        return Redirect::to($response, '/admin/einstellungen');
    }
}
