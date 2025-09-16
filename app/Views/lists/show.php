<?php
declare(strict_types=1);

/** @var \App\Models\UserList|null $list */
/** @var int|null $listId */
/** @var array<int, array{
 *   id:int,
 *   name:string,
 *   description:?string,
 *   avg:float,
 *   count:int,
 *   my_score:?int,
 *   my_comment?:?string
 * }> $items
 * @var bool $canAdd
 * @var string $createItemToken
 * @var array<int,string> $rateTokens
 * @var array<int, array<int, array{user_id:int,user:string,score:int,comment:string,created_at:string}>> $commentsByItem
 */

if (!$list): ?>
  <div class="section">
    <h5>Liste nicht verfügbar</h5>
    <p class="grey-text">Diese Liste existiert nicht oder du hast keinen Zugriff.</p>
  </div>
<?php else:
  $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); ?>
  <div class="section">
    <h4><?= htmlspecialchars($list->title, ENT_QUOTES, 'UTF-8') ?></h4>
    <?php if ($list->description): ?>
      <p><?= nl2br(htmlspecialchars($list->description, ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>
    <p class="grey-text">Sichtbarkeit: <?= htmlspecialchars($list->visibility, ENT_QUOTES, 'UTF-8') ?></p>
  </div>

  <?php if ($canAdd): ?>
  <div class="card">
    <div class="card-content">
      <span class="card-title">Neuen Eintrag hinzufügen</span>
      <form method="post" action="<?= $base ?>/lists/<?= (int)$list->id ?>/items">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($createItemToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="input-field">
          <input id="name" name="name" type="text" maxlength="150" required>
          <label for="name">Name</label>
        </div>
        <div class="input-field">
          <textarea id="description" name="description" class="materialize-textarea" maxlength="2000"></textarea>
          <label for="description">Beschreibung (optional)</label>
        </div>
        <button class="btn waves-effect" type="submit">Hinzufügen</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="section">
    <h5>Einträge</h5>
    <?php if (empty($items)): ?>
      <p class="grey-text">Noch keine Einträge vorhanden.</p>
    <?php else: ?>
      <div class="row">
        <?php foreach ($items as $it): ?>
          <?php
            // Defensive reads to avoid PHP warnings if keys are missing.
            $itemId    = (int)$it['id'];
            $name      = (string)$it['name'];
            $desc      = isset($it['description']) && $it['description'] !== null ? (string)$it['description'] : null;
            $avg       = isset($it['avg']) ? (float)$it['avg'] : 0.0;
            $count     = isset($it['count']) ? (int)$it['count'] : 0;
            $myScore   = isset($it['my_score']) && $it['my_score'] !== null ? (int)$it['my_score'] : null;
            $myComment = isset($it['my_comment']) && $it['my_comment'] !== null ? (string)$it['my_comment'] : '';
            $rateCsrf  = htmlspecialchars($rateTokens[$itemId] ?? '', ENT_QUOTES, 'UTF-8');
          ?>
          <div class="col s12 m6">
            <div class="card">
              <div class="card-content">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                  <span class="card-title" style="margin:0;">
                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                  </span>

                  <span
                    class="chip my-score-badge"
                    data-item="<?= $itemId ?>"
                    style="<?= $myScore === null ? 'display:none;' : '' ?>"
                    aria-live="polite"
                  >
                    <i class="material-icons" style="vertical-align:middle;margin-right:4px;">star</i>
                    Deine Bewertung: <strong style="margin-left:4px;"><?= $myScore !== null ? $myScore : '' ?></strong>
                  </span>
                </div>

                <?php if ($desc): ?>
                  <p style="margin-top:8px;"><?= nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>

                <!-- Rating block -->
                <div class="mt-3">
                  <div class="rating"
                       data-item-id="<?= $itemId ?>"
                       data-csrf="<?= $rateCsrf ?>"
                       data-my-score="<?= $myScore !== null ? $myScore : 0 ?>">
                    <?php for ($s=1; $s<=5; $s++): ?>
                      <button type="button" class="star-btn" data-score="<?= $s ?>" aria-label="Bewerten mit <?= $s ?> Stern(en)">
                        <i class="material-icons star-icon"><?= ($myScore !== null && $myScore >= $s) ? 'star' : 'star_border' ?></i>
                      </button>
                    <?php endfor; ?>
                  </div>
                  <div class="text-muted mt-2">
                    Ø <span class="avg" data-item="<?= $itemId ?>"><?= number_format($avg, 2, ',', '') ?></span>
                    · <span class="count" data-item="<?= $itemId ?>"><?= $count ?></span> Bewertungen
                  </div>
                </div>

                <!-- Comment toggle + field (optional) -->
                <div class="mt-3">
                  <a href="#!" class="comment-toggle" data-target="<?= $itemId ?>">
                    <i class="material-icons left">chat</i>
                    <?= ($myComment !== '') ? 'Kommentar bearbeiten' : 'Kommentar hinzufügen/ändern (optional)' ?>
                  </a>
                  <div class="comment-block" id="comment-<?= $itemId ?>" style="display:none;">
                    <div class="input-field" style="margin-top:16px;">
                      <textarea class="materialize-textarea comment-input" maxlength="2000" data-item="<?= $itemId ?>"><?= htmlspecialchars($myComment, ENT_QUOTES, 'UTF-8') ?></textarea>
                      <label>Kommentar (max. 2000 Zeichen)</label>
                    </div>
                    <div class="right-align">
                      <button type="button" class="btn-flat clear-comment" data-item="<?= $itemId ?>">
                        Leeren
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Latest comments list -->
                <?php $cl = $commentsByItem[$itemId] ?? []; if (!empty($cl)): ?>
                  <div class="mt-3 comments-wrap" data-item="<?= $itemId ?>">
                    <span class="grey-text text-darken-1">Letzte Kommentare:</span>
                    <ul class="collection comments-list" style="border:none;" aria-live="polite">
                      <?php foreach ($cl as $c): ?>
                        <li class="collection-item"
                            data-user-id="<?= (int)$c['user_id'] ?>"
                            style="border:0;border-bottom:1px solid rgba(0,0,0,.06);">
                          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
                            <strong><?= htmlspecialchars($c['user'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="grey-text">
                              <?php for ($s=1; $s<=5; $s++): ?>
                                <i class="material-icons" style="font-size:18px;vertical-align:middle;">
                                  <?= ($c['score'] >= $s) ? 'star' : 'star_border' ?>
                                </i>
                              <?php endfor; ?>
                            </span>
                          </div>
                          <div class="comment-body" style="margin-top:6px;">
                            <?= nl2br(htmlspecialchars($c['comment'], ENT_QUOTES, 'UTF-8')) ?>
                          </div>
                          <div class="grey-text" style="margin-top:4px;font-size:.9rem;">
                            <?= htmlspecialchars(date('d.m.Y H:i', strtotime($c['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php else: ?>
                  <div class="mt-3 comments-wrap" data-item="<?= $itemId ?>" style="display:none;">
                    <span class="grey-text text-darken-1">Letzte Kommentare:</span>
                    <ul class="collection comments-list" style="border:none;" aria-live="polite"></ul>
                  </div>
                <?php endif; ?>

              </div> <!-- /card-content -->
            </div> <!-- /card -->
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Confirm modal -->
  <div id="confirm-update-modal" class="modal">
    <div class="modal-content">
      <h5>Bewertung aktualisieren?</h5>
      <p>Du hast dieses Element bereits bewertet. Möchtest du deine Bewertung überschreiben oder nur die Sterne anpassen?</p>
      <p class="grey-text">Hinweis: Lässt du das Kommentar-Feld leer, bleibt dein bisheriger Kommentar erhalten. Aktiviere „Kommentar löschen“, um ihn zu entfernen.</p>
      <p>
        <label>
          <input type="checkbox" id="confirm-clear-comment" />
          <span>Kommentar löschen</span>
        </label>
      </p>
    </div>
    <div class="modal-footer">
      <a href="#!" class="modal-close btn-flat" id="confirm-cancel">Abbrechen</a>
      <a href="#!" class="modal-close btn" id="confirm-apply">Aktualisieren</a>
    </div>
  </div>

  <script>
    (function(){
      const base = '<?= $base ?>';
      const isLogged = <?= \App\Core\Auth::check() ? 'true' : 'false' ?>;
      const currentUserId = <?= \App\Core\Auth::check() ? (int)\App\Core\Auth::id() : 'null' ?>;

      // Modal state
      let pending = { itemId: null, score: null, container: null };

      document.addEventListener('DOMContentLoaded', () => {
        if (window.M && M.Modal) {
          M.Modal.init(document.querySelectorAll('.modal'), { preventScrolling: true });
        }
      });

      function openConfirmModal(itemId, score, container){
        pending = { itemId, score, container };
        const cb = document.getElementById('confirm-clear-comment');
        if (cb) cb.checked = false;
        const modal = document.getElementById('confirm-update-modal');
        if (window.M && M.Modal && modal) {
          const instance = M.Modal.getInstance(modal) || M.Modal.init(modal);
          instance.open();
        }
      }

      function fillStars(container, score){
        const icons = container.querySelectorAll('.star-icon');
        icons.forEach((ic, idx) => {
          ic.textContent = (idx + 1) <= score ? 'star' : 'star_border';
        });
      }

      function formatDate(d){
        const pad = (n)=> String(n).padStart(2,'0');
        return `${pad(d.getDate())}.${pad(d.getMonth()+1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
      }

      function createCommentLi(user, score, comment, created, userId) {
        const li = document.createElement('li');
        li.className = 'collection-item';
        li.style.border = '0';
        li.style.borderBottom = '1px solid rgba(0,0,0,.06)';
        if (userId != null) li.setAttribute('data-user-id', String(userId));
        li.classList.add('fade-in');

        const top = document.createElement('div');
        top.style.display = 'flex';
        top.style.justifyContent = 'space-between';
        top.style.alignItems = 'center';
        top.style.gap = '8px';
        top.style.flexWrap = 'wrap';

        const strong = document.createElement('strong');
        strong.textContent = user;
        top.appendChild(strong);

        const stars = document.createElement('span');
        stars.className = 'grey-text';
        for (let s=1; s<=5; s++){
          const i = document.createElement('i');
          i.className = 'material-icons';
          i.style.fontSize = '18px';
          i.style.verticalAlign = 'middle';
          i.textContent = s <= score ? 'star' : 'star_border';
          stars.appendChild(i);
        }
        top.appendChild(stars);

        const body = document.createElement('div');
        body.className = 'comment-body';
        body.style.marginTop = '6px';
        body.textContent = comment;

        const meta = document.createElement('div');
        meta.className = 'grey-text';
        meta.style.marginTop = '4px';
        meta.style.fontSize = '.9rem';
        meta.textContent = created;

        li.appendChild(top);
        li.appendChild(body);
        li.appendChild(meta);
        return li;
      }

      function upsertOwnComment(ul, score, comment) {
        const userId = currentUserId;
        const created = formatDate(new Date());
        const newLi = createCommentLi('Du', score, comment, created, userId);

        const selector = userId != null ? `li.collection-item[data-user-id="${userId}"]` : null;
        const existing = selector ? ul.querySelector(selector) : null;

        if (existing) {
          ul.replaceChild(newLi, existing);
        } else if (ul.firstChild) {
          ul.insertBefore(newLi, ul.firstChild);
        } else {
          ul.appendChild(newLi);
        }

        while (ul.childElementCount > 3) {
          ul.removeChild(ul.lastElementChild);
        }
      }

      // Confirm modal buttons
      document.getElementById('confirm-apply')?.addEventListener('click', async () => {
        if (!pending.itemId || !pending.container) return;
        await submitRating(pending.container, pending.score, true);
        pending = { itemId: null, score: null, container: null };
      });
      document.getElementById('confirm-cancel')?.addEventListener('click', () => {
        pending = { itemId: null, score: null, container: null };
      });

      async function submitRating(container, score, confirmed=false){
        if (!isLogged) { if (window.M && M.toast) M.toast({html: 'Bitte zuerst anmelden.'}); return; }

        // guard against double clicks while posting
        if (container.dataset.posting === '1') return;
        container.dataset.posting = '1';
        container.querySelectorAll('.star-btn').forEach(b => b.setAttribute('disabled','disabled'));

        const itemId = parseInt(container.dataset.itemId, 10);
        const csrf = container.dataset.csrf;
        const ta = document.querySelector('.comment-input[data-item="' + itemId + '"]');
        const rawComment = ta ? (ta.value || '').trim() : '';
        const hasExisting = (parseInt(container.dataset.myScore || '0', 10) > 0);

        if (hasExisting && !confirmed) {
          openConfirmModal(itemId, score, container);
          container.dataset.posting = '0';
          container.querySelectorAll('.star-btn').forEach(b => b.removeAttribute('disabled'));
          return;
        }

        const clearCb = document.getElementById('confirm-clear-comment');
        const clearComment = !!(clearCb && clearCb.checked);
        const comment = clearComment ? null : (rawComment.length > 0 ? rawComment : null);

        try{
          const res = await fetch(`${base}/items/${itemId}/rate`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({score, csrf, comment, clearComment})
          });

          const data = await res.json();
          if (!data.ok) { throw new Error(data.message || 'Fehler'); }

          fillStars(container, score);
          container.dataset.myScore = String(score);

          const avg = document.querySelector(`.avg[data-item="${itemId}"]`);
          const cnt = document.querySelector(`.count[data-item="${itemId}"]`);
          if (avg) avg.textContent = (Number(data.avg)).toFixed(2).replace('.', ',');
          if (cnt) cnt.textContent = String(data.count);

          if (data.next_csrf) { container.dataset.csrf = data.next_csrf; }

          const badge = document.querySelector('.my-score-badge[data-item="'+itemId+'"]');
          if (badge) {
            badge.style.display = 'inline-flex';
            const strong = badge.querySelector('strong');
            if (strong) strong.textContent = String(score);
          }

          const wrap = document.querySelector('.comments-wrap[data-item="'+itemId+'"]');
          if (wrap) {
            if (clearComment) {
              const ul = wrap.querySelector('.comments-list');
              if (ul && currentUserId !== null) {
                const exist = ul.querySelector(`li.collection-item[data-user-id="${currentUserId}"]`);
                if (exist) exist.remove();
              }
            } else if (comment) {
              if (getComputedStyle(wrap).display === 'none') wrap.style.display = 'block';
              let ul = wrap.querySelector('.comments-list');
              if (!ul) {
                ul = document.createElement('ul');
                ul.className = 'collection comments-list';
                ul.style.border = 'none';
                wrap.appendChild(ul);
              }
              upsertOwnComment(ul, score, comment);
            }
          }

          if (ta && comment) { ta.value = ''; if (window.M && M.updateTextFields) M.updateTextFields(); }

          if (window.M && M.toast) M.toast({html: data.message});
        } catch(e){
          if (window.M && M.toast) M.toast({html: e.message});
        } finally {
          container.dataset.posting = '0';
          container.querySelectorAll('.star-btn').forEach(b => b.removeAttribute('disabled'));
        }
      }

      // Toggle comment block visibility
      document.querySelectorAll('.comment-toggle').forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const id = link.getAttribute('data-target');
          const block = document.getElementById('comment-' + id);
          if (!block) return;
          const isHidden = getComputedStyle(block).display === 'none';
          block.style.display = isHidden ? 'block' : 'none';
          if (isHidden) {
            if (window.M && M.updateTextFields) M.updateTextFields();
            const ta = block.querySelector('textarea.materialize-textarea');
            if (ta && window.M && M.textareaAutoResize) M.textareaAutoResize(ta);
          }
        });
      });

      // Clear comment button
      document.querySelectorAll('.clear-comment').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-item');
          const ta = document.querySelector('.comment-input[data-item="' + id + '"]');
          if (ta) {
            ta.value = '';
            if (window.M && M.updateTextFields) M.updateTextFields();
            if (window.M && M.toast) M.toast({html: 'Kommentar geleert (wird nur entfernt, wenn du „Kommentar löschen“ bestätigst).'});
          }
        });
      });

      // Bind rating stars
      document.querySelectorAll('.rating').forEach(container => {
        const my = parseInt(container.dataset.myScore || '0', 10);
        if (my > 0) fillStars(container, my);

        container.querySelectorAll('.star-btn').forEach(btn => {
          btn.addEventListener('click', () => {
            const score = parseInt(btn.dataset.score, 10);
            // store pending for modal case
            pending = { itemId: parseInt(container.dataset.itemId, 10), score, container };
            submitRating(container, score, false);
          });
        });
      });
    })();
  </script>
<?php endif; ?>
