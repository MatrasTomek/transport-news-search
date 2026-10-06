<?php
declare(strict_types=1);

const IMAGE_FORMATS = ['poziomy' => [1200, 630], 'kwadrat' => [1080, 1080]];

function generate_image(ClaudeClient $claude, array $topic, string $post, string $format, array $cfg, ?string $logoDataUri): array
{
    [$w, $h] = IMAGE_FORMATS[$format] ?? IMAGE_FORMATS['poziomy'];
    $messages = [user_message(prompt_image_user($topic, $post, $w, $h, $cfg))];
    for ($attempt = 1; $attempt <= 2; $attempt++) {
        $resp = $claude->send(['max_tokens' => 8000, 'system' => prompt_image_system(), 'messages' => $messages]);
        $svg = extract_svg(ClaudeClient::finalText($resp));
        if ($svg === null) {
            $error = 'brak kodu SVG w odpowiedzi';
        } else {
            try {
                return ['svg' => sanitize_svg($svg, $logoDataUri, $w, $h), 'width' => $w, 'height' => $h];
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
        app_log("Grafika, próba $attempt: $error");
        $messages = append_assistant($messages, $resp['content_raw']);
        $messages[] = user_message("Poprzednia odpowiedź była niepoprawna ({$error}). Zwróć wyłącznie poprawny, kompletny dokument SVG.");
    }
    throw new RuntimeException('Nie udało się wygenerować grafiki. Spróbuj ponownie.');
}
