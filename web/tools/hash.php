<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
$password = $argv[1] ?? null;
if ($password === null) {
    fwrite(STDERR, 'Hasło: ');
    $password = trim((string)fgets(STDIN));
}
if ($password === '') {
    fwrite(STDERR, "Hasło nie może być puste.\n");
    exit(1);
}
echo password_hash($password, PASSWORD_DEFAULT), "\n";
