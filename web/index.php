<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';
require_login_page();

$currentId = isset($_GET['search']) ? (int)$_GET['search'] : null;
$search = $currentId !== null ? search_get($DB, $currentId) : null;

echo render_page_start('Tematy transportowe', true);
?>
<section class="card">
  <h1>Nowe wyszukiwanie</h1>
  <form id="search-form">
    <label>Hasło (opcjonalnie) <input name="query" maxlength="200" placeholder="np. e-CMR, KSeF dla przewoźników — puste = 6 obszarów"></label>
    <label>Liczba dni <input name="days" type="number" min="1" max="365" value="14" required></label>
    <button type="submit">Szukaj</button>
  </form>
  <p id="progress" class="status" hidden></p>
</section>

<?php if ($search !== null): ?>
<section class="card">
  <h2>Wyszukiwanie z <?= e($search['created_at']) ?>: <?= $search['query'] === null ? '6 obszarów' : '„' . e($search['query']) . '”' ?>, <?= (int)$search['days'] ?> dni</h2>
  <?= render_steps(steps_summary($DB, (int)$search['id']), $search) ?>
  <?php if ($search['status'] === 'done'): ?>
    <?= render_topics(topics_for($DB, (int)$search['id'])) ?>
    <?= render_extras($search) ?>
    <p class="muted"><em>Materiał roboczy wygenerowany automatycznie. Przed publikacją zweryfikuj treść przepisów w źródłach.</em></p>
  <?php endif; ?>
</section>
<?php elseif ($currentId !== null): ?>
<p class="error">Nie znaleziono wyszukiwania.</p>
<?php endif; ?>

<section class="card">
  <h2>Historia</h2>
  <?= render_history(search_list($DB), $currentId) ?>
</section>
<?= render_post_template() ?>
<?php
echo render_page_end();
