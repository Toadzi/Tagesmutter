<?php

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class BenutzerController
{
    public function __construct(private Twig $twig)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $db = Database::get();
        $benutzer = $db->query("
            SELECT b.*, k.vorname || ' ' || k.nachname as kind_name
            FROM benutzer b
            LEFT JOIN kinder k ON k.id = b.kind_id
            ORDER BY b.rolle, b.name
        ")->fetchAll();

        $kinder = $db->query("SELECT id, vorname, nachname FROM kinder WHERE aktiv = 1 ORDER BY vorname")->fetchAll();

        return $this->twig->render($response, 'admin/benutzer.twig', [
            'benutzer' => $benutzer,
            'kinder' => $kinder,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $db = Database::get();
        $kinder = $db->query("SELECT id, vorname, nachname FROM kinder WHERE aktiv = 1 ORDER BY vorname")->fetchAll();

        return $this->twig->render($response, 'admin/benutzer_form.twig', [
            'benutzer' => [],
            'is_new' => true,
            'kinder' => $kinder,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $db = Database::get();

        if (empty($data['passwort']) || strlen($data['passwort']) < 6) {
            return $this->twig->render($response, 'admin/benutzer_form.twig', [
                'benutzer' => $data,
                'is_new' => true,
                'kinder' => $db->query("SELECT id, vorname, nachname FROM kinder WHERE aktiv = 1")->fetchAll(),
                'fehler' => 'Passwort muss mindestens 6 Zeichen haben.',
            ]);
        }

        $hash = password_hash($data['passwort'], PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $db->prepare("
            INSERT INTO benutzer (name, benutzername, passwort_hash, rolle, kind_id, aktiv)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $data['name'],
            $data['benutzername'],
            $hash,
            $data['rolle'],
            !empty($data['kind_id']) ? (int)$data['kind_id'] : null,
        ]);

        $_SESSION['success'] = 'Benutzer «' . $data['name'] . '» wurde angelegt.';
        return Redirect::to($response, '/admin/benutzer');
    }

    public function edit(Request $request, Response $response, string $id): Response
    {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM benutzer WHERE id = ?");
        $stmt->execute([(int)$id]);
        $benutzer = $stmt->fetch();

        if (!$benutzer) {
            return $response->withStatus(404);
        }

        $kinder = $db->query("SELECT id, vorname, nachname FROM kinder WHERE aktiv = 1 ORDER BY vorname")->fetchAll();

        return $this->twig->render($response, 'admin/benutzer_form.twig', [
            'benutzer' => $benutzer,
            'is_new' => false,
            'kinder' => $kinder,
        ]);
    }

    public function update(Request $request, Response $response, string $id): Response
    {
        $id = (int)$id;
        $data = $request->getParsedBody();
        $db = Database::get();

        // Eigenen Admin-Account kann man nicht deaktivieren
        if ($id === (int)($_SESSION['benutzer_id'] ?? 0)) {
            $data['aktiv'] = '1';
        }

        $params = [
            $data['name'],
            $data['benutzername'],
            $data['rolle'],
            !empty($data['kind_id']) ? (int)$data['kind_id'] : null,
            isset($data['aktiv']) ? 1 : 0,
        ];

        if (!empty($data['passwort'])) {
            if (strlen($data['passwort']) < 6) {
                $kinder = $db->query("SELECT id, vorname, nachname FROM kinder WHERE aktiv = 1")->fetchAll();
                $stmt = $db->prepare("SELECT * FROM benutzer WHERE id = ?");
                $stmt->execute([$id]);
                return $this->twig->render($response, 'admin/benutzer_form.twig', [
                    'benutzer' => array_merge($stmt->fetch(), $data),
                    'is_new' => false,
                    'kinder' => $kinder,
                    'fehler' => 'Passwort muss mindestens 6 Zeichen haben.',
                ]);
            }
            $hash = password_hash($data['passwort'], PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("UPDATE benutzer SET name=?, benutzername=?, passwort_hash=?, rolle=?, kind_id=?, aktiv=? WHERE id=?");
            $params[] = $id;
            $params[2] = $hash; // replace position
            // Rebuild correctly
            $stmt->execute([$data['name'], $data['benutzername'], $hash, $data['rolle'],
                !empty($data['kind_id']) ? (int)$data['kind_id'] : null,
                isset($data['aktiv']) ? 1 : 0, $id]);
        } else {
            $stmt = $db->prepare("UPDATE benutzer SET name=?, benutzername=?, rolle=?, kind_id=?, aktiv=? WHERE id=?");
            $stmt->execute([$data['name'], $data['benutzername'], $data['rolle'],
                !empty($data['kind_id']) ? (int)$data['kind_id'] : null,
                isset($data['aktiv']) ? 1 : 0, $id]);
        }

        $_SESSION['success'] = 'Benutzer «' . $data['name'] . '» wurde gespeichert.';
        return Redirect::to($response, '/admin/benutzer');
    }

    public function delete(Request $request, Response $response, string $id): Response
    {
        $id = (int)$id;

        // Eigenen Account nicht löschen
        if ($id === (int)($_SESSION['benutzer_id'] ?? 0)) {
            $_SESSION['success'] = 'Du kannst deinen eigenen Account nicht löschen.';
            return Redirect::to($response, '/admin/benutzer');
        }

        $db = Database::get();
        $db->prepare("DELETE FROM benutzer WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Benutzer wurde gelöscht.';
        return Redirect::to($response, '/admin/benutzer');
    }
}
