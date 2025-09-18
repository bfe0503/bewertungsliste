<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Item;
use App\Models\Rating;
use App\Models\UserList;

final class ItemController
{
    /** Create item in a list */
    public function create(array $params): void
    {
        $listId = isset($params['id']) ? (int)$params['id'] : 0;
        $list = $listId > 0 ? UserList::find($listId) : null;

        if (!$list) {
            http_response_code(404);
            Flash::add('error', 'Liste nicht gefunden.');
            $this->redirect('/lists');
            return;
        }

        if (!Auth::check()) {
            Flash::add('error', 'Bitte anmelden.');
            $this->redirect('/login');
            return;
        }

        // Private lists: only owner may add items; public lists: any logged-in user
        if ((int)$list->is_public !== 1 && $list->user_id !== (int)Auth::id()) {
            http_response_code(403);
            Flash::add('error', 'Kein Zugriff auf diese private Liste.');
            $this->redirect('/lists/' . $listId);
            return;
        }

        if (!Csrf::validate('create_item_' . $listId, $_POST['csrf'] ?? null)) {
            http_response_code(403);
            Flash::add('error', 'Ungültiges CSRF-Token.');
            $this->redirect('/lists/' . $listId);
            return;
        }

        // Accept both legacy (name/description) and new (title/url) payloads.
        $title = trim((string)($_POST['title'] ?? $_POST['name'] ?? ''));
        $url   = trim((string)($_POST['url'] ?? $_POST['description'] ?? ''));

        if ($title === '') {
            Flash::add('error', 'Name/Titel ist erforderlich.');
            $this->redirect('/lists/' . $listId);
            return;
        }
        if (mb_strlen($title) > 255) {
            Flash::add('error', 'Name/Titel ist zu lang (max. 255).');
            $this->redirect('/lists/' . $listId);
            return;
        }
        if ($url !== '' && mb_strlen($url) > 2048) {
            Flash::add('error', 'URL ist zu lang (max. 2048).');
            $this->redirect('/lists/' . $listId);
            return;
        }

        Item::create($listId, (int)Auth::id(), $title, $url !== '' ? $url : null);
        Flash::add('success', 'Eintrag hinzugefügt.');
        $this->redirect('/lists/' . $listId);
    }

    /** Rate an item via JSON (AJAX). Supports optional "comment" and "clearComment". */
    public function rate(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Bitte anmelden.']);
            return;
        }

        $itemId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($itemId <= 0) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Item nicht gefunden.']);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Ungültiges JSON.']);
            return;
        }

        $score        = isset($data['score']) ? (int)$data['score'] : 0;
        $csrf         = isset($data['csrf']) ? (string)$data['csrf'] : '';
        $commentInput = array_key_exists('comment', $data) ? $data['comment'] : null; // detect presence
        $clearComment = isset($data['clearComment']) ? (bool)$data['clearComment'] : false;

        $comment = null;
        if ($commentInput !== null) {
            $comment = trim((string)$commentInput);
            if ($comment === '') {
                // empty string means "no change" unless clearComment is true
                $comment = null;
            }
        }

        if (!Csrf::validate('rate_' . $itemId, $csrf)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Ungültiges CSRF-Token.']);
            return;
        }
        if ($score < 1 || $score > 5) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Score muss 1–5 sein.']);
            return;
        }
        if (!$clearComment && $comment !== null && mb_strlen($comment) > 2000) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Kommentar ist zu lang (max. 2000 Zeichen).']);
            return;
        }

        Rating::upsertWithPolicy($itemId, (int)Auth::id(), $score, $comment, $clearComment);
        $stats = Rating::stats($itemId);

        // Issue a fresh CSRF token so the client can rate again without reload.
        $nextCsrf = Csrf::token('rate_' . $itemId);

        echo json_encode([
            'ok'        => true,
            'itemId'    => $itemId,
            'score'     => $score,
            'avg'       => $stats['avg'],
            'count'     => $stats['count'],
            'message'   => 'Bewertung gespeichert.',
            'next_csrf' => $nextCsrf,
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
