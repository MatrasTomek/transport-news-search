<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

$in = api_guard();
$search = search_get($DB, (int)($in['search_id'] ?? 0)) ?? json_response(['error' => 'Nie znaleziono wyszukiwania.'], 404);
$searchId = (int)$search['id'];
$now = time();
$timeout = (int)$CONFIG['api_timeout'];

$step = step_next($DB, $searchId);
if ($step === null) {
    json_response(['state' => 'finished', 'search_status' => $search['status']]);
}
if (step_is_busy($step, $now, $timeout)) {
    json_response(['state' => 'busy', 'label' => $step['label']]);
}

set_time_limit($timeout + 30);
ignore_user_abort(true);
$sources = (string)@file_get_contents(DATA_DIR . '/zrodla.md');
$result = run_step($DB, app_claude($CONFIG), $search, $step, $CONFIG, new DateTimeImmutable('today'), $sources, $now);
json_response($result + [
    'step_id' => (int)$step['id'],
    'label' => $step['label'],
    'steps' => steps_summary($DB, $searchId),
]);
