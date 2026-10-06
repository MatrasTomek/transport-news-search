<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

$in = api_guard();
$topic = topic_get($DB, (int)($in['topic_id'] ?? 0)) ?? json_response(['error' => 'Nie znaleziono tematu.'], 404);
$post = mb_substr(trim((string)($in['post'] ?? '')), 0, 5000, 'UTF-8');
if ($post === '') {
    json_response(['error' => 'Najpierw wygeneruj post.'], 400);
}
$format = array_key_exists($in['format'] ?? '', IMAGE_FORMATS) ? $in['format'] : 'poziomy';

set_time_limit((int)$CONFIG['api_timeout'] + 30);
try {
    json_response(generate_image(app_claude($CONFIG), $topic, $post, $format, $CONFIG, logo_data_uri()));
} catch (RuntimeException $e) {
    json_response(['error' => $e->getMessage()], 502);
}
