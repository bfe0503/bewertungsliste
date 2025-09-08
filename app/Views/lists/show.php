<?php
/** @var \App\Models\UserList|null $list */
/** @var int|null $listId */
/** @var array<int, array{id:int,name:string,description:?string,avg:float,count:int,my_score:?int}> $items */
/** @var bool $canAdd */
/** @var string $createItemToken */
/** @var array<int,string> $rateTokens */
/** @var array<int, array<int, array{user:string,score:int,comment:string,created_at:string}>> $commentsByItem */

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
          <div class="col s12 m6">
            <div class="card">
              <div class="card-content">
                <span class="card-title"><?= htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($it['description']): ?>
                  <p><?= nl2br(htmlspecialchars($it['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>

                <!-- Rating block -->
                <div class="mt-3">
                  <div class="rating"
                       data-item-id="<?= (int)$it['id'] ?>"
                       data-csrf="<?= htmlspecialchars($rateTokens[$it['id']] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       data-my-score="<?= $it['my_score'] !== null ? (int)$it['my_score'] : 0 ?>">
                    <?php for ($s=1; $s<=5; $s++): ?>
                      <button type="button" class="star-btn" data-score="<?= $s ?>" aria-label="Bewerten mit <?= $s ?> Stern(en)">
                        <i class="material-icons star-icon"><?= ($it['my_score'] !== null && $it['my_score'] >= $s) ? 'star' : 'star_border' ?></i>
                      </button>
                    <?php endfor; ?>
                  </div>
                  <div class="text-muted mt-2">
                    Ø <span class="avg" data-item="<?= (int)$it['id'] ?>"><?= number_format($it['avg'], 2, ',', '') ?></span>
                    · <span class="count" data-item="<?= (int)$it['id'] ?>"><?= (int)$it['count'] ?></span> Bewertungen
                  </div>
                </div>

                <!-- Comment toggle + field (optional) -->
                <div class="mt-3">
                  <a href="#!" class="comment-toggle" data-target="<?= (int)$it['id'] ?>">
                    <i class="material-icons left">chat</i>Kommentar hinzufügen/ändern (optional)
                  </a>
                  <div class="comment-block" id="comment-<?= (int)$it['id'] ?>" style="display:none;">
                    <div class="input-field" style="margin-top:16px;">
                      <textarea class="materialize-textarea comment-input" maxlength="2000" data-item="<?= (int)$it['id'] ?>"></textarea>
                      <label>Kommentar (max. 2000 Zeichen)</label>
                    </div>
                    <div class="right-align">
                      <button type="button" class="btn-flat clear-comment" data-item="<?= (int)$it['id'] ?>">
                        Leeren
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Latest comments list -->
                <?php $cl = $commentsByItem[$it['id']] ?? []; if (!empty($cl)): ?>
                  <div class="mt-3">
                    <span class="grey-text text-darken-1">Letzte Kommentare:</span>
                    <ul class="collection" style="border:none;">
                      <?php foreach ($cl as $c): ?>
                        <li class="collection-item" style="border:0;border-bottom:1px solid rgba(0,0,0,.06);">
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
                          <div style="margin-top:6px;">
                            <?= nl2br(htmlspecialchars($c['comment'], ENT_QUOTES, 'UTF-8')) ?>
                          </div>
                          <div class="grey-text" style="margin-top:4px;font-size:.9rem;">
                            <?= htmlspecialchars(date('d.m.Y H:i', strtotime($c['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                          </div>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>

              </div> <!-- /card-content -->
            </div> <!-- /card -->
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    (function(){
      const base = '<?= $base ?>';
      const isLogged = <?= \App\Core\Auth::check() ? 'true' : 'false' ?>;

      function fillStars(container, score){
        const icons = container.querySelectorAll('.star-icon');
        icons.forEach((ic, idx) => {
          ic.textContent = (idx + 1) <= score ? 'star' : 'star_border';
        });
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
            if (window.M && M.toast) M.toast({html: 'Kommentar geleert.'});
          }
        });
      });

      // Bind rating stars
      document.querySelectorAll('.rating').forEach(container => {
        const my = parseInt(container.dataset.myScore || '0', 10);
        if (my > 0) fillStars(container, my);

        container.querySelectorAll('.star-btn').forEach(btn => {
          btn.addEventListener('click', async () => {
            if (!isLogged) { if (window.M && M.toast) M.toast({html: 'Bitte zuerst anmelden.'}); return; }

            const score = parseInt(btn.dataset.score, 10);
            const itemId = parseInt(container.dataset.itemId, 10);
            const csrf = container.dataset.csrf;

            const ta = document.querySelector('.comment-input[data-item="' + itemId + '"]');
            const rawComment = ta ? (ta.value || '').trim() : '';
            const comment = rawComment.length > 0 ? rawComment : null;

            if (comment && comment.length > 2000) {
              if (window.M && M.toast) M.toast({html: 'Kommentar zu lang (max. 2000 Zeichen).'});
              return;
            }

            try{
              const res = await fetch(`${base}/items/${itemId}/rate`, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({score, csrf, comment})
              });

              const data = await res.json();
              if (!data.ok) { throw new Error(data.message || 'Fehler'); }

              fillStars(container, score);
              const avg = document.querySelector(`.avg[data-item="${itemId}"]`);
              const cnt = document.querySelector(`.count[data-item="${itemId}"]`);
              if (avg) avg.textContent = (Number(data.avg)).toFixed(2).replace('.', ',');
              if (cnt) cnt.textContent = String(data.count);

              if (ta) { ta.value = ''; if (window.M && M.updateTextFields) M.updateTextFields(); }

              if (window.M && M.toast) M.toast({html: data.message});
              // Note: comments list is static for now (updated on page reload)
            } catch(e){
              if (window.M && M.toast) M.toast({html: e.message});
            }
          });
        });
      });
    })();
  </script>
<?php endif; ?>
