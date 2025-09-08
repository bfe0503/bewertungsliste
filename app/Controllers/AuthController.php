<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Core\Auth;
use App\Models\User;

final class AuthController
{
    public function showRegister(): void
    {
        $token = Csrf::token('register');
        View::render('auth/register', ['title' => 'Registrieren', 'csrf' => $token]);
    }

    public function register(): void
    {
        $ok = Csrf::validate('register', $_POST['csrf'] ?? null);
        if (!$ok) {
            http_response_code(403);
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/register');
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $display = trim((string)($_POST['display_name'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::add('error', 'Bitte gib eine gültige E-Mail-Adresse ein.');
            $this->redirect('/register');
            return;
        }
        if (strlen($password) < 6) {
            Flash::add('error', 'Das Passwort muss mindestens 6 Zeichen lang sein.');
            $this->redirect('/register');
            return;
        }
        if (User::findByEmail($email)) {
            Flash::add('error', 'Diese E-Mail ist bereits registriert.');
            $this->redirect('/register');
            return;
        }

        $userId = User::create($email, $password, $display !== '' ? $display : null);
        Auth::login($userId);
        Flash::add('success', 'Willkommen! Du bist jetzt angemeldet.');
        $this->redirect('/');
    }

    public function showLogin(): void
    {
        $token = Csrf::token('login');
        View::render('auth/login', ['title' => 'Anmelden', 'csrf' => $token]);
    }

    public function login(): void
    {
        $ok = Csrf::validate('login', $_POST['csrf'] ?? null);
        if (!$ok) {
            http_response_code(403);
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/login');
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $user = $email !== '' ? User::findByEmail($email) : null;
        if (!$user || !password_verify($password, $user->password_hash)) {
            Flash::add('error', 'Zugangsdaten ungültig.');
            $this->redirect('/login');
            return;
        }

        Auth::login($user->id);
        Flash::add('success', 'Erfolgreich angemeldet.');
        $this->redirect('/');
    }

    public function logout(): void
    {
        if (!Csrf::validate('logout', $_POST['csrf'] ?? null)) {
            http_response_code(403);
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/');
            return;
        }
        Auth::logout();
        Flash::add('success', 'Abgemeldet.');
        $this->redirect('/');
    }

    private function redirect(string $path): void
    {
        // Build location that respects subfolder deployment
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $loc = $base . $path;
        header('Location: ' . ($loc !== '' ? $loc : '/'));
        exit;
    }
}
