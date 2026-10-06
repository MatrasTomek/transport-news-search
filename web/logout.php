<?php
declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && csrf_valid($_POST['csrf'] ?? null)) {
    $_SESSION = [];
    session_destroy();
}
header('Location: login.php');
exit;
