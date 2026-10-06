<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';

function db_with_search(): array
{
    $db = db_connect(':memory:');
    $id = search_create($db, null, 14, null, [
        ['label' => 'Prawo transportowe', 'kind' => 'area'],
        ['label' => 'ZUS i składki', 'kind' => 'area'],
        ['label' => 'Wybór tematów', 'kind' => 'synthesis'],
    ], 1000);
    return [$db, $id];
}

function db_topic(int $nr): array
{
    return ['title' => "Temat $nr", 'area' => 'ZUS i składki', 'status' => 'projekt', 'effective_date' => null,
        'summary' => 'Opis', 'urgency' => '⚪ projekt', 'urgency_code' => 'white',
        'sources' => [['url' => 'https://zus.pl/x', 'title' => 'ZUS', 'type' => 'oficjalne', 'date' => null]]];
}

test('db: tworzenie wyszukiwania i kroków', function () {
    [$db, $id] = db_with_search();
    $s = search_get($db, $id);
    assert_same('running', $s['status']);
    assert_same(null, $s['query']);
    $steps = steps_for($db, $id);
    assert_same(3, count($steps));
    assert_same('pending', $steps[0]['status']);
    assert_same('synthesis', $steps[2]['kind']);
});

test('db: step_next pomija done i error', function () {
    [$db, $id] = db_with_search();
    $steps = steps_for($db, $id);
    step_update($db, (int)$steps[0]['id'], ['status' => 'done', 'result_json' => '{}'], 1001);
    step_update($db, (int)$steps[1]['id'], ['status' => 'error', 'error' => 'x'], 1002);
    assert_same((int)$steps[2]['id'], (int)step_next($db, $id)['id']);
    step_update($db, (int)$steps[2]['id'], ['status' => 'done'], 1003);
    assert_same(null, step_next($db, $id));
    assert_same(1002, (int)step_get($db, (int)$steps[1]['id'])['updated_at']);
});

test('db: step_update odrzuca nieznane pola', function () {
    [$db, $id] = db_with_search();
    $step = steps_for($db, $id)[0];
    assert_throws(InvalidArgumentException::class, fn() => step_update($db, (int)$step['id'], ['label' => 'x'], 1));
});

test('db: tematy zapis i odczyt', function () {
    [$db, $id] = db_with_search();
    topics_replace($db, $id, [db_topic(1), db_topic(2)]);
    topics_replace($db, $id, [db_topic(1), db_topic(2)]);
    $t = topics_for($db, $id);
    assert_same(2, count($t));
    assert_same(2, (int)$t[1]['nr']);
    assert_same('https://zus.pl/x', $t[0]['sources'][0]['url']);
    assert_same('Temat 1', topic_get($db, (int)$t[0]['id'])['title']);
    assert_same(null, topic_get($db, 99999));
    assert_same(2, (int)search_list($db)[0]['topic_count']);
});

test('db: step_retry resetuje krok i syntezę', function () {
    [$db, $id] = db_with_search();
    $steps = steps_for($db, $id);
    step_update($db, (int)$steps[0]['id'], ['status' => 'error', 'error' => 'timeout'], 1);
    step_update($db, (int)$steps[1]['id'], ['status' => 'done'], 1);
    step_update($db, (int)$steps[2]['id'], ['status' => 'done'], 1);
    topics_replace($db, $id, [db_topic(1)]);
    search_set_status($db, $id, 'done', ['watch' => []]);
    assert_same($id, step_retry($db, (int)$steps[0]['id'], 2));
    $after = steps_for($db, $id);
    assert_same('pending', $after[0]['status']);
    assert_same(null, $after[0]['error']);
    assert_same('done', $after[1]['status']);
    assert_same('pending', $after[2]['status']);
    assert_same([], topics_for($db, $id));
    assert_same('running', search_get($db, $id)['status']);
    assert_same(null, step_retry($db, 99999, 2));
});

test('db: steps_summary', function () {
    [$db, $id] = db_with_search();
    $s = steps_summary($db, $id);
    assert_same(['id', 'label', 'kind', 'status', 'error'], array_keys($s[0]));
    assert_true(is_int($s[0]['id']));
});

test('db: blokada logowania po 5 próbach na 15 minut', function () {
    $db = db_connect(':memory:');
    for ($i = 0; $i < 4; $i++) {
        login_register_failure($db, '1.2.3.4', 1000);
    }
    assert_same(false, login_is_locked($db, '1.2.3.4', 1000));
    login_register_failure($db, '1.2.3.4', 1000);
    assert_same(true, login_is_locked($db, '1.2.3.4', 1000));
    assert_same(true, login_is_locked($db, '1.2.3.4', 1899));
    assert_same(false, login_is_locked($db, '1.2.3.4', 1900));
    assert_same(false, login_is_locked($db, '5.6.7.8', 1000));
});

test('db: login_reset zeruje licznik', function () {
    $db = db_connect(':memory:');
    for ($i = 0; $i < 4; $i++) {
        login_register_failure($db, '1.2.3.4', 1000);
    }
    login_reset($db, '1.2.3.4');
    login_register_failure($db, '1.2.3.4', 1000);
    assert_same(false, login_is_locked($db, '1.2.3.4', 1000));
});

test('db: step_claim jest atomowy i przejmuje zawieszony krok', function () {
    [$db, $id] = db_with_search();
    $step = steps_for($db, $id)[0];
    assert_same(true, step_claim($db, (int)$step['id'], 1000, 150));
    assert_same(false, step_claim($db, (int)$step['id'], 1001, 150));
    assert_same(false, step_claim($db, (int)$step['id'], 1360, 150));
    assert_same(true, step_claim($db, (int)$step['id'], 1361, 150));
    step_update($db, (int)$step['id'], ['status' => 'paused'], 2000);
    assert_same(true, step_claim($db, (int)$step['id'], 2001, 150));
    step_update($db, (int)$step['id'], ['status' => 'done'], 3000);
    assert_same(false, step_claim($db, (int)$step['id'], 9999, 150));
});
