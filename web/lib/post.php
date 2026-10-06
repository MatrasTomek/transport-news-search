<?php
declare(strict_types=1);

function validate_post_params(mixed $style, mixed $maxChars): array
{
    $style = in_array($style, ['ekspercki', 'lekki'], true) ? $style : 'ekspercki';
    $n = filter_var($maxChars, FILTER_VALIDATE_INT);
    $n = $n === false ? 1000 : max(200, min(3000, $n));
    return [$style, $n];
}

function clean_post_text(string $text): string
{
    $text = trim($text);
    if (preg_match('/^```[a-z]*\s*(.*?)\s*```$/s', $text, $m) === 1) {
        $text = trim($m[1]);
    }
    return $text;
}

function generate_post(ClaudeClient $claude, array $topic, string $style, int $maxChars, string $hints, array $cfg): array
{
    $payload = [
        'max_tokens' => 2000,
        'system' => prompt_post_system($style, $maxChars, $cfg),
        'tools' => [['type' => $cfg['web_fetch_tool'], 'name' => 'web_fetch', 'max_uses' => 3]],
    ];
    $messages = [user_message(prompt_post_user($topic, $hints))];
    for ($i = 0; $i < 4; $i++) {
        $resp = $claude->send($payload + ['messages' => $messages]);
        $messages = append_assistant($messages, $resp['content_raw']);
        if (($resp['stop_reason'] ?? '') !== 'pause_turn') {
            break;
        }
    }
    $text = clean_post_text(ClaudeClient::finalText($resp));

    if (char_count($text) > $maxChars) {
        $messages[] = user_message(prompt_shorten($maxChars, char_count($text)));
        $resp = $claude->send($payload + ['messages' => $messages]);
        $text = clean_post_text(ClaudeClient::finalText($resp));
    }
    $length = char_count($text);
    return ['text' => $text, 'length' => $length, 'over_limit' => $length > $maxChars];
}
