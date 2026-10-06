<?php
declare(strict_types=1);

function session_start_secure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('tsid');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(mixed $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function attempt_login(PDO $db, array $cfg, string $user, string $pass, string $ip, int $now): ?string
{
    if (login_is_locked($db, $ip, $now)) {
        return 'Zbyt wiele nieudanych prób. Spróbuj ponownie za 15 minut.';
    }
    $hash = (string)($cfg['password_hash'] ?? '');
    if ($hash !== '' && hash_equals((string)$cfg['login'], $user) && password_verify($pass, $hash)) {
        login_reset($db, $ip);
        return null;
    }
    login_register_failure($db, $ip, $now);
    return 'Nieprawidłowy login lub hasło.';
}
