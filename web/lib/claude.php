<?php
declare(strict_types=1);

class ClaudeException extends RuntimeException
{
}

final class ClaudeClient
{
    /** @var callable */
    private $transport;
    /** @var callable */
    private $sleep;

    public function __construct(private string $apiKey, private string $model, callable $transport, ?callable $sleep = null)
    {
        $this->transport = $transport;
        $this->sleep = $sleep ?? 'sleep';
    }

    public function send(array $payload): array
    {
        if ($this->apiKey === '') {
            throw new ClaudeException('Brak klucza API. Sprawdź klucz API w config.php.');
        }
        $payload['model'] = $this->model;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        for ($attempt = 1; ; $attempt++) {
            $res = ($this->transport)($body, $this->apiKey);
            $status = (int)$res['status'];
            if ($status === 200) {
                $data = json_decode($res['body'], true);
                $raw = json_decode($res['body']);
                if (!is_array($data) || !($raw instanceof stdClass)) {
                    throw new ClaudeException('Nieczytelna odpowiedź API Claude.');
                }
                $data['content_raw'] = $raw->content ?? [];
                return $data;
            }
            if ($status === 401) {
                throw new ClaudeException('Nieprawidłowy klucz API. Sprawdź klucz API w config.php.');
            }
            if (($status === 429 || $status >= 500) && $attempt === 1) {
                ($this->sleep)(self::retryDelay($res['headers'] ?? []));
                continue;
            }
            throw new ClaudeException(self::errorMessage($status, (string)($res['body'] ?? '')));
        }
    }

    public static function retryDelay(array $headers): int
    {
        $v = $headers['retry-after'] ?? null;
        return is_numeric($v) ? max(1, min(30, (int)$v)) : 5;
    }

    public static function errorMessage(int $status, string $body): string
    {
        $data = json_decode($body, true);
        $msg = is_array($data) ? ($data['error']['message'] ?? null) : null;
        return match (true) {
            $status === 0 => 'Brak odpowiedzi z API Claude (przekroczony czas lub błąd sieci). Spróbuj ponownie.',
            $status === 429 => 'Przekroczono limit zapytań do API Claude. Spróbuj za chwilę.',
            $status >= 500 => 'API Claude jest przeciążone lub niedostępne. Spróbuj za chwilę.',
            default => 'Błąd API (' . $status . ')' . (is_string($msg) && $msg !== '' ? ': ' . $msg : '.'),
        };
    }

    /** Tekst z bloków `text` po ostatnim bloku innego typu (np. wyniku narzędzia). */
    public static function finalText(array $response): string
    {
        $parts = [];
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $parts[] = (string)$block['text'];
            } else {
                $parts = [];
            }
        }
        return trim(implode('', $parts));
    }
}

function curl_transport(int $timeout): callable
{
    return function (string $body, string $apiKey) use ($timeout): array {
        $headers = [];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$headers): int {
                $p = explode(':', $line, 2);
                if (count($p) === 2) {
                    $headers[strtolower(trim($p[0]))] = trim($p[1]);
                }
                return strlen($line);
            },
        ]);
        $resp = curl_exec($ch);
        $status = $resp === false ? 0 : (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($resp === false) {
            app_log('cURL: ' . curl_error($ch));
        }
        return ['status' => $status, 'headers' => $headers, 'body' => $resp === false ? '' : (string)$resp];
    };
}

function user_message(string $text): stdClass
{
    return (object)['role' => 'user', 'content' => $text];
}

/** Dokleja treść asystenta; kolejne tury asystenta (po pause_turn) scala w jedną wiadomość. */
function append_assistant(array $messages, array $content): array
{
    $last = $messages === [] ? null : $messages[count($messages) - 1];
    if ($last !== null && $last->role === 'assistant' && is_array($last->content)) {
        $last->content = array_merge($last->content, $content);
    } else {
        $messages[] = (object)['role' => 'assistant', 'content' => $content];
    }
    return $messages;
}
