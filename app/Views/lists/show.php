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
        <label for="name">Name / Title</label>
      </div>
      <div class="input-field">
        <input id="description" name="description" type="url" maxlength="2048" placeholder="https://example.com (optional)">
        <label for="description">URL (optional)</label>
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
          <th>Link</th>
          <th>Ø</th>
          <th>Anz.</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if (!empty($it['description'])): ?>
              <a href="<?= htmlspecialchars($it['description'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Öffnen</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td><?= number_format((float)$it['avg'], 2, ',', '') ?></td>
          <td><?= (int)$it['count'] ?></td>
        </tr>
        <?php if (!empty($commentsByItem[(int)$it['id']])): ?>
          <tr>
            <td colspan="4">
              <strong>Letzte Kommentare:</strong>
              <ul style="margin-top:6px;">
                <?php foreach ($commentsByItem[(int)$it['id']] as $c): ?>
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
