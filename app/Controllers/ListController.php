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
        if (!Auth::check()) {
            Flash::add('error', 'Bitte melde dich an, um Listen zu sehen.');
            $this->redirect('/login');
            return;
        }

        $uid    = (int)Auth::id();
        $own    = UserList::allByUser($uid);
        $public = UserList::allPublic(30);

        $token = Csrf::token('create_list');

        View::render('lists/index', [
            'title'    => 'Listen',
            'own'      => $own,
            'public'   => $public,
            'csrf'     => $token,
            'isLogged' => true,
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
        $isPublic    = $visibility === 'private' ? 0 : 1;

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
        $id  = UserList::create($uid, $title, $description !== '' ? $description : null, $isPublic);
        Flash::add('success', 'Liste erstellt.');
        $this->redirect('/lists/' . $id);
    }

    public function show(array $params): void
    {
        if (!Auth::check()) {
            Flash::add('error', 'Bitte melde dich an, um Listen zu sehen.');
            $this->redirect('/login');
            return;
        }

        $id   = isset($params['id']) ? (int)$params['id'] : 0;
        $list = $id > 0 ? UserList::find($id) : null;

        if (!$list) {
            http_response_code(404);
            View::render('lists/show', ['title' => 'Liste nicht gefunden', 'listId' => $id, 'list' => null]);
            return;
        }

        $uid      = (int)Auth::id();
        $isPublic = (int)($list->is_public ?? 0) === 1;

        if (!$isPublic && (int)$list->user_id !== $uid) {
            http_response_code(403);
            View::render('lists/show', ['title' => 'Zugriff verweigert', 'listId' => $id, 'list' => null]);
            return;
        }

        $items = Item::forListWithStats((int)$list->id, $uid);

        $createItemToken = Csrf::token('create_item_' . $list->id);

        $rateTokens = [];
        $itemIds    = [];
        foreach ($items as $it) {
            $rateTokens[$it['id']] = Csrf::token('rate_' . $it['id']);
            $itemIds[]             = (int)$it['id'];
        }

        $commentsByItem = Rating::latestCommentsForItems($itemIds, 3);

        $canAdd = $isPublic ? true : ($uid === (int)$list->user_id);

        View::render('lists/show', [
            'title'           => (string)$list->title,
            'listId'          => (int)$list->id,
            'list'            => $list,
            'items'           => $items,
            'canAdd'          => $canAdd,
            'createItemToken' => $createItemToken,
            'rateTokens'      => $rateTokens,
            'commentsByItem'  => $commentsByItem,
            'currentUserId'   => $uid,
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
        $id   = (int)($params['id'] ?? 0);
        $list = UserList::find($id);
        if (!$list || (int)$list->user_id !== (int)$uid) {
            Flash::add('error', 'Nicht berechtigt.');
            $this->redirect('/lists');
            return;
        }
        $updateCsrf = Csrf::token('list_update_' . $id);
        $deleteCsrf = Csrf::token('list_delete_' . $id);

        View::render('lists/edit', [
            'title'      => 'Liste bearbeiten',
            'list'       => $list,
            'csrf'       => $updateCsrf,
            'deleteCsrf' => $deleteCsrf,
        ]);
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
            $this->redirect('/lists/' . $id . '/edit');
            return;
        }
        $list = UserList::find($id);
        if (!$list || (int)$list->user_id !== (int)$uid) {
            Flash::add('error', 'Nicht berechtigt.');
            $this->redirect('/lists');
            return;
        }

        $title       = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $visibility  = (string)($_POST['visibility'] ?? 'public');
        $isPublic    = $visibility === 'private' ? 0 : 1;

        if ($title === '') {
            Flash::add('error', 'Titel ist erforderlich.');
            $this->redirect('/lists/' . $id . '/edit');
            return;
        }
        if (mb_strlen($title) > 150) {
            Flash::add('error', 'Titel ist zu lang (max. 150).');
            $this->redirect('/lists/' . $id . '/edit');
            return;
        }

        UserList::update($id, $title, $description !== '' ? $description : null, $isPublic);
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
            $this->redirect('/lists');
            return;
        }
        $list = UserList::find($id);
        if (!$list || (int)$list->user_id !== (int)$uid) {
            Flash::add('error', 'Nicht berechtigt.');
            $this->redirect('/lists');
            return;
        }

        UserList::deleteCascade($id);
        Flash::add('success', 'Liste gelöscht.');
        $this->redirect('/lists');
    }

    private function redirect(string $path): void
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $loc  = $base . $path;
        header('Location: ' . ($loc !== '' ? $loc : '/'));
        exit;
    }
}
