<?php

/**
 * Zweck: Authentifizierung: Login-Formular, Login-Prüfung (bcrypt) und Logout.
 */

namespace App\Controllers;

use App\Database;
use App\Http\Redirect;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class AuthController
{
    public function __construct(private Twig $twig)
    {
    }

    public function loginForm(Request $request, Response $response): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!empty($_SESSION['benutzer_id'])) {
            return Redirect::to($response, '/');
        }

        return $this->twig->render($response, 'auth/login.twig', [
            'fehler' => $_SESSION['login_fehler'] ?? null,
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $data = $request->getParsedBody();
        $benutzername = trim($data['benutzername'] ?? '');
        $passwort = $data['passwort'] ?? '';

        // Brute-Force-Schutz: nach 5 Fehlversuchen 30 Sekunden sperren
        $fehlversuche = $_SESSION['login_fehlversuche'] ?? 0;
        $gesperrtBis  = $_SESSION['login_gesperrt_bis'] ?? 0;

        if ($gesperrtBis > time()) {
            $warten = $gesperrtBis - time();
            $_SESSION['login_fehler'] = "Zu viele Fehlversuche. Bitte {$warten} Sekunden warten.";
            return Redirect::to($response, '/login');
        }

        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM benutzer WHERE benutzername = ? AND aktiv = 1");
        $stmt->execute([$benutzername]);
        $user = $stmt->fetch();

        if ($user && password_verify($passwort, $user['passwort_hash'])) {
            // Fehlversuche zurücksetzen
            unset($_SESSION['login_fehlversuche'], $_SESSION['login_gesperrt_bis']);
            session_regenerate_id(true);
            $_SESSION['benutzer_id'] = $user['id'];
            $_SESSION['benutzer_name'] = $user['name'];
            $_SESSION['rolle'] = $user['rolle'];
            $_SESSION['kind_id'] = $user['kind_id'];
            unset($_SESSION['login_fehler']);

            // Letzten Login speichern
            $db->prepare("UPDATE benutzer SET letzter_login = datetime('now') WHERE id = ?")
               ->execute([$user['id']]);

            // Eltern → direkt zum eigenen Kind
            if ($user['rolle'] !== 'admin' && $user['kind_id']) {
                return Redirect::to($response, '/kinder/' . $user['kind_id']);
            }
            return Redirect::to($response, '/');
        }

        // Fehlversuch zählen
        $_SESSION['login_fehlversuche'] = $fehlversuche + 1;
        if ($_SESSION['login_fehlversuche'] >= 5) {
            $_SESSION['login_gesperrt_bis'] = time() + 30;
            $_SESSION['login_fehlversuche'] = 0;
            $_SESSION['login_fehler'] = 'Zu viele Fehlversuche. Bitte 30 Sekunden warten.';
        } else {
            $_SESSION['login_fehler'] = 'Benutzername oder Passwort falsch.';
        }
        return Redirect::to($response, '/login');
    }

    public function logout(Request $request, Response $response): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Session-Daten leeren
        $_SESSION = [];

        // Session-Cookie im Browser löschen
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        return Redirect::to($response, '/login');
    }
}
