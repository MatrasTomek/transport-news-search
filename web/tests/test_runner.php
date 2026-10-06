<?php
declare(strict_types=1);

require_once __DIR__ . '/fakes.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/validate.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/prompts.php';
require_once __DIR__ . '/../lib/search_runner.php';

const TEST_CFG = [
    'company_name' => 'MAWEX', 'blog_url' => 'https://mawex-biuro.pl/blog',
    'web_search_tool' => 'web_search_20250305', 'web_fetch_tool' => 'web_fetch_20250910',
    'contact_cta' => 'Masz pytania? Skontaktuj się z biurem MAWEX.',
    'brand_colors' => ['primary' => '#1f4e79', 'accent' => '#f2a900', 'background' => '#ffffff', 'text' => '#1a1a1a'],
];

function candidate_json(string $title = 'Tańszy diesel', string $date = '2026-10-03'): string
{
    return json_encode(['candidates' => [[
        'title' => $title, 'area' => 'Księgowość i podatki w transporcie', 'status' => 'obowiązuje',
        'effective_date' => $date, 'summary' => 'Obniżka.',
        'sources' => [['url' => 'https://www.gov.pl/a', 'title' => 'Gov', 'type' => 'oficjalne', 'date' => '2026-10-02']],
    ]], 'watch' => [['text' => 'Projekt PIT', 'url' => null, 'unverified' => true]]], JSON_UNESCAPED_UNICODE);
}

function runner_setup(?string $query = null): array
{
    $db = db_connect(':memory:');
    $id = search_create($db, $query, 14, null, build_steps($query), 1000);
    return [$db, search_get($db, $id)];
}

function run_next(PDO $db, array $search, ClaudeClient $c): array
{
    $step = step_next($db, (int)$search['id']);
    return run_step($db, $c, search_get($db, (int)$search['id']), $step, TEST_CFG, new DateTimeImmutable('2026-10-06'), "- GITD — gov.pl/web/gitd", 2000);
}

test('build_steps: obszary i hasło', function () {
    $s = build_steps(null);
    assert_same(7, count($s));
    assert_same(['label' => 'Prawo transportowe', 'kind' => 'area'], $s[0]);
    assert_same(['label' => 'Wybór tematów', 'kind' => 'synthesis'], $s[6]);
    assert_same([['label' => 'e-CMR', 'kind' => 'query'], ['label' => 'Wybór tematów', 'kind' => 'synthesis']], build_steps('e-CMR'));
});

test('step_is_busy: świeży running, stary running, inne statusy', function () {
    assert_same(true, step_is_busy(['status' => 'running', 'updated_at' => 1000], 1100, 150));
    assert_same(false, step_is_busy(['status' => 'running', 'updated_at' => 1000], 1211, 150));
    assert_same(false, step_is_busy(['status' => 'paused', 'updated_at' => 1000], 1001, 150));
});

test('research: pause_turn zapisuje stan, kontynuacja kończy krok', function () {
    [$db, $search] = runner_setup('e-CMR');
    $req = [];
    $c = fake_claude([
        fake_response([text_block('Szukam.'), ['type' => 'server_tool_use', 'id' => 's1', 'name' => 'web_fetch', 'input' => new stdClass()]], 'pause_turn'),
        fake_response([text_block(candidate_json())]),
    ], $req);
    assert_same('paused', run_next($db, $search, $c)['state']);
    $step = steps_for($db, (int)$search['id'])[0];
    assert_same('paused', $step['status']);
    assert_true(str_contains($step['state_json'], '"input":{}'));
    assert_same('done', run_next($db, $search, $c)['state']);
    $payload = $req[1]['payload'];
    assert_same('assistant', end($payload['messages'])['role']);
    assert_true(str_contains($req[1]['body'], '"input":{}'));
    assert_same('web_search', $payload['tools'][0]['name']);
    assert_same(5, $payload['tools'][0]['max_uses']);
    assert_same(4, $payload['tools'][1]['max_uses']);
    $done = steps_for($db, (int)$search['id'])[0];
    assert_same(null, $done['state_json']);
    assert_same('Tańszy diesel', json_decode($done['result_json'], true)['candidates'][0]['title']);
});

test('research: prompt zawiera okres, źródła i hasło', function () {
    [$db, $search] = runner_setup('e-CMR');
    $req = [];
    run_next($db, $search, fake_claude([fake_response([text_block(candidate_json())])], $req));
    $p = $req[0]['payload'];
    assert_true(str_contains($p['system'], '22.09.2026'), 'data początkowa');
    assert_true(str_contains($p['system'], 'gov.pl/web/gitd'), 'źródła');
    assert_true(str_contains($p['messages'][0]['content'], '„e-CMR”'), 'hasło');
});

