<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

$in = api_guard();
$query = clean_query($in['query'] ?? null);
$days = validate_days(isset($in['days']) ? (string)$in['days'] : null);
$id = search_create($DB, $query, $days['days'], $days['note'], build_steps($query), time());
json_response(['search_id' => $id]);
