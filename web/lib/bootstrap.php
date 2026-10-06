<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');
define('APP_LOG', DATA_DIR . '/app.log');

date_default_timezone_set('Europe/Warsaw');

if (!is_file(APP_ROOT . '/config.php')) {
    http_response_code(500);
    exit('Brak pliku config.php. Skopiuj config.example.php do config.php i uzupełnij.');
}
$CONFIG = require APP_ROOT . '/config.php';

foreach (['util', 'validate', 'db', 'claude', 'prompts', 'search_runner', 'post', 'svg', 'image', 'auth', 'http', 'view'] as $lib) {
    require_once __DIR__ . "/$lib.php";
}

if (!is_writable(DATA_DIR)) {
    http_response_code(500);
    exit('Katalog data/ nie ma prawa zapisu.');
}
$DB = db_connect(DATA_DIR . '/app.sqlite');
ensure_sources_file(DATA_DIR . '/zrodla.md', APP_ROOT . '/zrodla.default.md');
session_start_secure();

function app_claude(array $cfg): ClaudeClient
{
    return new ClaudeClient((string)$cfg['anthropic_api_key'], (string)$cfg['model'], curl_transport((int)$cfg['api_timeout']));
}

function logo_data_uri(): ?string
{
    $f = DATA_DIR . '/logo.png';
    return is_file($f) ? 'data:image/png;base64,' . base64_encode((string)file_get_contents($f)) : null;
}

function require_login_page(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
