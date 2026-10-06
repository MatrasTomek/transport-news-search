<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

$in = api_guard();
$topic = topic_get($DB, (int)($in['topic_id'] ?? 0)) ?? json_response(['error' => 'Nie znaleziono tematu.'], 404);
[$style, $maxChars] = validate_post_params($in['style'] ?? null, $in['max_chars'] ?? null);
$hints = mb_substr(trim((string)($in['hints'] ?? '')), 0, 500, 'UTF-8');

set_time_limit((int)$CONFIG['api_timeout'] + 30);
try {
    json_response(generate_post(app_claude($CONFIG), $topic, $style, $maxChars, $hints, $CONFIG));
} catch (ClaudeException $e) {
    json_response(['error' => $e->getMessage()], 502);
}
