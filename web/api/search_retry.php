<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

$in = api_guard();
$searchId = step_retry($DB, (int)($in['step_id'] ?? 0), time()) ?? json_response(['error' => 'Nie znaleziono kroku.'], 404);
json_response(['search_id' => $searchId]);
