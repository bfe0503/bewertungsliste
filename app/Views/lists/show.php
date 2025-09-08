<?php
/** @var \App\Models\UserList|null $list */
/** @var int|null $listId */
/** @var array<int, array{id:int,name:string,description:?string,avg:float,count:int,my_score:?int}> $items */
/** @var bool $canAdd */
/** @var string $createItemToken */
/** @var array<int,string> $rateTokens */
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
              </div>
            </div>
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

      document.querySelectorAll('.rating').forEach(container => {
        const my = parseInt(container.dataset.myScore || '0', 10);
        if (my > 0) fillStars(container, my);

        container.querySelectorAll('.star-btn').forEach(btn => {
          btn.addEventListener('click', async () => {
            if (!isLogged) { M.toast({html: 'Bitte zuerst anmelden.'}); return; }
            const score = parseInt(btn.dataset.score, 10);
            const itemId = parseInt(container.dataset.itemId, 10);
            const csrf = container.dataset.csrf;

            try{
              const res = await fetch(`${base}/items/${itemId}/rate`, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({score, csrf})
              });
              const data = await res.json();
              if (!data.ok) { throw new Error(data.message || 'Fehler'); }

              // Update stars + stats
              fillStars(container, score);
              const avg = document.querySelector(`.avg[data-item="${itemId}"]`);
              const cnt = document.querySelector(`.count[data-item="${itemId}"]`);
              if (avg) avg.textContent = (Number(data.avg)).toFixed(2).replace('.', ',');
              if (cnt) cnt.textContent = String(data.count);
              M.toast({html: data.message});
            } catch(e){
              M.toast({html: e.message});
            }
          });
        });
      });
    })();
  </script>
<?php endif; ?>
