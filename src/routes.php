<?php

/**
 * Zweck: Zentrale Routendefinition – ordnet URLs den Controller-Methoden zu (Auth, Kinder, Tagebuch, Portfolio, Anwesenheit, Berichte, Unfälle, Admin).
 */

use App\Controllers\KinderController;
use App\Controllers\DashboardController;
use App\Controllers\TagebuchController;
use App\Controllers\AnwesenheitController;
use App\Controllers\PortfolioController;
use App\Controllers\BerichtController;
use App\Controllers\KontakteController;
use App\Controllers\DokumenteController;
use App\Controllers\MeilensteineController;
use App\Controllers\TextbausteineController;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\BenutzerController;
use App\Controllers\UnfallController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/** @var App $app */

// Static files (uploads) — Authentifizierung über globale AuthMiddleware
$app->get('/uploads/{path:.*}', function ($request, $response, string $path) {
    // Pfad-Traversal verhindern
    $normalised = realpath(__DIR__ . '/../data/uploads/' . $path);
    $base = realpath(__DIR__ . '/../data/uploads');

    if (!$normalised || !$base || !str_starts_with($normalised, $base . DIRECTORY_SEPARATOR)) {
        return $response->withStatus(403);
    }

    if (!file_exists($normalised) || !is_file($normalised)) {
        return $response->withStatus(404);
    }

    $mime = mime_content_type($normalised) ?: 'application/octet-stream';
    $response->getBody()->write(file_get_contents($normalised));
    return $response->withHeader('Content-Type', $mime);
});

// Auth
$app->get('/login', AuthController::class . ':loginForm');
$app->post('/login', AuthController::class . ':login');
$app->get('/logout', AuthController::class . ':logout');

// Admin
$app->group('/admin', function (RouteCollectorProxy $group) {
    $group->get('', AdminController::class . ':index');
    $group->get('/einstellungen', AdminController::class . ':einstellungen');
    $group->post('/einstellungen', AdminController::class . ':einstellungenSave');
    $group->get('/benutzer', BenutzerController::class . ':index');
    $group->get('/benutzer/neu', BenutzerController::class . ':create');
    $group->post('/benutzer/neu', BenutzerController::class . ':store');
    $group->get('/benutzer/{id:[0-9]+}/bearbeiten', BenutzerController::class . ':edit');
    $group->post('/benutzer/{id:[0-9]+}/bearbeiten', BenutzerController::class . ':update');
    $group->post('/benutzer/{id:[0-9]+}/loeschen', BenutzerController::class . ':delete');

    // Textbausteine
    $group->get('/textbausteine', TextbausteineController::class . ':index');
    $group->post('/textbausteine/neu', TextbausteineController::class . ':store');
    $group->post('/textbausteine/{id:[0-9]+}/bearbeiten', TextbausteineController::class . ':update');
    $group->post('/textbausteine/{id:[0-9]+}/loeschen', TextbausteineController::class . ':delete');
    $group->post('/textbausteine/sortieren', TextbausteineController::class . ':sort');
    $group->get('/textbausteine/export', TextbausteineController::class . ':export');
    $group->post('/textbausteine/import', TextbausteineController::class . ':import');
});

// Dashboard
$app->get('/', DashboardController::class . ':index');

// Unfallbuch (global)
$app->get('/unfaelle', UnfallController::class . ':globalIndex');

// Kinder
$app->group('/kinder', function (RouteCollectorProxy $group) {
    $group->get('', KinderController::class . ':index');
    $group->get('/neu', KinderController::class . ':create');
    $group->post('/neu', KinderController::class . ':store');
    $group->get('/{id:[0-9]+}', KinderController::class . ':show');
    $group->get('/{id:[0-9]+}/bearbeiten', KinderController::class . ':edit');
    $group->post('/{id:[0-9]+}/bearbeiten', KinderController::class . ':update');
    $group->post('/{id:[0-9]+}/loeschen', KinderController::class . ':delete');
    $group->post('/{id:[0-9]+}/foto', KinderController::class . ':uploadFoto');
});

