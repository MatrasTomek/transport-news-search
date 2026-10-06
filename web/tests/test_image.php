<?php
declare(strict_types=1);

require_once __DIR__ . '/fakes.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/prompts.php';
require_once __DIR__ . '/../lib/svg.php';
require_once __DIR__ . '/../lib/image.php';

const LOGO = 'data:image/png;base64,iVBORw0KGgo=';

function svg_doc(string $inner): string
{
    return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1200 630">' . $inner . '</svg>';
}

test('extract_svg z bloku kodu i prozy', function () {
    assert_same('<svg a="1"></svg>', extract_svg("Proszę:\n```svg\n<svg a=\"1\"></svg>\n```"));
    assert_same(null, extract_svg('brak'));
});

test('sanitize_svg usuwa skrypty, on*, foreignObject, zewnętrzne odwołania', function () {
    $out = sanitize_svg(svg_doc(
        '<script>alert(1)</script><rect onload="x()" fill="url(#g)" width="5" height="5"/>'
        . '<foreignObject><div>x</div></foreignObject>'
        . '<image href="https://evil.example/x.png"/><use xlink:href="#g"/><a xlink:href="javascript:alert(1)"><text>t</text></a>'
        . '<rect style="fill:url(https://evil.example/f)"/><style>@import url(https://evil.example/a.css);</style>'
    ), null, 1200, 630);
    foreach (['<script', 'onload', 'foreignObject', 'evil.example', 'javascript:', '@import'] as $bad) {
        assert_true(!str_contains($out, $bad), "zostało: $bad");
    }
    assert_true(str_contains($out, 'fill="url(#g)"'));
    assert_true(str_contains($out, 'xlink:href="#g"'));
    assert_true(str_contains($out, 'width="1200"'));
});

test('sanitize_svg wstawia logo w logo-slot albo usuwa slot', function () {
    $svg = svg_doc('<rect id="logo-slot" x="900" y="530" width="260" height="70" fill="none"/>');
    $with = sanitize_svg($svg, LOGO, 1200, 630);
    assert_true(str_contains($with, '<image'));
    assert_true(str_contains($with, 'href="' . LOGO . '"'));
    assert_true(str_contains($with, 'x="900"'));
    assert_true(!str_contains(sanitize_svg($svg, null, 1200, 630), 'logo-slot'));
});

test('sanitize_svg: brak xmlns dodawany, DOCTYPE i nie-SVG odrzucane', function () {
    assert_true(str_contains(sanitize_svg('<svg viewBox="0 0 10 10"><rect/></svg>', null, 1080, 1080), 'xmlns="http://www.w3.org/2000/svg"'));
    assert_throws(RuntimeException::class, fn() => sanitize_svg('<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg>&x;</svg>', null, 10, 10));
    assert_throws(RuntimeException::class, fn() => sanitize_svg('<html></html>', null, 10, 10));
    assert_throws(RuntimeException::class, fn() => sanitize_svg('<svg><rect></svg>', null, 10, 10));
});

test('generate_image: sukces, rozmiar formatu, prompt z kolorami i slotem logo', function () {
    $req = [];
    $c = fake_claude([fake_response([text_block(svg_doc('<rect width="10" height="10"/>'))])], $req);
    $r = generate_image($c, post_topic(), 'Treść posta', 'kwadrat', TEST_CFG, null);
    assert_same(1080, $r['width']);
    assert_same(1080, $r['height']);
    assert_true(str_contains($r['svg'], '<svg'));
    $user = $req[0]['payload']['messages'][0]['content'];
    assert_true(str_contains($user, '#1f4e79'));
    assert_true(str_contains($user, 'id="logo-slot" x="780" y="980" width="260" height="70"'));
});

test('generate_image: jedna ponowna próba, potem wyjątek', function () {
    $req = [];
    $c = fake_claude([fake_response([text_block('nie umiem')]), fake_response([text_block(svg_doc('<rect/>'))])], $req);
    assert_same(630, generate_image($c, post_topic(), 'x', 'poziomy', TEST_CFG, null)['height']);
    assert_true(str_contains(end($req[1]['payload']['messages'])['content'], 'SVG'));

    $req2 = [];
    $c2 = fake_claude([fake_response([text_block('a')]), fake_response([text_block('b')])], $req2);
    assert_throws(RuntimeException::class, fn() => generate_image($c2, post_topic(), 'x', 'poziomy', TEST_CFG, null));
});
