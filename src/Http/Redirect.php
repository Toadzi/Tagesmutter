<?php

namespace App\Http;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Zentraler Helper für HTTP-Redirects.
 * Prefixt relative Pfade mit APP_BASE_PATH und setzt Status 302.
 */
class Redirect
{
    /**
     * Weiterleitung auf einen anwendungsinternen Pfad (z. B. "/kinder/42").
     * APP_BASE_PATH wird automatisch vorangestellt.
     */
    public static function to(Response $response, string $path): Response
    {
        return $response
            ->withHeader('Location', APP_BASE_PATH . $path)
            ->withStatus(302);
    }

    /**
     * Weiterleitung auf eine vollständig aufgebaute URL (bereits inkl. APP_BASE_PATH).
     * Für Sonderfälle wie dynamisch aus Request-Parametern konstruierte Redirects.
     */
    public static function toUrl(Response $response, string $url): Response
    {
        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
