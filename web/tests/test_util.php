<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/util.php';

test('validate_days: puste i poprawne', function () {
    assert_same(['days' => 14, 'note' => null], validate_days(null));
    assert_same(['days' => 14, 'note' => null], validate_days('  '));
    assert_same(['days' => 21, 'note' => null], validate_days(' 21 '));
    assert_same(['days' => 1, 'note' => null], validate_days('1'));
    assert_same(['days' => 365, 'note' => null], validate_days('365'));
});

test('validate_days: za duże i niepoprawne', function () {
    assert_same(['days' => 365, 'note' => 'Okres ograniczono do 365 dni.'], validate_days('1000'));
    foreach (['0', '-5', '30 dni', 'abc', '2.5'] as $bad) {
        assert_same(['days' => 14, 'note' => "Nieprawidłowy argument „{$bad}”, przyjęto domyślne 14 dni."], validate_days($bad), $bad);
    }
});

test('search_period przez granicę miesiąca', function () {
    assert_same(['2026-08-23', '2026-10-02'], search_period(new DateTimeImmutable('2026-10-02'), 40));
});

test('format_date_pl', function () {
    assert_same('03.10.2026', format_date_pl('2026-10-03'));
    assert_same('nieznana', format_date_pl(null));
    assert_same('nieznana', format_date_pl('2026-02-30'));
});

test('urgency: granice 30/31/90/91 dni', function () {
    $today = new DateTimeImmutable('2026-10-06');
    assert_same('red', urgency('2026-11-05', 'uchwalone', $today)['code']);    // +30
    assert_same('yellow', urgency('2026-11-06', 'uchwalone', $today)['code']); // +31
    assert_same('yellow', urgency('2027-01-04', 'uchwalone', $today)['code']); // +90
    assert_same('white', urgency('2027-01-05', 'uchwalone', $today)['code']);  // +91
    assert_same('red', urgency('2026-09-06', 'obowiązuje', $today)['code']);   // -30
    assert_same('white', urgency('2026-09-05', 'obowiązuje', $today)['code']); // -31
});

test('urgency: przejście na czas letni nie zmienia liczby dni', function () {
    $today = new DateTimeImmutable('2027-03-01');
    assert_same('yellow', urgency('2027-04-01', 'uchwalone', $today)['code']); // +31 przez 28.03.2027
});

test('urgency: etykiety, projekt, brak daty', function () {
    $today = new DateTimeImmutable('2026-10-06');
    assert_same('🔴 weszło w życie 03.10.2026', urgency('2026-10-03', 'obowiązuje', $today)['label']);
    assert_same('🔴 wchodzi w życie 20.10.2026', urgency('2026-10-20', 'uchwalone', $today)['label']);
    assert_same('🟡 wchodzi w życie 01.12.2026', urgency('2026-12-01', 'uchwalone', $today)['label']);
    assert_same('⚪ obowiązuje od 01.01.2026', urgency('2026-01-01', 'obowiązuje', $today)['label']);
    assert_same(['code' => 'white', 'label' => '⚪ projekt'], urgency('2026-10-10', 'projekt', $today));
    assert_same(['code' => 'white', 'label' => '⚪ data nieznana'], urgency(null, 'uchwalone', $today));
});

test('e escapuje HTML', function () {
    assert_same('&lt;b&gt;&quot;x&quot;&amp;&#039;', e('<b>"x"&\''));
    assert_same('', e(null));
});

test('safe_url', function () {
    assert_same('https://gov.pl/a?b=1', safe_url('https://gov.pl/a?b=1'));
    assert_same('https://trans.info/pl/obniżka', safe_url(' https://trans.info/pl/obniżka '));
    assert_same(null, safe_url('javascript:alert(1)'));
    assert_same(null, safe_url('ftp://x.pl'));
    assert_same(null, safe_url('https://x.pl/a b'));
    assert_same(null, safe_url('https://x.pl/"><script>'));
    assert_same(null, safe_url(123));
});

test('extract_json: czysty, w bloku kodu, w prozie', function () {
    assert_same(['a' => 1], extract_json('{"a":1}'));
    assert_same(['a' => 1], extract_json("Oto wynik:\n```json\n{\"a\":1}\n```\nKoniec."));
    assert_same(['a' => ['b' => 2]], extract_json('Wynik: {"a":{"b":2}} — gotowe'));
    assert_same(null, extract_json('brak danych'));
    assert_same(null, extract_json('{niepoprawny}'));
});

test('char_count liczy polskie znaki i emoji', function () {
    assert_same(8, char_count('Zażółć 🚚'));
});

test('clean_query', function () {
    assert_same(null, clean_query('   '));
    assert_same(null, clean_query(null));
    assert_same('e-CMR dla przewoźników', clean_query("  e-CMR   dla\nprzewoźników "));
    assert_same(200, mb_strlen(clean_query(str_repeat('ą', 300))));
});

test('ensure_sources_file kopiuje tylko gdy brak', function () {
    $dir = sys_get_temp_dir() . '/ts_' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents("$dir/default.md", 'domyślne');
    ensure_sources_file("$dir/zrodla.md", "$dir/default.md");
    assert_same('domyślne', file_get_contents("$dir/zrodla.md"));
    file_put_contents("$dir/zrodla.md", 'moje');
    ensure_sources_file("$dir/zrodla.md", "$dir/default.md");
    assert_same('moje', file_get_contents("$dir/zrodla.md"));
});
