<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\User;

final class AccountController
{
    public function show(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Flash::add('error', 'Bitte anmelden.');
            $this->redirect('/login');
            return;
        }
        $user = User::findById($uid);
        $token = Csrf::token('account_update');
        View::render('account/index', [
            'title' => 'Mein Konto',
            'csrf'  => $token,
            'user'  => $user,
        ]);
    }

    public function update(): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Flash::add('error', 'Bitte anmelden.');
            $this->redirect('/login');
            return;
        }
        if (!Csrf::validate('account_update', $_POST['csrf'] ?? '')) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/account');
            return;
        }

        $username = strtolower(trim((string)($_POST['username'] ?? '')));
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string)($_POST['new_password_confirm'] ?? '');

        $errors = [];

        if (!preg_match('/^[a-z0-9_.-]{3,32}$/', (string)($_POST['username'] ?? ''))) {
            $errors[] = 'Ungültiger Benutzername.';
        }
        if ($newPassword !== '') {
            if (strlen($newPassword) < 12) {
                $errors[] = 'Das Passwort muss mindestens 12 Zeichen lang sein.';
            }
            if ($newPassword !== $newPasswordConfirm) {
                $errors[] = 'Passwort-Bestätigung stimmt nicht.';
            }
            // Verify current password for change
            $user = User::findById($uid);
            if (!$user || !password_verify($currentPassword, $user->password_hash)) {
                $errors[] = 'Aktuelles Passwort ist falsch.';
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $e) {
                Flash::add('error', $e);
            }
            $this->redirect('/account');
            return;
        }

        // Persist username (case-insensitive unique)
        try {
            User::updateUsername($uid, (string)($_POST['username'] ?? ''));
        } catch (\Throwable $e) {
            Flash::add('error', 'Benutzername bereits vergeben.');
            $this->redirect('/account');
            return;
        }

        if ($newPassword !== '') {
            User::updatePassword($uid, password_hash($newPassword, PASSWORD_ARGON2ID));
        }

        Flash::add('success', 'Konto aktualisiert.');
        $this->redirect('/account');
    }

    private function redirect(string $path): void
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $loc = $base . $path;
        header('Location: ' . ($loc !== '' ? $loc : '/'));
        exit;
    }
}