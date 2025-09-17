<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;
use App\Models\UserList;
use App\Models\Item;
use App\Models\Rating;

final class ListController
{
    public function index(): void
    {
        $own = [];
        if (Auth::check()) {
            $uid = (int)Auth::id();
            // DB uses user_id
            $own = UserList::allByUser($uid);
        }
        // public lists (is_public = 1)
        $public = UserList::allPublic(30);
        $token = Csrf::token('create_list');

        View::render('lists/index', [
            'title'    => 'Listen',
            'own'      => $own,
            'public'   => $public,
            'csrf'     => $token,
            'isLogged' => Auth::check(),
        ]);
    }

    public function create(): void
    {
        if (!Auth::check()) {
            Flash::add('error', 'Bitte melde dich an, um eine Liste zu erstellen.');
            $this->redirect('/login');
            return;
        }
        if (!Csrf::validate('create_list', $_POST['csrf'] ?? null)) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/lists');
            return;
        }

        $title       = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $visibility  = (string)($_POST['visibility'] ?? 'public');
        if (!in_array($visibility, ['public', 'private'], true)) {
            $visibility = 'public';
        }

        if ($title === '') {
            Flash::add('error', 'Titel ist erforderlich.');
            $this->redirect('/lists');
            return;
        }
        if (mb_strlen($title) > 150) {
            Flash::add('error', 'Titel ist zu lang (max. 150).');
            $this->redirect('/lists');
            return;
        }

        $uid = (int)Auth::id();
        $isPublic = $visibility === 'public' ? 1 : 0;

        // DB has columns: user_id, title, description, is_public
        $id = UserList::create($uid, $title, $description !== '' ? $description : null, $isPublic);

        Flash::add('success', 'Liste erstellt.');
        $this->redirect('/lists/' . $id);
    }

    public function show(array $params): void
    {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $list = $id > 0 ? UserList::findById($id) : null;

        if (!$list) {
            http_response_code(404);
            View::render('lists/show', ['title' => 'Liste nicht gefunden', 'listId' => $id, 'list' => null]);
            return;
        }

        // Private list: only owner (user_id) may see
        if ((int)$list->is_public !== 1) {
            $uid = Auth::id();
            if ($uid === null || $list->user_id !== (int)$uid) {
                http_response_code(403);
                View::render('lists/show', ['title' => 'Zugriff verweigert', 'listId' => $id, 'list' => null]);
                return;
            }
        }

        $uid = Auth::id();
        $items = Item::forListWithStats($list->id, $uid !== null ? (int)$uid : null);

        // CSRF for item creation (form on this page)
        $createItemToken = Csrf::token('create_item_' . $list->id);

        // Per-item CSRF for rating (for AJAX)
        $rateTokens = [];
        $itemIds = [];
        foreach ($items as $it) {
            $rateTokens[$it['id']] = Csrf::token('rate_' . $it['id']);
            $itemIds[] = (int)$it['id'];
        }

        // Latest comments per item (up to 3) incl. user_id for own-comment detection
        $commentsByItem = Rating::latestCommentsForItems($itemIds, 3);

        // Can user add items? (public lists: any logged-in; private: only owner)
        $canAdd = ((int)$list->is_public === 1)
            ? Auth::check()
            : ((int)($uid ?? -1) === $list->user_id);

        View::render('lists/show', [
            'title'           => $list->title,
            'listId'          => $list->id,
            'list'            => $list,
            'items'           => $items,
            'canAdd'          => $canAdd,
            'createItemToken' => $createItemToken,
            'rateTokens'      => $rateTokens,
            'commentsByItem'  => $commentsByItem,
            'currentUserId'   => $uid !== null ? (int)$uid : null,
        ]);
    }

    public function edit(array $params): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Flash::add('error', 'Bitte anmelden.');
            $this->redirect('/login');
            return;
        }
        $id = (int)($params['id'] ?? 0);
        $list = UserList::findById($id);
        if (!$list || (int)$list->user_id !== (int)$uid) {
            Flash::add('error', 'Nicht berechtigt.');
            $this->redirect('/');
            return;
        }
        $token = Csrf::token('list_update_' . $id);
        View::render('lists/edit', ['title'=>'Liste bearbeiten', 'list'=>$list, 'csrf'=>$token]);
    }

    public function update(array $params): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Flash::add('error', 'Bitte anmelden.');
            $this->redirect('/login');
            return;
        }
        $id = (int)($params['id'] ?? 0);
        if (!Csrf::validate('list_update_' . $id, $_POST['csrf'] ?? '')) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/');
            return;
        }
        $list = UserList::findById($id);
        if (!$list || (int)$list->user_id !== (int)$uid) {
            Flash::add('error', 'Nicht berechtigt.');
            $this->redirect('/');
            return;
        }
        $title      = trim((string)($_POST['title'] ?? ''));
        $visibility = (string)($_POST['visibility'] ?? 'public');
        $isPublic   = $visibility === 'public' ? 1 : 0;

        UserList::update($id, $title, $isPublic);
        Flash::add('success', 'Liste aktualisiert.');
        $this->redirect('/lists/' . $id);
    }

    public function delete(array $params): void
    {
        $uid = Auth::id();
        if ($uid === null) {
            Flash::add('error', 'Bitte anmelden.');
            $this->redirect('/login');
            return;
        }
        $id = (int)($params['id'] ?? 0);
        if (!Csrf::validate('list_delete_' . $id, $_POST['csrf'] ?? '')) {
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/');
            return;
        }
        $list = UserList::findById($id);
        if (!$list || (int)$list->user_id !== (int)$uid) {
            Flash::add('error', 'Nicht berechtigt.');
            $this->redirect('/');
            return;
        }
        UserList::deleteCascade($id);
        Flash::add('success', 'Liste gelöscht.');
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
