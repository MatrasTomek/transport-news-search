<?php
declare(strict_types=1);

require_once __DIR__ . '/fakes.php';

test('claude: wysyła model i zwraca content_raw', function () {
    $req = [];
    $c = fake_claude([fake_response([text_block('ok')])], $req);
    $r = $c->send(['max_tokens' => 10, 'messages' => [user_message('hej')]]);
    assert_same('claude-test', $req[0]['payload']['model']);
    assert_same('hej', $req[0]['payload']['messages'][0]['content']);
    assert_same('end_turn', $r['stop_reason']);
    assert_true($r['content_raw'][0] instanceof stdClass);
});

test('claude: 529 → jedno ponowienie z retry-after', function () {
    $req = [];
    $slept = [];
    $responses = [['status' => 529, 'headers' => ['retry-after' => '7'], 'body' => '{}'], fake_response([text_block('ok')])];
    $transport = function (string $b, string $k) use (&$responses, &$req) { $req[] = $b; return array_shift($responses); };
    $c = new ClaudeClient('k', 'm', $transport, function (int $s) use (&$slept) { $slept[] = $s; });
    $c->send(['messages' => []]);
    assert_same(2, count($req));
    assert_same([7], $slept);
});

test('claude: drugi błąd 529 → ClaudeException po polsku', function () {
    $req = [];
    $c = fake_claude([['status' => 529, 'headers' => [], 'body' => '{}'], ['status' => 529, 'headers' => [], 'body' => '{}']], $req);
    try {
        $c->send(['messages' => []]);
        throw new AssertionError('brak wyjątku');
    } catch (ClaudeException $e) {
        assert_same('API Claude jest przeciążone lub niedostępne. Spróbuj za chwilę.', $e->getMessage());
    }
});

test('claude: timeout (status 0) bez ponowienia', function () {
    $req = [];
    $c = fake_claude([['status' => 0, 'headers' => [], 'body' => '']], $req);
    assert_throws(ClaudeException::class, fn() => $c->send(['messages' => []]));
    assert_same(1, count($req));
});

test('claude: 401 i brak klucza', function () {
    $req = [];
    $c = fake_claude([['status' => 401, 'headers' => [], 'body' => '{"error":{"message":"invalid x-api-key"}}']], $req);
    try {
        $c->send(['messages' => []]);
        throw new AssertionError('brak wyjątku');
    } catch (ClaudeException $e) {
        assert_true(str_contains($e->getMessage(), 'Sprawdź klucz API w config.php'));
    }
    $empty = new ClaudeClient('', 'm', fn() => throw new RuntimeException('nie powinno wołać'));
    assert_throws(ClaudeException::class, fn() => $empty->send(['messages' => []]));
});

test('claude: errorMessage 400 z treścią API', function () {
    assert_same('Błąd API (400): bad', ClaudeClient::errorMessage(400, '{"error":{"message":"bad"}}'));
    assert_same('Błąd API (400).', ClaudeClient::errorMessage(400, 'xx'));
    assert_same(5, ClaudeClient::retryDelay([]));
    assert_same(30, ClaudeClient::retryDelay(['retry-after' => '120']));
});

test('finalText: tylko tekst po ostatnim bloku narzędzia', function () {
    $r = ['content' => [
        text_block('Sprawdzę źródła.'),
        ['type' => 'server_tool_use', 'id' => 's1', 'name' => 'web_search', 'input' => ['query' => 'x']],
        ['type' => 'web_search_tool_result', 'tool_use_id' => 's1', 'content' => []],
        text_block('{"a":'),
        text_block('1}'),
    ]];
    assert_same('{"a":1}', ClaudeClient::finalText($r));
    assert_same('sam tekst', ClaudeClient::finalText(['content' => [text_block('sam tekst')]]));
});

test('append_assistant zachowuje puste obiekty i scala kolejne tury', function () {
    $raw = json_decode('[{"type":"server_tool_use","id":"s1","name":"web_fetch","input":{}}]');
    $messages = append_assistant([user_message('start')], $raw);
    $messages = append_assistant($messages, json_decode('[{"type":"text","text":"dalej"}]'));
    assert_same(2, count($messages));
    $json = json_encode(['messages' => $messages]);
    assert_true(str_contains($json, '"input":{}'), $json);
    assert_same(2, count($messages[1]->content));
});
