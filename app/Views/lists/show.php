<?php
/**
 * Variables:
 * @var int|null $listId
 * @var \App\Models\UserList|null $list
 * @var array<int, array{id:int,name:string,description:?string,avg:float,count:int,my_score:?int}> $items
 * @var bool $canAdd
 * @var string $createItemToken
 * @var array<int,string> $rateTokens
 * @var array<int, array<int, array{ user:?string, comment:?string, created_at:string }>> $commentsByItem
 * @var int|null $currentUserId
 *
 * Notes:
 * - We treat "description" from items array as legacy field; we no longer ask for URL here.
 * - Rating is handled via AJAX to /items/{id}/rate with JSON: {score, csrf, comment?, clearComment?}
 */
use App\Core\Csrf;

$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>

<?php if (!$list): ?>
  <div class="section">
    <h5>Liste nicht gefunden oder Zugriff verweigert</h5>
    <p>Die angeforderte Liste (ID: <?= (int)($listId ?? 0) ?>) ist nicht verfügbar.</p>
    <a class="btn" href="<?= $base ?>/lists">Zurück zu den Listen</a>
  </div>
  <?php return; ?>
<?php endif; ?>

<div class="section">
  <h4><?= htmlspecialchars($list->title, ENT_QUOTES, 'UTF-8') ?></h4>

  <?php if ($list->description !== null && $list->description !== ''): ?>
    <p><?= htmlspecialchars($list->description, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <p>
    Sichtbarkeit:
    <span class="badge <?= ((int)$list->is_public === 1) ? 'green' : 'grey' ?>">
      <?= ((int)$list->is_public === 1) ? 'Public' : 'Private' ?>
    </span>
  </p>
</div>

<?php if (!empty($canAdd)): ?>
  <div class="section">
    <h6>Eintrag hinzufügen</h6>
    <form method="post" action="<?= $base ?>/lists/<?= (int)$list->id ?>/items">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($createItemToken, ENT_QUOTES, 'UTF-8') ?>">
      <div class="input-field">
        <input id="name" name="name" type="text" required maxlength="255">
        <label for="name">Name</label>
      </div>
      <button class="btn waves-effect" type="submit">Hinzufügen</button>
    </form>
  </div>
<?php endif; ?>

<div class="section">
  <h6>Items</h6>
  <?php if (empty($items)): ?>
    <p>Noch keine Einträge.</p>
  <?php else: ?>
    <table class="striped">
      <thead>
        <tr>
          <th>Name</th>
          <th>Ø</th>
          <th>Anz.</th>
          <th>Bewerten</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <?php
          $iid = (int)$it['id'];
          $avg = (float)$it['avg'];
          $cnt = (int)$it['count'];
          $my  = $it['my_score'] !== null ? (int)$it['my_score'] : 0;
          $csrf = $rateTokens[$iid] ?? '';
        ?>
        <tr id="row-<?= $iid ?>">
          <td><?= htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><span id="avg-<?= $iid ?>"><?= number_format($avg, 2, ',', '') ?></span></td>
          <td><span id="cnt-<?= $iid ?>"><?= $cnt ?></span></td>
          <td>
            <div class="rating" data-item="<?= $iid ?>" data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <!-- Stars (1..5) -->
              <span class="stars" data-current="<?= $my ?>">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="star" data-score="<?= $s ?>" style="cursor:pointer;">★</i>
                <?php endfor; ?>
              </span>
              <small style="margin-left:6px;">Deine Bewertung: <span id="mine-<?= $iid ?>"><?= $my > 0 ? $my : '–' ?></span></small>

              <!-- Comment box -->
              <div class="input-field" style="margin-top:8px;">
                <textarea id="cmt-<?= $iid ?>" class="materialize-textarea" maxlength="2000" placeholder="Kommentar (optional)"></textarea>
                <label for="cmt-<?= $iid ?>" class="active">Kommentar (optional)</label>
              </div>

              <label style="display:inline-flex; align-items:center; gap:6px; font-size:0.9em;">
                <input type="checkbox" id="clr-<?= $iid ?>" />
                <span>Kommentar entfernen</span>
              </label>

              <div style="margin-top:8px;">
                <button type="button" class="btn-small rate-btn" data-item="<?= $iid ?>">Speichern</button>
                <span class="rate-msg" id="msg-<?= $iid ?>" style="margin-left:8px; color:#555;"></span>
              </div>
            </div>
          </td>
        </tr>

        <?php if (!empty($commentsByItem[$iid])): ?>
          <tr>
            <td colspan="4">
              <strong>Letzte Kommentare:</strong>
              <ul style="margin-top:6px;">
                <?php foreach ($commentsByItem[$iid] as $c): ?>
                  <li>
                    <em><?= htmlspecialchars($c['user'] ?? 'anonymous', ENT_QUOTES, 'UTF-8') ?></em>:
                    <?= htmlspecialchars($c['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    <small style="color:#666;">(<?= htmlspecialchars($c['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</small>
                  </li>
                <?php endforeach; ?>
              </ul>
            </td>
          </tr>
        <?php endif; ?>

      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<style>
/* Minimal star styling (no external libs) */
.stars .star {
  font-style: normal;
  font-size: 18px;
  opacity: 0.35;
  padding: 0 2px;
}
.stars .star.active {
  opacity: 1;
}
</style>

<script>
/**
 * Minimal rating client:
 * - Click stars to set score (1..5)
 * - Click "Speichern" to POST JSON to /items/{id}/rate
 * - Updates avg/count/my score and replaces CSRF token from server
 * - If "Kommentar entfernen" is checked, sets clearComment = true and ignores textarea
 */
(function() {
  // Initialize stars with current selection
  document.querySelectorAll('.rating .stars').forEach(function(starWrap) {
    var current = parseInt(starWrap.getAttribute('data-current') || '0', 10);
    setStarVisual(starWrap, current);
  });

  function setStarVisual(starWrap, n) {
    starWrap.querySelectorAll('.star').forEach(function(star) {
      var s = parseInt(star.getAttribute('data-score') || '0', 10);
      star.classList.toggle('active', s <= n && n > 0);
    });
    starWrap.setAttribute('data-current', String(n));
  }

  // Click on star -> set selected value
  document.querySelectorAll('.rating .star').forEach(function(el) {
    el.addEventListener('click', function(ev) {
      var star = ev.currentTarget;
      var wrap = star.closest('.rating').querySelector('.stars');
      var score = parseInt(star.getAttribute('data-score') || '0', 10);
      setStarVisual(wrap, score);
    });
  });

  // Save button -> AJAX POST
  document.querySelectorAll('.rating .rate-btn').forEach(function(btn) {
    btn.addEventListener('click', async function(ev) {
      var itemId = parseInt(btn.getAttribute('data-item') || '0', 10);
      var ratingRoot = btn.closest('.rating');
      var stars = ratingRoot.querySelector('.stars');
      var score = parseInt(stars.getAttribute('data-current') || '0', 10);
      var csrf = ratingRoot.getAttribute('data-csrf') || '';
      var msg = document.getElementById('msg-' + itemId);
      var mine = document.getElementById('mine-' + itemId);
      var avgEl = document.getElementById('avg-' + itemId);
      var cntEl = document.getElementById('cnt-' + itemId);

      var clear = document.getElementById('clr-' + itemId).checked;
      var comment = document.getElementById('cmt-' + itemId).value;

      // If clearComment checked, ignore comment content
      var payload = {
        score: score,
        csrf: csrf,
        clearComment: !!clear
      };
      if (!clear) {
        payload.comment = comment;
      }

      try {
        var res = await fetch('<?= $base ?>/items/' + itemId + '/rate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
          credentials: 'same-origin'
        });
        var data = await res.json();

        if (data && data.ok) {
          // Update UI from server response
          if (typeof data.avg !== 'undefined')  avgEl.textContent = Number(data.avg).toFixed(2).replace('.', ',');
          if (typeof data.count !== 'undefined') cntEl.textContent = String(data.count);
          if (typeof data.score !== 'undefined') mine.textContent = String(data.score);

          // Replace CSRF for next rating cycle
          if (data.next_csrf) {
            ratingRoot.setAttribute('data-csrf', data.next_csrf);
          }

          // Reset clear checkbox (optional)
          document.getElementById('clr-' + itemId).checked = false;

          msg.textContent = data.message || 'Gespeichert.';
          msg.style.color = '#2e7d32';
        } else {
          msg.textContent = (data && data.message) ? data.message : 'Fehler.';
          msg.style.color = '#c62828';
        }
      } catch (e) {
        msg.textContent = 'Netzwerk-/Serverfehler.';
        msg.style.color = '#c62828';
      }
    });
  });
})();
</script>
