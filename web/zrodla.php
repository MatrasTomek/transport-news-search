<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';
require_login_page();

$file = DATA_DIR . '/zrodla.md';
$message = null;
$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $content = str_replace("\r\n", "\n", (string)($_POST['content'] ?? ''));
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.';
    } elseif (strlen($content) > 100000) {
        $error = 'Lista źródeł jest za długa (maks. 100 000 znaków).';
    } elseif (file_put_contents($file, $content, LOCK_EX) === false) {
        $error = 'Nie udało się zapisać pliku. Sprawdź prawa zapisu katalogu data/.';
    } else {
        $message = 'Zapisano. Nowa lista będzie użyta przy kolejnym wyszukiwaniu.';
    }
}
echo render_page_start('Źródła', true);
?>
<h1>Zaufane źródła</h1>
<p class="muted">Claude zaczyna wyszukiwanie od tych źródeł, potem szuka szerzej. Format pozycji: <code>- Nazwa — domena — uwaga</code>.</p>
<?php if ($message !== null): ?><p class="ok"><?= e($message) ?></p><?php endif; ?>
<?php if ($error !== null): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <textarea name="content" rows="30" class="mono"><?= e((string)@file_get_contents($file)) ?></textarea>
  <button type="submit">Zapisz</button>
</form>
<?php
echo render_page_end();
