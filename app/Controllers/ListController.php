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
    /** Lists overview — now requires authentication */
    public function index(): void
    {
        // Require login for any list access
        if (!Auth::check()) {
            Flash::add('error', 'Bitte melde dich an, um Listen zu sehen.');
            $this->redirect('/login');
            return;
        }

        $uid = (int)Auth::id();
        $own = UserList::allByUser($uid);
        $public = UserList::allPublic(30); // still loaded, but only for logged-in users

        $token = Csrf::token('create_list');

        View::render('lists/index', [
            'title'    => 'Listen',
            'own'      => $own,
            'public'   => $public,
            'csrf'     => $token,
            'isLogged' => true,
        ]);
    }

    /** Create a list — unchanged (already required login) */
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

        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $visibility = (string)($_POST['visibility'] ?? 'public');
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
        $id = UserList::create($uid, $title, $description !== '' ? $description : null, $visibility);
        Flash::add('success', 'Liste erstellt.');
        $this->redirect('/lists/' . $id);
    }

    /** Show a single list — now requires authentication even for public lists */
    public function show(array $params): void
    {
        // Require login for viewing any list
        if (!Auth::check()) {
            Flash::add('error', 'Bitte melde dich an, um Listen zu sehen.');
            $this->redirect('/login');
            return;
        }

        $id = isset($params['id']) ? (int)$params['id'] : 0;
        $list = $id > 0 ? UserList::find($id) : null;

        if (!$list) {
            http_response_code(404);
            View::render('lists/show', ['title' => 'Liste nicht gefunden', 'listId' => $id, 'list' => null]);
            return;
        }

        // Private lists: only owner may view; public lists: allowed because already logged in
        $uid = (int)Auth::id();
        if ($list->visibility === 'private' && $list->user_id !== $uid) {
            http_response_code(403);
            View::render('lists/show', ['title' => 'Zugriff verweigert', 'listId' => $id, 'list' => null]);
            return;
        }

        $items = Item::forListWithStats($list->id, $uid);

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
        $canAdd = $list->visibility === 'public'
            ? true
            : ($uid === $list->user_id);

        View::render('lists/show', [
            'title'           => $list->title,
            'listId'          => $list->id,
            'list'            => $list,
            'items'           => $items,
            'canAdd'          => $canAdd,
            'createItemToken' => $createItemToken,
            'rateTokens'      => $rateTokens,
            'commentsByItem'  => $commentsByItem,
            'currentUserId'   => $uid,
        ]);
    }

    private function redirect(string $path): void
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $loc = $base . $path;
        header('Location: ' . ($loc !== '' ? $loc : '/'));
        exit;
    }
}
