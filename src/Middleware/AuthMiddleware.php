<?php

namespace App\Middleware;

use App\Http\Redirect;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Pfad ohne BASE_PATH ermitteln, damit alle Checks unabhängig vom
        // Installationsverzeichnis funktionieren (z.B. /tagesmutter/login → /login)
        $fullPath = $request->getUri()->getPath();
        $base     = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
        $path     = ($base !== '' && str_starts_with($fullPath, $base))
                    ? substr($fullPath, strlen($base))
                    : $fullPath;
        if ($path === '' || $path === false) {
            $path = '/';
        }

        // Immer erlaubt
        if (str_starts_with($path, '/login') || str_starts_with($path, '/logout')) {
            return $handler->handle($request);
        }

        // Nicht eingeloggt → Login
        if (empty($_SESSION['benutzer_id'])) {
            return Redirect::to(new SlimResponse(), '/login');
        }

        // Benutzerrolle prüfen
        $rolle = $_SESSION['rolle'] ?? '';
        $kindId = $_SESSION['kind_id'] ?? null;

        // Admin-Bereich nur für Admins
        if (str_starts_with($path, '/admin') && $rolle !== 'admin') {
            return Redirect::to(new SlimResponse(), '/');
        }

        // Eltern/Leserecht dürfen nur ihr Kind sehen, keine Schreiboperationen
        if ($rolle !== 'admin') {
            // Schreiboperationen (POST) außer bei erlaubten Pfaden
            if ($request->getMethod() === 'POST') {
                $response = new SlimResponse();
                return $response->withStatus(403)->withHeader('Content-Type', 'text/html');
            }

            // Nur Zugriff auf das eigene Kind
            if ($kindId && preg_match('#^/kinder/(\d+)#', $path, $m)) {
                if ((int)$m[1] !== (int)$kindId) {
                    return Redirect::to(new SlimResponse(), '/kinder/' . $kindId);
                }
            }
        }

        // Benutzerdaten der Request mitgeben
        $request = $request
            ->withAttribute('benutzer_id', $_SESSION['benutzer_id'])
            ->withAttribute('rolle', $rolle)
            ->withAttribute('benutzer_name', $_SESSION['benutzer_name'] ?? '')
            ->withAttribute('kind_id', $kindId);

        return $handler->handle($request);
    }
}
