<?php

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class KontakteController
{
    public function store(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $stmt = $db->prepare("
            INSERT INTO kontakte (kind_id, rolle, name, telefon, mobil, email, notfall, abholberechtigt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $kindId,
            $data['rolle'] ?? '',
            $data['name'],
            $data['telefon'] ?? '',
            $data['mobil'] ?? '',
            $data['email'] ?? '',
            isset($data['notfall']) ? 1 : 0,
            isset($data['abholberechtigt']) ? 1 : 0,
        ]);

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=kontakte');
    }

    public function update(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $stmt = $db->prepare("
            UPDATE kontakte SET rolle = ?, name = ?, telefon = ?, mobil = ?, email = ?,
                notfall = ?, abholberechtigt = ?
            WHERE id = ? AND kind_id = ?
        ");

        $stmt->execute([
            $data['rolle'] ?? '',
            $data['name'],
            $data['telefon'] ?? '',
            $data['mobil'] ?? '',
            $data['email'] ?? '',
            isset($data['notfall']) ? 1 : 0,
            isset($data['abholberechtigt']) ? 1 : 0,
            $id,
            $kindId,
        ]);

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=kontakte');
    }

    public function delete(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $id = (int)$id;
        $db = Database::get();

        $stmt = $db->prepare("DELETE FROM kontakte WHERE id = ? AND kind_id = ?");
        $stmt->execute([$id, $kindId]);

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=kontakte');
    }
}
