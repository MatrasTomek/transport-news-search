<?php
declare(strict_types=1);

function validate_days(?string $raw): array
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return ['days' => 14, 'note' => null];
    }
    if (preg_match('/^[0-9]+$/', $raw) === 1 && (int)$raw >= 1) {
        $n = (int)$raw;
        if ($n > 365) {
            return ['days' => 365, 'note' => 'Okres ograniczono do 365 dni.'];
        }
        return ['days' => $n, 'note' => null];
    }
    return ['days' => 14, 'note' => "Nieprawidłowy argument „{$raw}”, przyjęto domyślne 14 dni."];
}

function search_period(DateTimeImmutable $today, int $days): array
{
    return [$today->modify("-{$days} days")->format('Y-m-d'), $today->format('Y-m-d')];
}

function parse_iso_date(?string $s): ?DateTimeImmutable
{
    if ($s === null || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m) !== 1) {
        return null;
    }
    if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
        return null;
    }
    return new DateTimeImmutable($s);
}

function format_date_pl(?string $iso): string
{
    $d = parse_iso_date($iso);
    return $d === null ? 'nieznana' : $d->format('d.m.Y');
}

function urgency(?string $effectiveDate, string $status, DateTimeImmutable $today): array
{
    if (in_array($status, ['projekt', 'zapowiedź'], true)) {
        return ['code' => 'white', 'label' => "⚪ $status"];
    }
    $d = parse_iso_date($effectiveDate);
    if ($d === null) {
        return ['code' => 'white', 'label' => '⚪ data nieznana'];
    }
    // Liczymy w UTC, żeby zmiana czasu (DST) nie zjadała dnia.
    $utc = new DateTimeZone('UTC');
    $from = new DateTimeImmutable($today->format('Y-m-d'), $utc);
    $diff = (int)$from->diff(new DateTimeImmutable($d->format('Y-m-d'), $utc))->format('%r%a');
    $date = $d->format('d.m.Y');
    if ($diff >= -30 && $diff <= 30) {
        return ['code' => 'red', 'label' => $diff <= 0 ? "🔴 weszło w życie $date" : "🔴 wchodzi w życie $date"];
    }
    if ($diff >= 31 && $diff <= 90) {
        return ['code' => 'yellow', 'label' => "🟡 wchodzi w życie $date"];
    }
    if ($diff > 90) {
        return ['code' => 'white', 'label' => "⚪ wchodzi w życie $date"];
    }
    return ['code' => 'white', 'label' => "⚪ obowiązuje od $date"];
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function safe_url(mixed $url): ?string
{
    if (!is_string($url)) {
        return null;
    }
    $url = trim($url);
    if ($url === '' || preg_match('/[\s\x00-\x1F"<>]/u', $url) === 1) {
        return null;
    }
    $p = parse_url($url);
    if ($p === false || !in_array(strtolower($p['scheme'] ?? ''), ['http', 'https'], true) || empty($p['host'])) {
        return null;
    }
    return $url;
}

function extract_json(string $text): ?array
{
    if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m) === 1) {
        $text = $m[1];
    }
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start === false || $end === false || $end < $start) {
        return null;
    }
    $data = json_decode(substr($text, $start, $end - $start + 1), true);
    return is_array($data) ? $data : null;
}

function char_count(string $s): int
{
    return mb_strlen($s, 'UTF-8');
}

function clean_query(mixed $raw): ?string
{
    $q = trim((string)preg_replace('/\s+/u', ' ', (string)$raw));
    if ($q === '') {
        return null;
    }
    return mb_substr($q, 0, 200, 'UTF-8');
}

function ensure_sources_file(string $target, string $default): void
{
    if (!is_file($target) && is_file($default)) {
        copy($default, $target);
    }
}

function app_log(string $msg): void
{
    if (defined('APP_LOG')) {
        error_log('[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", 3, APP_LOG);
    }
}
