<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\User;
use App\Models\UserList;

final class AdminController
{
    private function requireAdmin(): ?int
    {
        $uid = Auth::id();
        if ($uid === null) {
            Flash::add('error', 'Bitte anmelden.');
            $this->redirect('/login');
            return null;
        }
        $user = User::findById($uid);
        if (!$user || !($user->is_admin ?? false)) {
            Flash::add('error', 'Admin-Rechte erforderlich.');
            $this->redirect('/');
            return null;
        }
        return $uid;
    }

    public function dashboard(): void
    {
        if ($this->requireAdmin() === null) return;
        View::render('admin/dashboard', ['title' => 'Admin']);
    }

    public function users(): void
    {
        if ($this->requireAdmin() === null) return;
        $users = User::all();
        $token = Csrf::token('admin_users');
        View::render('admin/users', ['title' => 'Admin – Users', 'users'=>$users, 'csrf'=>$token]);
    }

    public function resetPassword(array $params): void
    {
        if ($this->requireAdmin() === null) return;
        if (!Csrf::validate('admin_users', $_POST['csrf'] ?? '')) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/admin/users');
            return;
        }
        $id = (int)($params['id'] ?? 0);
        $new = (string)($_POST['new_password'] ?? '');
        if (strlen($new) < 12) {
            Flash::add('error', 'Neues Passwort zu kurz.');
            $this->redirect('/admin/users');
            return;
        }
        User::updatePassword($id, password_hash($new, PASSWORD_ARGON2ID));
        Flash::add('success', 'Passwort aktualisiert.');
        $this->redirect('/admin/users');
    }

    public function deleteUser(array $params): void
    {
        if ($this->requireAdmin() === null) return;
        if (!Csrf::validate('admin_users', $_POST['csrf'] ?? '')) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/admin/users');
            return;
        }
        $id = (int)($params['id'] ?? 0);
        User::delete($id);
        Flash::add('success', 'Nutzer gelöscht.');
        $this->redirect('/admin/users');
    }

    public function lists(): void
    {
        if ($this->requireAdmin() === null) return;
        $lists = UserList::all();
        $token = Csrf::token('admin_lists');
        View::render('admin/lists', ['title' => 'Admin – Lists', 'lists'=>$lists, 'csrf'=>$token]);
    }

    public function deleteList(array $params): void
    {
        if ($this->requireAdmin() === null) return;
        if (!Csrf::validate('admin_lists', $_POST['csrf'] ?? '')) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/admin/lists');
            return;
        }
        $id = (int)($params['id'] ?? 0);
        UserList::deleteCascade($id);
        Flash::add('success', 'Liste gelöscht.');
        $this->redirect('/admin/lists');
    }

    private function redirect(string $path): void
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $loc = $base . $path;
        header('Location: ' . ($loc !== '' ? $loc : '/'));
        exit;
    }
}