test('research: niepoprawny JSON → prośba o poprawkę, potem sukces', function () {
    [$db, $search] = runner_setup('e-CMR');
    $req = [];
    $c = fake_claude([fake_response([text_block('Nie znalazłem nic ciekawego.')]), fake_response([text_block(candidate_json())])], $req);
    assert_same('paused', run_next($db, $search, $c)['state']);
    assert_same('done', run_next($db, $search, $c)['state']);
    $last = end($req[1]['payload']['messages']);
    assert_same('user', $last['role']);
    assert_true(str_contains($last['content'], 'JSON'));
});

test('research: dwa razy zły JSON → błąd kroku', function () {
    [$db, $search] = runner_setup('e-CMR');
    $req = [];
    $c = fake_claude([fake_response([text_block('x')]), fake_response([text_block('y')])], $req);
    run_next($db, $search, $c);
    $r = run_next($db, $search, $c);
    assert_same('error', $r['state']);
    assert_same('error', steps_for($db, (int)$search['id'])[0]['status']);
    assert_same('running', search_get($db, (int)$search['id'])['status']);
});

test('research: błąd API → krok error z komunikatem', function () {
    [$db, $search] = runner_setup('e-CMR');
    $req = [];
    $c = fake_claude([['status' => 400, 'headers' => [], 'body' => '{"error":{"message":"bad"}}']], $req);
    $r = run_next($db, $search, $c);
    assert_same(['state' => 'error', 'message' => 'Błąd API (400): bad'], $r);
});

test('synteza: tematy z pilnością, błąd obszaru w no_news, status done', function () {
    [$db, $search] = runner_setup(null);
    $steps = steps_for($db, (int)$search['id']);
    foreach ($steps as $i => $s) {
        if ($s['kind'] === 'area') {
            $result = $i === 1 ? null : json_encode(validate_candidates(json_decode(candidate_json("Temat $i"), true)), JSON_UNESCAPED_UNICODE);
            step_update($db, (int)$s['id'], $result === null ? ['status' => 'error', 'error' => 'x'] : ['status' => 'done', 'result_json' => $result], 1500);
        }
    }
    $req = [];
    $synth = json_encode(['topics' => [json_decode(candidate_json('Temat 0'), true)['candidates'][0]],
        'watch' => [], 'no_news' => ['ZUS i składki: brak istotnych zmian w okresie.']], JSON_UNESCAPED_UNICODE);
    $r = run_next($db, $search, fake_claude([fake_response([text_block($synth)])], $req));
    assert_same('done', $r['state']);
    assert_same(false, isset($req[0]['payload']['tools']));
    assert_true(str_contains($req[0]['payload']['messages'][0]['content'], 'Temat 2'));
    $topics = topics_for($db, (int)$search['id']);
    assert_same('🔴 weszło w życie 03.10.2026', $topics[0]['urgency']);
    assert_same('red', $topics[0]['urgency_code']);
    $s = search_get($db, (int)$search['id']);
    assert_same('done', $s['status']);
    $extras = json_decode($s['extras_json'], true);
    assert_true(in_array('Czas pracy kierowców (błąd wyszukiwania)', $extras['no_news'], true));
});

test('synteza: brak kandydatów → bez wywołania API', function () {
    [$db, $search] = runner_setup('e-CMR');
    $first = steps_for($db, (int)$search['id'])[0];
    step_update($db, (int)$first['id'], ['status' => 'done', 'result_json' => '{"candidates":[],"watch":[]}'], 1500);
    $req = [];
    assert_same('done', run_next($db, $search, fake_claude([], $req))['state']);
    assert_same(0, count($req));
    $extras = json_decode(search_get($db, (int)$search['id'])['extras_json'], true);
    assert_same(['e-CMR: brak istotnych zmian w okresie.'], $extras['no_news']);
});

test('synteza: wszystkie kroki z błędem → wyszukiwanie error', function () {
    [$db, $search] = runner_setup('e-CMR');
    $first = steps_for($db, (int)$search['id'])[0];
    step_update($db, (int)$first['id'], ['status' => 'error', 'error' => 'x'], 1500);
    $req = [];
    $r = run_next($db, $search, fake_claude([], $req));
    assert_same('error', $r['state']);
    assert_same('error', search_get($db, (int)$search['id'])['status']);
});
