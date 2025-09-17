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
        if (!Csrf::validate('register', $_POST['csrf'] ?? '')) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/register');
            return;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $errors = [];

        if (!preg_match('/^[a-z0-9_.-]{3,32}$/', $username)) {
            $errors[] = 'Ungültiger Benutzername (erlaubt a-z, 0-9, _.-, 3–32 Zeichen).';
        }
        if (strlen($password) < 12) {
            $errors[] = 'Das Passwort muss mindestens 12 Zeichen lang sein.';
        }
        $lower = strtolower($username);
        if (User::findByUsernameLower($lower)) {
            $errors[] = 'Benutzername ist bereits vergeben.';
        }

        if ($errors) {
            foreach ($errors as $e) {
                Flash::add('error', $e);
            }
            $this->redirect('/register');
            return;
        }

        $uid = User::createWithUsername($username, password_hash($password, PASSWORD_ARGON2ID));
        Auth::login($uid);
        Flash::add('success', 'Willkommen, ' . htmlspecialchars($username));
        $this->redirect('/');
    }

    public function showLogin(): void
    {
        $token = Csrf::token('login');
        View::render('auth/login', ['title' => 'Anmelden', 'csrf' => $token]);
    }

    public function login(): void
    {
        if (!Csrf::validate('login', $_POST['csrf'] ?? '')) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/login');
            return;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $user = User::findByUsernameLower(strtolower($username));
        if (!$user || !password_verify($password, $user->password_hash)) {
            Flash::add('error', 'Anmeldedaten ungültig.');
            $this->redirect('/login');
            return;
        }

        Auth::login($user->id);
        Flash::add('success', 'Willkommen zurück, ' . htmlspecialchars($user->username ?? ''));
        $this->redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        Flash::add('success', 'Abgemeldet.');
        $this->redirect('/');
    }

    private function redirect(string $path): void
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $loc = $base . $path;
        header('Location: ' . ($loc !== '' ? $loc : '/'));
        exit;
    }
}