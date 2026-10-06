<?php
declare(strict_types=1);

const TOPIC_STATUSES = ['obowiązuje', 'uchwalone', 'projekt', 'zapowiedź'];
const SOURCE_TYPES = ['oficjalne', 'medium branżowe'];

function iso_or_null(mixed $v): ?string
{
    return is_string($v) && parse_iso_date($v) !== null ? $v : null;
}

function validate_sources(mixed $raw): array
{
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $s) {
        if (!is_array($s)) {
            continue;
        }
        $url = safe_url($s['url'] ?? null);
        if ($url === null) {
            continue;
        }
        $title = trim((string)($s['title'] ?? ''));
        $out[] = [
            'url' => $url,
            'title' => $title !== '' ? $title : $url,
            'type' => in_array($s['type'] ?? null, SOURCE_TYPES, true) ? $s['type'] : 'medium branżowe',
            'date' => iso_or_null($s['date'] ?? null),
        ];
    }
    return $out;
}

function validate_topic(mixed $raw): ?array
{
    if (!is_array($raw)) {
        return null;
    }
    $title = trim((string)($raw['title'] ?? ''));
    $status = $raw['status'] ?? null;
    if ($title === '' || !in_array($status, TOPIC_STATUSES, true)) {
        return null;
    }
    $sources = validate_sources($raw['sources'] ?? null);
    if ($sources === []) {
        return null;
    }
    return [
        'title' => $title,
        'area' => trim((string)($raw['area'] ?? '')),
        'status' => $status,
        'effective_date' => iso_or_null($raw['effective_date'] ?? null),
        'summary' => trim((string)($raw['summary'] ?? '')),
        'sources' => $sources,
    ];
}

function validate_watch(mixed $raw): array
{
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $w) {
        if (!is_array($w)) {
            continue;
        }
        $text = trim((string)($w['text'] ?? ''));
        if ($text === '') {
            continue;
        }
        $out[] = ['text' => $text, 'url' => safe_url($w['url'] ?? null), 'unverified' => ($w['unverified'] ?? false) === true];
    }
    return $out;
}

function validate_candidates(array $data): array
{
    if (!isset($data['candidates']) || !is_array($data['candidates'])) {
        throw new InvalidArgumentException('Brak listy "candidates".');
    }
    return [
        'candidates' => array_values(array_filter(array_map('validate_topic', $data['candidates']))),
        'watch' => validate_watch($data['watch'] ?? []),
    ];
}

function validate_synthesis(array $data): array
{
    if (!isset($data['topics']) || !is_array($data['topics'])) {
        throw new InvalidArgumentException('Brak listy "topics".');
    }
    $topics = array_values(array_filter(array_map('validate_topic', $data['topics'])));
    $noNews = is_array($data['no_news'] ?? null)
        ? array_values(array_filter($data['no_news'], fn($x) => is_string($x) && trim($x) !== ''))
        : [];
    return [
        'topics' => array_slice($topics, 0, 10),
        'watch' => validate_watch($data['watch'] ?? []),
        'no_news' => $noNews,
    ];
}
