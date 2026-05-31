<?php

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MeilensteineController
{
    public function store(Request $request, Response $response, string $kind_id): Response
    {
        $kindId = (int)$kind_id;
        $data = $request->getParsedBody();
        $db = Database::get();

        $stmt = $db->prepare("
            INSERT INTO meilensteine (kind_id, datum, bildungsbereich, beschreibung)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $kindId,
            $data['datum'] ?: date('Y-m-d'),
            $data['bildungsbereich'] ?? '',
            $data['beschreibung'],
        ]);

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=meilensteine');
    }

    public function delete(Request $request, Response $response, string $kind_id, string $id): Response
    {
        $kindId = (int)$kind_id;
        $db = Database::get();

        $db->prepare("DELETE FROM meilensteine WHERE id = ? AND kind_id = ?")
           ->execute([(int)$id, $kindId]);

        return Redirect::to($response, '/kinder/' . $kindId . '?tab=meilensteine');
    }
}
