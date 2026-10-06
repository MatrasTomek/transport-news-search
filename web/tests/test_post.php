<?php
declare(strict_types=1);

require_once __DIR__ . '/fakes.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/prompts.php';
require_once __DIR__ . '/../lib/post.php';

function post_topic(): array
{
    return ['id' => 1, 'title' => 'Tańszy diesel', 'area' => 'Księgowość i podatki w transporcie', 'status' => 'obowiązuje',
        'effective_date' => '2026-10-03', 'urgency' => '🔴 weszło w życie 03.10.2026', 'summary' => 'Obniżka VAT na paliwo.',
        'sources' => [['url' => 'https://www.gov.pl/paliwo', 'title' => 'Gov', 'type' => 'oficjalne', 'date' => '2026-10-02']]];
}

test('validate_post_params', function () {
    assert_same(['ekspercki', 1000], validate_post_params('zły', null));
    assert_same(['lekki', 200], validate_post_params('lekki', '50'));
    assert_same(['ekspercki', 3000], validate_post_params('ekspercki', 99999));
    assert_same(['ekspercki', 1500], validate_post_params('ekspercki', '1500'));
    assert_same(['ekspercki', 1000], validate_post_params('ekspercki', 'abc'));
});

test('generate_post: źródła w wiadomości użytkownika, web_fetch max 3, styl i limit w systemie', function () {
    $req = [];
    $c = fake_claude([fake_response([text_block("Post 🚚 #transport")])], $req);
    $r = generate_post($c, post_topic(), 'lekki', 500, 'podkreśl termin', TEST_CFG);
    assert_same(['text' => 'Post 🚚 #transport', 'length' => 17, 'over_limit' => false], $r);
    $p = $req[0]['payload'];
    assert_true(str_contains($p['messages'][0]['content'], 'https://www.gov.pl/paliwo'));
    assert_true(str_contains($p['messages'][0]['content'], 'podkreśl termin'));
    assert_same(['type' => 'web_fetch_20250910', 'name' => 'web_fetch', 'max_uses' => 3], $p['tools'][0]);
    assert_true(str_contains($p['system'], '500 znaków'));
    assert_true(str_contains($p['system'], 'pytania do czytelnika'));
});

test('generate_post: za długi → skrócenie; nadal za długi → ostrzeżenie', function () {
    $req = [];
    $c = fake_claude([fake_response([text_block(str_repeat('a', 260))]), fake_response([text_block(str_repeat('b', 199))])], $req);
    $r = generate_post($c, post_topic(), 'ekspercki', 200, '', TEST_CFG);
    assert_same(['text' => str_repeat('b', 199), 'length' => 199, 'over_limit' => false], $r);
    assert_true(str_contains(end($req[1]['payload']['messages'])['content'], '260 znaków'));

    $req2 = [];
    $c2 = fake_claude([fake_response([text_block(str_repeat('a', 260))]), fake_response([text_block(str_repeat('b', 230))])], $req2);
    $r2 = generate_post($c2, post_topic(), 'ekspercki', 200, '', TEST_CFG);
    assert_same(true, $r2['over_limit']);
    assert_same(230, $r2['length']);
});

test('generate_post: pause_turn kontynuowany, tekst tylko po narzędziach, bez otoczki ```', function () {
    $req = [];
    $c = fake_claude([
        fake_response([text_block('Otwieram źródło.'), ['type' => 'server_tool_use', 'id' => 's1', 'name' => 'web_fetch', 'input' => ['url' => 'https://www.gov.pl/paliwo']]], 'pause_turn'),
        fake_response([['type' => 'web_fetch_tool_result', 'tool_use_id' => 's1', 'content' => []], text_block("```\nGotowy post\n```")]),
    ], $req);
    assert_same('Gotowy post', generate_post($c, post_topic(), 'ekspercki', 1000, '', TEST_CFG)['text']);
    assert_same(2, count($req));
});
