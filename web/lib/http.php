<?php
declare(strict_types=1);

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_guard(): array
{
    if (!is_logged_in()) {
        json_response(['error' => 'Sesja wygasła. Zaloguj się ponownie.'], 401);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response(['error' => 'Metoda niedozwolona.'], 405);
    }
    if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        json_response(['error' => 'Nieprawidłowy token bezpieczeństwa. Odśwież stronę.'], 403);
    }
    // Zwalniamy blokadę sesji, żeby długie wywołanie Claude nie blokowało innych stron.
    session_write_close();
    $body = json_decode((string)file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}