// Kontakte
$app->group('/kinder/{kind_id:[0-9]+}/kontakte', function (RouteCollectorProxy $group) {
    $group->post('/neu', KontakteController::class . ':store');
    $group->post('/{id:[0-9]+}/bearbeiten', KontakteController::class . ':update');
    $group->post('/{id:[0-9]+}/loeschen', KontakteController::class . ':delete');
});

// Tagebuch
$app->group('/kinder/{kind_id:[0-9]+}/tagebuch', function (RouteCollectorProxy $group) {
    $group->get('', TagebuchController::class . ':index');
    $group->post('/neu', TagebuchController::class . ':store');
    $group->get('/{id:[0-9]+}/bearbeiten', TagebuchController::class . ':edit');
    $group->post('/{id:[0-9]+}/bearbeiten', TagebuchController::class . ':update');
    $group->post('/{id:[0-9]+}/loeschen', TagebuchController::class . ':delete');
});

// Portfolio (Fotos)
$app->group('/kinder/{kind_id:[0-9]+}/portfolio', function (RouteCollectorProxy $group) {
    $group->get('', PortfolioController::class . ':index');
    $group->post('/upload', PortfolioController::class . ':upload');
    $group->post('/{id:[0-9]+}/bearbeiten', PortfolioController::class . ':update');
    $group->post('/{id:[0-9]+}/loeschen', PortfolioController::class . ':delete');
    $group->get('/pdf', PortfolioController::class . ':exportPdf');
});

// Anwesenheit
$app->group('/kinder/{kind_id:[0-9]+}/anwesenheit', function (RouteCollectorProxy $group) {
    $group->get('', AnwesenheitController::class . ':index');
    $group->post('/speichern', AnwesenheitController::class . ':save');
    $group->post('/schnell', AnwesenheitController::class . ':quickAction');
    $group->get('/pdf', AnwesenheitController::class . ':exportPdf');
    $group->get('/csv', AnwesenheitController::class . ':exportCsv');
});

// Meilensteine
$app->group('/kinder/{kind_id:[0-9]+}/meilensteine', function (RouteCollectorProxy $group) {
    $group->post('/neu', MeilensteineController::class . ':store');
    $group->post('/{id:[0-9]+}/loeschen', MeilensteineController::class . ':delete');
});

// Dokumente
$app->group('/kinder/{kind_id:[0-9]+}/dokumente', function (RouteCollectorProxy $group) {
    $group->post('/upload', DokumenteController::class . ':upload');
    $group->get('/{id:[0-9]+}/download', DokumenteController::class . ':download');
    $group->post('/{id:[0-9]+}/loeschen', DokumenteController::class . ':delete');
});

// Unfallbuch (je Kind)
$app->group('/kinder/{kind_id:[0-9]+}/unfaelle', function (RouteCollectorProxy $group) {
    $group->get('',                                    UnfallController::class . ':index');
    $group->get('/neu',                                UnfallController::class . ':create');
    $group->post('/neu',                               UnfallController::class . ':store');
    $group->get('/{id:[0-9]+}',                        UnfallController::class . ':show');
    $group->get('/{id:[0-9]+}/bearbeiten',             UnfallController::class . ':edit');
    $group->post('/{id:[0-9]+}/bearbeiten',            UnfallController::class . ':update');
    $group->post('/{id:[0-9]+}/loeschen',              UnfallController::class . ':delete');
    $group->get('/{id:[0-9]+}/pdf',                    UnfallController::class . ':exportPdf');
});

// Berichte
$app->group('/kinder/{kind_id:[0-9]+}/berichte', function (RouteCollectorProxy $group) {
    $group->get('', BerichtController::class . ':index');
    $group->get('/neu', BerichtController::class . ':create');
    $group->post('/neu', BerichtController::class . ':store');
    $group->get('/{id:[0-9]+}', BerichtController::class . ':show');
    $group->get('/{id:[0-9]+}/bearbeiten', BerichtController::class . ':edit');
    $group->post('/{id:[0-9]+}/bearbeiten', BerichtController::class . ':update');
    $group->post('/{id:[0-9]+}/loeschen', BerichtController::class . ':delete');
    $group->get('/{id:[0-9]+}/pdf', BerichtController::class . ':exportPdf');
});
