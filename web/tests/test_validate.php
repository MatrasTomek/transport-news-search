<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/validate.php';

function sample_topic(array $override = []): array
{
    return array_merge([
        'title' => 'Tańszy diesel',
        'area' => 'Księgowość i podatki w transporcie',
        'status' => 'obowiązuje',
        'effective_date' => '2026-10-03',
        'summary' => 'Obniżka VAT.',
        'sources' => [['url' => 'https://www.gov.pl/a', 'title' => 'Gov', 'type' => 'oficjalne', 'date' => '2026-10-02']],
    ], $override);
}

test('validate_topic: poprawny temat', function () {
    $t = validate_topic(sample_topic());
    assert_same('Tańszy diesel', $t['title']);
    assert_same('2026-10-03', $t['effective_date']);
    assert_same('oficjalne', $t['sources'][0]['type']);
});

test('validate_topic: zły status lub brak tytułu → null', function () {
    assert_same(null, validate_topic(sample_topic(['status' => 'uchwalone (podpisane)'])));
    assert_same(null, validate_topic(sample_topic(['title' => '  '])));
    assert_same(null, validate_topic('tekst'));
});

test('validate_topic odrzuca javascript: i temat bez źródeł', function () {
    $t = sample_topic(['sources' => [['url' => 'javascript:alert(1)', 'title' => 'x', 'type' => 'oficjalne']]]);
    assert_same(null, validate_topic($t));
    assert_same(null, validate_topic(sample_topic(['sources' => []])));
});

test('validate_topic: normalizuje typ źródła, datę i tytuł źródła', function () {
    $t = validate_topic(sample_topic([
        'effective_date' => '01.01.2027',
        'sources' => [['url' => 'https://trans.info/x', 'type' => 'blog', 'date' => 'wczoraj']],
    ]));
    assert_same(null, $t['effective_date']);
    assert_same('medium branżowe', $t['sources'][0]['type']);
    assert_same(null, $t['sources'][0]['date']);
    assert_same('https://trans.info/x', $t['sources'][0]['title']);
});

test('validate_watch', function () {
    $w = validate_watch([
        ['text' => 'Projekt X', 'url' => 'https://legislacja.gov.pl/x', 'unverified' => true],
        ['text' => 'Bez linku', 'url' => 'javascript:x'],
        ['text' => ''],
        'zły',
    ]);
    assert_same([
        ['text' => 'Projekt X', 'url' => 'https://legislacja.gov.pl/x', 'unverified' => true],
        ['text' => 'Bez linku', 'url' => null, 'unverified' => false],
    ], $w);
});

test('validate_candidates: struktura', function () {
    $r = validate_candidates(['candidates' => [sample_topic(), sample_topic(['status' => 'zły'])], 'watch' => []]);
    assert_same(1, count($r['candidates']));
    assert_same([], validate_candidates(['candidates' => []])['candidates']);
    assert_throws(InvalidArgumentException::class, fn() => validate_candidates(['topics' => []]));
});

test('validate_synthesis: maks. 10 tematów i no_news', function () {
    $r = validate_synthesis(['topics' => array_fill(0, 12, sample_topic()), 'watch' => [], 'no_news' => ['ZUS i składki: brak istotnych zmian w okresie.', 5]]);
    assert_same(10, count($r['topics']));
    assert_same(['ZUS i składki: brak istotnych zmian w okresie.'], $r['no_news']);
    assert_throws(InvalidArgumentException::class, fn() => validate_synthesis(['candidates' => []]));
});

test('validate_synthesis: no_news jako tekst nie wywraca walidacji', function () {
    assert_same([], validate_synthesis(['topics' => [], 'no_news' => 'brak'])['no_news']);
});
