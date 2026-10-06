<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/view.php';

test('attempt_login: sukces, błąd, blokada', function () {
    $db = db_connect(':memory:');
    $cfg = ['login' => 'admin', 'password_hash' => password_hash('tajne', PASSWORD_DEFAULT)];
    assert_same(null, attempt_login($db, $cfg, 'admin', 'tajne', '1.1.1.1', 1000));
    assert_same('Nieprawidłowy login lub hasło.', attempt_login($db, $cfg, 'admin', 'złe', '1.1.1.1', 1000));
    for ($i = 0; $i < 4; $i++) {
        attempt_login($db, $cfg, 'x', 'y', '1.1.1.1', 1000);
    }
    assert_same('Zbyt wiele nieudanych prób. Spróbuj ponownie za 15 minut.', attempt_login($db, $cfg, 'admin', 'tajne', '1.1.1.1', 1000));
    assert_same(null, attempt_login($db, $cfg, 'admin', 'tajne', '1.1.1.1', 1900));
});

test('attempt_login: pusty hash nigdy nie wpuszcza', function () {
    $db = db_connect(':memory:');
    assert_same('Nieprawidłowy login lub hasło.', attempt_login($db, ['login' => 'admin', 'password_hash' => ''], 'admin', '', '1.1.1.1', 1000));
});

test('csrf_valid', function () {
    $_SESSION = ['csrf' => 'abc'];
    assert_same(true, csrf_valid('abc'));
    assert_same(false, csrf_valid('abd'));
    assert_same(false, csrf_valid(null));
    $_SESSION = [];
    assert_same(false, csrf_valid(''));
});

test('render_topics escapuje treść i pomija niebezpieczne linki', function () {
    $html = render_topics([[
        'id' => 7, 'nr' => 1, 'title' => '<script>alert(1)</script>', 'area' => 'A&B', 'status' => 'projekt',
        'effective_date' => null, 'urgency' => '⚪ projekt', 'urgency_code' => 'white', 'summary' => '"cudzysłów"',
        'sources' => [
            ['url' => 'https://gov.pl/x?a=1&b=2', 'title' => '<b>Gov</b>', 'type' => 'oficjalne', 'date' => '2026-10-02'],
            ['url' => 'javascript:alert(1)', 'title' => 'zły', 'type' => 'oficjalne', 'date' => null],
        ],
    ]]);
    assert_true(!str_contains($html, '<script>'));
    assert_true(str_contains($html, '&lt;script&gt;'));
    assert_true(str_contains($html, 'href="https://gov.pl/x?a=1&amp;b=2"'));
    assert_true(!str_contains($html, 'javascript:'));
    assert_true(str_contains($html, '02.10.2026'));
    assert_true(str_contains($html, 'data-write-post="7"'));
    assert_true(str_contains($html, 'id="post-row-7"'));
});

test('render_topics: pusta lista', function () {
    assert_true(str_contains(render_topics([]), 'Brak tematów'));
});

test('render_steps: Ponów przy błędzie, Wznów przy running', function () {
    $steps = [
        ['id' => 1, 'label' => 'Prawo transportowe', 'kind' => 'area', 'status' => 'error', 'error' => 'Timeout <x>'],
        ['id' => 2, 'label' => 'Wybór tematów', 'kind' => 'synthesis', 'status' => 'pending', 'error' => null],
    ];
    $html = render_steps($steps, ['id' => 5, 'status' => 'running']);
    assert_true(str_contains($html, 'data-retry-step="1"'));
    assert_true(str_contains($html, 'data-resume="5"'));
    assert_true(str_contains($html, 'Timeout &lt;x&gt;'));
    assert_true(!str_contains(render_steps($steps, ['id' => 5, 'status' => 'done']), 'data-resume'));
});

test('render_extras i render_history', function () {
    $html = render_extras(['note' => 'Okres ograniczono do 365 dni.', 'extras_json' => json_encode([
        'watch' => [['text' => 'Projekt <PIT>', 'url' => 'https://legislacja.gov.pl/x', 'unverified' => true]],
        'no_news' => ['ZUS i składki: brak istotnych zmian w okresie.'],
    ])]);
    assert_true(str_contains($html, 'Projekt &lt;PIT&gt;'));
    assert_true(str_contains($html, '(niezweryfikowane)'));
    assert_true(str_contains($html, 'Okres ograniczono'));
    $h = render_history([['id' => 3, 'created_at' => '2026-10-06 10:00', 'query' => null, 'days' => 14, 'status' => 'done', 'topic_count' => 6]], 3);
    assert_true(str_contains($h, '6 obszarów'));
    assert_true(str_contains($h, 'href="index.php?search=3"'));
});

test('api_guard zwalnia blokadę sesji przed długim wywołaniem', function () {
    $lib = realpath(__DIR__ . '/../lib');
    $code = "<?php foreach (['util', 'db', 'auth', 'http'] as \$l) { require '$lib/' . \$l . '.php'; }\n"
        . "ini_set('session.use_cookies', '0'); ini_set('session.cache_limiter', ''); ini_set('session.save_path', sys_get_temp_dir());\n"
        . "session_start(); \$_SESSION['user'] = 'admin'; \$_SESSION['csrf'] = 'tok';\n"
        . "\$_SERVER['REQUEST_METHOD'] = 'POST'; \$_SERVER['HTTP_X_CSRF_TOKEN'] = 'tok';\n"
        . "api_guard(); echo session_status() === PHP_SESSION_NONE ? 'closed' : 'open';";
    $f = tempnam(sys_get_temp_dir(), 'ag');
    file_put_contents($f, $code);
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($f) . ' 2>&1');
    unlink($f);
    assert_same('closed', trim((string)$out));
});
