<?php

/**
 * Zweck: PDF-Erzeugung mit DomPDF aus PHP-Vorlagen inkl. passender HTTP-Response.
 */

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Http\Message\ResponseInterface as Response;

class PdfService
{
    public static function renderTemplate(string $template, array $data): string
    {
        $templatePath = __DIR__ . '/../../templates/pdf/' . $template . '.php';
        if (!file_exists($templatePath)) {
            return '<h1>Template nicht gefunden</h1>';
        }

        ob_start();
        extract($data);
        require $templatePath;
        return ob_get_clean();
    }

    public static function generateResponse(Response $response, string $html, string $filename): Response
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', __DIR__ . '/../../');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $response->getBody()->write($dompdf->output());

        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
