<?php
declare(strict_types=1);

function render_page_start(string $title, bool $nav): string
{
    $h = '<!doctype html><html lang="pl"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="csrf-token" content="' . e(csrf_token()) . '">'
        . '<title>' . e($title) . '</title><link rel="stylesheet" href="assets/app.css"></head><body>';
    if ($nav) {
        $h .= '<header class="topbar"><strong>Tematy transportowe</strong><nav>'
            . '<a href="index.php">Wyszukiwanie</a><a href="zrodla.php">Źródła</a>'
            . '<form method="post" action="logout.php" class="inline"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
            . '<button type="submit" class="link">Wyloguj</button></form></nav></header>';
    }
    return $h . '<main>';
}

function render_page_end(): string
{
    return '</main><script src="assets/app.js"></script></body></html>';
}

function render_topics(array $topics): string
{
    if ($topics === []) {
        return '<p class="muted">Brak tematów spełniających kryteria w tym okresie.</p>';
    }
    $h = '<div class="table-wrap"><table class="topics"><thead><tr><th>#</th><th>Tytuł</th><th>Obszar</th><th>Status</th>'
        . '<th>Pilność</th><th>Opis</th><th>Źródła</th><th></th></tr></thead><tbody>';
    foreach ($topics as $t) {
        $id = (int)$t['id'];
        $src = '';
        foreach ($t['sources'] as $s) {
            $url = safe_url($s['url'] ?? null);
            if ($url === null) {
                continue;
            }
            $meta = $s['type'] . ($s['date'] ? ', ' . format_date_pl($s['date']) : '');
            $src .= '<li><a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e($s['title']) . '</a> '
                . '<span class="muted">— ' . e($meta) . '</span></li>';
        }
        $h .= '<tr class="topic-row urg-' . e($t['urgency_code']) . '">'
            . '<td>' . (int)$t['nr'] . '</td>'
            . '<td><strong>' . e($t['title']) . '</strong></td>'
            . '<td>' . e($t['area']) . '</td>'
            . '<td>' . e($t['status']) . '</td>'
            . '<td class="nowrap">' . e($t['urgency']) . '</td>'
            . '<td>' . e($t['summary']) . '</td>'
            . '<td><ul class="sources">' . $src . '</ul></td>'
            . '<td><button type="button" data-write-post="' . $id . '">Pisz post</button></td></tr>'
            . '<tr class="post-row" id="post-row-' . $id . '" hidden><td colspan="8"></td></tr>';
    }
    return $h . '</tbody></table></div>';
}

function render_steps(array $steps, array $search): string
{
    $icons = ['pending' => '⏳', 'running' => '🔄', 'paused' => '🔄', 'done' => '✅', 'error' => '❌'];
    $h = '<ul class="steps">';
    foreach ($steps as $s) {
        $h .= '<li>' . ($icons[$s['status']] ?? '•') . ' ' . e($s['label']);
        if ($s['status'] === 'error') {
            $h .= ' <span class="error">' . e((string)$s['error']) . '</span> '
                . '<button type="button" data-retry-step="' . (int)$s['id'] . '" data-search="' . (int)$search['id'] . '">Ponów</button>';
        }
        $h .= '</li>';
    }
    $h .= '</ul>';
    if ($search['status'] === 'running') {
        $h .= '<p>Wyszukiwanie nie zostało dokończone. <button type="button" data-resume="' . (int)$search['id'] . '">Wznów</button></p>';
    }
    return $h;
}

function render_extras(array $search): string
{
    $extras = json_decode((string)($search['extras_json'] ?? ''), true) ?: [];
    $h = '';
    if (!empty($search['note'])) {
        $h .= '<p class="note">' . e($search['note']) . '</p>';
    }
    $h .= '<h3>Do obserwacji</h3>';
    $watch = $extras['watch'] ?? [];
    if ($watch === []) {
        $h .= '<p class="muted">brak</p>';
    } else {
        $h .= '<ul>';
        foreach ($watch as $w) {
            $url = safe_url($w['url'] ?? null);
            $h .= '<li>' . e($w['text'])
                . ($url !== null ? ' <a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">link</a>' : '')
                . (!empty($w['unverified']) ? ' <span class="muted">(niezweryfikowane)</span>' : '') . '</li>';
        }
        $h .= '</ul>';
    }
    $h .= '<h3>Obszary bez istotnych nowości</h3>';
    $noNews = $extras['no_news'] ?? [];
    $h .= $noNews === [] ? '<p class="muted">brak</p>'
        : '<ul>' . implode('', array_map(fn($n) => '<li>' . e($n) . '</li>', $noNews)) . '</ul>';
    return $h;
}

function render_history(array $list, ?int $currentId): string
{
    if ($list === []) {
        return '<p class="muted">Brak wcześniejszych wyszukiwań.</p>';
    }
    $labels = ['running' => 'w toku', 'done' => 'gotowe', 'error' => 'błąd'];
    $h = '<ul class="history">';
    foreach ($list as $s) {
        $id = (int)$s['id'];
        $what = $s['query'] === null ? '6 obszarów' : '„' . $s['query'] . '”';
        $h .= '<li' . ($id === $currentId ? ' class="current"' : '') . '><a href="index.php?search=' . $id . '">'
            . e($s['created_at']) . ' · ' . e($what) . ' · ' . (int)$s['days'] . ' dni · '
            . (int)$s['topic_count'] . ' tematów · ' . e($labels[$s['status']] ?? $s['status']) . '</a></li>';
    }
    return $h . '</ul>';
}

function render_post_template(): string
{
    return <<<'HTML'
        <template id="post-panel-tpl">
          <div class="post-panel">
            <div class="row">
              <label>Styl <select data-role="style"><option value="ekspercki">Ekspercki</option><option value="lekki">Lekki</option></select></label>
              <label>Limit znaków <input data-role="max" type="number" min="200" max="3000" step="50" value="1000"></label>
            </div>
            <label>Dodatkowe wskazówki (opcjonalnie)<textarea data-role="hints" rows="2" maxlength="500"></textarea></label>
            <button type="button" data-role="generate">Generuj</button>
            <p data-role="status" class="status" hidden></p>
            <div data-role="result" hidden>
              <textarea data-role="output" rows="12"></textarea>
              <p><span data-role="counter"></span> <span data-role="warning" class="error"></span></p>
              <button type="button" data-role="copy">Kopiuj</button>
              <button type="button" data-role="generate-again">Generuj ponownie</button>
              <div class="image-box">
                <label>Format <select data-role="format"><option value="poziomy">1200×630 (poziomy)</option><option value="kwadrat">1080×1080 (kwadrat)</option></select></label>
                <button type="button" data-role="image">Wygeneruj obraz</button>
                <p data-role="image-status" class="status" hidden></p>
                <div data-role="image-result" hidden>
                  <img data-role="preview" alt="Podgląd grafiki">
                  <button type="button" data-role="png">Pobierz PNG</button>
                  <button type="button" data-role="svg">Pobierz SVG</button>
                  <button type="button" data-role="image-again">Generuj ponownie</button>
                </div>
              </div>
            </div>
          </div>
        </template>
        HTML;
}
