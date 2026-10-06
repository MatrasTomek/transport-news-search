<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Formularz wygasł. Spróbuj ponownie.';
    } else {
        $error = attempt_login($DB, $CONFIG, (string)($_POST['login'] ?? ''), (string)($_POST['password'] ?? ''),
            (string)($_SERVER['REMOTE_ADDR'] ?? ''), time());
        if ($error === null) {
            session_regenerate_id(true);
            $_SESSION['user'] = $CONFIG['login'];
            unset($_SESSION['csrf']);
            header('Location: index.php');
            exit;
        }
    }
}
echo render_page_start('Logowanie', false);
?>
<section class="login">
  <h1>Logowanie</h1>
  <?php if ($error !== null): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Login <input name="login" autocomplete="username" required autofocus></label>
    <label>Hasło <input name="password" type="password" autocomplete="current-password" required></label>
    <button type="submit">Zaloguj</button>
  </form>
</section>
<?php
echo render_page_end();
