<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\User;

final class AuthController
{
    public function showRegister(): void
    {
        $token = Csrf::token('register');
        View::render('auth/register', ['title' => 'Register', 'csrf' => $token]);
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

        if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 32) {
            Flash::add('error', 'Username 3–32 Zeichen.');
            $this->redirect('/register');
            return;
        }
        if (mb_strlen($password) < 8) {
            Flash::add('error', 'Passwort zu kurz (min. 8).');
            $this->redirect('/register');
            return;
        }

        if (User::findByUsernameLower(mb_strtolower($username))) {
            Flash::add('error', 'Username bereits vergeben.');
            $this->redirect('/register');
            return;
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $uid  = User::create($username, null, $hash); // no email

        Auth::login($uid);
        $_SESSION['is_admin'] = 0;
        $this->redirect('/'); // Registrierte landen auf der Startseite
    }

    public function showLogin(): void
    {
        $token = Csrf::token('login');
        View::render('auth/login', ['title' => 'Login', 'csrf' => $token]);
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

        $u = User::findByUsernameLower(mb_strtolower($username));
        if (!$u || !password_verify($password, (string)$u->password_hash)) {
            Flash::add('error', 'Ungültige Zugangsdaten.');
            $this->redirect('/login');
            return;
        }

        Auth::login((int)$u->id);
        // Merker für Navigation/Redirect (ohne extra DB-Query)
        $_SESSION['is_admin'] = (int)($u->is_admin ?? 0);

        // NEU: Admins landen direkt im Admin-Dashboard
        if ((int)$u->is_admin === 1) {
            $this->redirect('/admin');
            return;
        }

        // Normale Nutzer auf Home
        $this->redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        unset($_SESSION['is_admin']);
        $this->redirect('/');
    }

    private function redirect(string $path): void
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $loc  = $base . $path;
        header('Location: ' . ($loc !== '' ? $loc : '/'));
        exit;
    }
}
