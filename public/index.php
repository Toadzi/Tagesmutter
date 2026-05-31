<?php

use App\Config\Bildungsbereiche;
use App\Database;
use App\Middleware\AuthMiddleware;
use App\Services\FileService;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

error_reporting(E_ALL & ~E_DEPRECATED);

// Deployment-Konfiguration laden (enthält APP_BASE_PATH)
$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require $configFile;
}
if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', '');
}

// Session-Sicherheitseinstellungen vor session_start()
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_path', APP_BASE_PATH . '/');
// ini_set('session.cookie_secure', '1'); // Nur bei HTTPS aktivieren

// Session starten (vor allem anderen)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/../vendor/autoload.php';

// Container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    Twig::class => function () {
        $twig = Twig::create(__DIR__ . '/../templates', [
            'cache' => false,
            'auto_reload' => true,
        ]);
        $twig->getEnvironment()->addGlobal('current_date', date('Y-m-d'));
        $twig->getEnvironment()->addGlobal('base_path', APP_BASE_PATH);
        $twig->getEnvironment()->addGlobal('session_user_id', $_SESSION['benutzer_id'] ?? null);
        $twig->getEnvironment()->addGlobal('session_user_name', $_SESSION['benutzer_name'] ?? '');
        $twig->getEnvironment()->addGlobal('session_rolle', $_SESSION['rolle'] ?? '');
        $twig->getEnvironment()->addGlobal('session_kind_id', $_SESSION['kind_id'] ?? null);
        // Flash messages aus Session lesen und danach löschen
        $twig->getEnvironment()->addGlobal('flash_success', $_SESSION['success'] ?? null);
        $twig->getEnvironment()->addGlobal('flash_error',   $_SESSION['error']   ?? null);
        unset($_SESSION['success'], $_SESSION['error']);
        // App-Einstellungen aus DB laden (nur wenn DB bereits existiert)
        try {
            $db = \App\Database::get();
            $settingRows = $db->query("SELECT schluessel, wert FROM einstellungen")->fetchAll();
            $appSettings = [];
            foreach ($settingRows as $r) { $appSettings[$r['schluessel']] = $r['wert']; }
        } catch (\Throwable $e) { $appSettings = []; }
        $twig->getEnvironment()->addGlobal('app_settings', $appSettings);
        // Bildungsbereiche zentral als Twig-Globals verfügbar machen
        $twig->getEnvironment()->addGlobal('bereich_keys', Bildungsbereiche::KEYS);
        $twig->getEnvironment()->addGlobal('bereich_labels', Bildungsbereiche::LABELS);
        $twig->getEnvironment()->addGlobal('bereich_icons', Bildungsbereiche::EMOJIS);
        return $twig;
    },
]);

$container = $containerBuilder->build();
$app = Bridge::create($container);
$app->setBasePath(APP_BASE_PATH);

// Middleware
$app->addRoutingMiddleware();
$app->add(TwigMiddleware::createFromContainer($app, Twig::class));
$app->add(new AuthMiddleware());
$app->addErrorMiddleware(true, true, true);

// Ensure data directories exist
foreach (['data', 'data/uploads', 'data/uploads/fotos', 'data/uploads/thumbs', 'data/backups'] as $dir) {
    FileService::ensureDir(__DIR__ . '/../' . $dir);
}

// Run migrations
Database::migrate();

// Routes
require __DIR__ . '/../src/routes.php';

$app->run();
