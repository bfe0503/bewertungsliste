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
        if ($list->visibility === 'private' && $list->user_id !== (int)Auth::id()) {
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

        $name = trim((string)($_POST['name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));

        if ($name === '') {
            Flash::add('error', 'Name ist erforderlich.');
            $this->redirect('/lists/' . $listId);
            return;
        }
        if (mb_strlen($name) > 150) {
            Flash::add('error', 'Name ist zu lang (max. 150).');
            $this->redirect('/lists/' . $listId);
            return;
        }

        Item::create($listId, $name, $desc !== '' ? $desc : null);
        Flash::add('success', 'Eintrag hinzugefügt.');
        $this->redirect('/lists/' . $listId);
    }

    /** Rate an item via JSON (AJAX). Supports optional "comment". */
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

        $score   = isset($data['score']) ? (int)$data['score'] : 0;
        $csrf    = isset($data['csrf']) ? (string)$data['csrf'] : '';
        $comment = isset($data['comment']) ? trim((string)$data['comment']) : null;
        if ($comment === '') {
            $comment = null;
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
        if ($comment !== null && mb_strlen($comment) > 2000) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Kommentar ist zu lang (max. 2000 Zeichen).']);
            return;
        }

        Rating::upsert($itemId, (int)Auth::id(), $score, $comment);
        $stats = Rating::stats($itemId);

        echo json_encode([
            'ok'      => true,
            'itemId'  => $itemId,
            'score'   => $score,
            'avg'     => $stats['avg'],
            'count'   => $stats['count'],
            'message' => 'Bewertung gespeichert.',
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
