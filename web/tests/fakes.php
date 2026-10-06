<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/claude.php';

function text_block(string $t): array
{
    return ['type' => 'text', 'text' => $t];
}

function fake_response(array $content, string $stopReason = 'end_turn'): array
{
    return ['status' => 200, 'headers' => [], 'body' => json_encode(
        ['id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'content' => $content, 'stop_reason' => $stopReason],
        JSON_UNESCAPED_UNICODE
    )];
}

/** $responses: lista wyników transportu (np. fake_response(...)). Każde żądanie trafia do $requests jako tablica. */
function fake_claude(array $responses, array &$requests): ClaudeClient
{
    $transport = function (string $body, string $apiKey) use (&$responses, &$requests): array {
        $requests[] = ['body' => $body, 'payload' => json_decode($body, true)];
        if ($responses === []) {
            throw new RuntimeException('fake_claude: brak kolejnej odpowiedzi');
        }
        return array_shift($responses);
    };
    return new ClaudeClient('test-key', 'claude-test', $transport, function (int $s): void {});
}
