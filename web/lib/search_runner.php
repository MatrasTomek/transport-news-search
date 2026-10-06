<?php
declare(strict_types=1);

function build_steps(?string $query): array
{
    $steps = $query === null
        ? array_map(fn(string $a) => ['label' => $a, 'kind' => 'area'], array_keys(AREAS))
        : [['label' => $query, 'kind' => 'query']];
    $steps[] = ['label' => 'Wybór tematów', 'kind' => 'synthesis'];
    return $steps;
}

function research_tools(array $cfg): array
{
    return [
        ['type' => $cfg['web_search_tool'], 'name' => 'web_search', 'max_uses' => 5],
        ['type' => $cfg['web_fetch_tool'], 'name' => 'web_fetch', 'max_uses' => 4],
    ];
}

/** Krok w toku w innym żądaniu. Po timeout + 60 s uznajemy żądanie za przerwane. */
function step_is_busy(array $step, int $now, int $timeout): bool
{
    return $step['status'] === 'running' && (int)$step['updated_at'] > $now - ($timeout + 60);
}

function run_step(PDO $db, ClaudeClient $claude, array $search, array $step, array $cfg, DateTimeImmutable $today, string $sources, int $now): array
{
    $stepId = (int)$step['id'];
    step_update($db, $stepId, ['status' => 'running'], $now);
    try {
        return $step['kind'] === 'synthesis'
            ? run_synthesis($db, $claude, $search, $step, $cfg, $today, $now)
            : run_research($db, $claude, $search, $step, $cfg, $today, $sources, $now);
    } catch (ClaudeException $e) {
        step_update($db, $stepId, ['status' => 'error', 'error' => $e->getMessage()], $now);
        if ($step['kind'] === 'synthesis') {
            search_set_status($db, (int)$search['id'], 'error');
        }
        return ['state' => 'error', 'message' => $e->getMessage()];
    }
}

/** Wspólna obsługa odpowiedzi z JSON: walidacja, jedna prośba o poprawkę, potem błąd. */
function handle_json_answer(PDO $db, array $step, array $messages, bool $fixAttempted, array $resp, callable $validate, int $now): array
{
    try {
        $data = extract_json(ClaudeClient::finalText($resp));
        if ($data === null) {
            throw new InvalidArgumentException('brak obiektu JSON');
        }
        return ['ok' => true, 'value' => $validate($data)];
    } catch (InvalidArgumentException $e) {
        if ($fixAttempted) {
            app_log("Krok {$step['id']} ({$step['label']}): niepoprawny JSON: " . ClaudeClient::finalText($resp));
            $msg = 'Claude zwrócił dane w niepoprawnym formacie.';
            step_update($db, (int)$step['id'], ['status' => 'error', 'error' => $msg, 'state_json' => null], $now);
            return ['ok' => false, 'result' => ['state' => 'error', 'message' => $msg]];
        }
        $messages[] = user_message(prompt_fix_json($e->getMessage()));
        step_update($db, (int)$step['id'], ['status' => 'paused', 'state_json' => json_encode(
            ['messages' => $messages, 'fix_attempted' => true], JSON_UNESCAPED_UNICODE)], $now);
        return ['ok' => false, 'result' => ['state' => 'paused', 'message' => null]];
    }
}

function load_state(array $step): array
{
    if ($step['state_json'] === null || $step['state_json'] === '') {
        return [null, false];
    }
    $state = json_decode($step['state_json']);
    return [$state->messages, (bool)($state->fix_attempted ?? false)];
}

function run_research(PDO $db, ClaudeClient $claude, array $search, array $step, array $cfg, DateTimeImmutable $today, string $sources, int $now): array
{
    $todayIso = $today->format('Y-m-d');
    $days = (int)$search['days'];
    [$messages, $fixAttempted] = load_state($step);
    $messages ??= [user_message(prompt_research_user($step['kind'], $step['label'], $todayIso, $days))];

    $resp = $claude->send([
        'max_tokens' => 8000,
        'system' => prompt_research_system($cfg, $todayIso, $days, $sources),
        'messages' => $messages,
        'tools' => research_tools($cfg),
    ]);
    $messages = append_assistant($messages, $resp['content_raw']);

    if (($resp['stop_reason'] ?? '') === 'pause_turn') {
        step_update($db, (int)$step['id'], ['status' => 'paused', 'state_json' => json_encode(
            ['messages' => $messages, 'fix_attempted' => $fixAttempted], JSON_UNESCAPED_UNICODE)], $now);
        return ['state' => 'paused', 'message' => null];
    }

    $answer = handle_json_answer($db, $step, $messages, $fixAttempted, $resp, 'validate_candidates', $now);
    if (!$answer['ok']) {
        return $answer['result'];
    }
    step_update($db, (int)$step['id'], ['status' => 'done', 'state_json' => null, 'error' => null,
        'result_json' => json_encode($answer['value'], JSON_UNESCAPED_UNICODE)], $now);
    return ['state' => 'done', 'message' => null];
}

function run_synthesis(PDO $db, ClaudeClient $claude, array $search, array $step, array $cfg, DateTimeImmutable $today, int $now): array
{
    $searchId = (int)$search['id'];
    $candidates = [];
    $watch = [];
    $failed = [];
    $searched = [];
    foreach (steps_for($db, $searchId) as $s) {
        if ($s['kind'] === 'synthesis') {
            continue;
        }
        if ($s['status'] === 'done') {
            $searched[] = $s['label'];
            $r = json_decode((string)$s['result_json'], true) ?: [];
            $candidates = array_merge($candidates, $r['candidates'] ?? []);
            $watch = array_merge($watch, $r['watch'] ?? []);
        } else {
            $failed[] = $s['label'];
        }
    }
    $failedNotes = array_map(fn($l) => "$l (błąd wyszukiwania)", $failed);

    if ($searched === []) {
        $msg = 'Żaden krok wyszukiwania się nie udał. Ponów kroki z błędem.';
        step_update($db, (int)$step['id'], ['status' => 'error', 'error' => $msg], $now);
        search_set_status($db, $searchId, 'error');
        return ['state' => 'error', 'message' => $msg];
    }

    if ($candidates === [] && $watch === []) {
        $result = ['topics' => [], 'watch' => [], 'no_news' => array_map(fn($l) => "$l: brak istotnych zmian w okresie.", $searched)];
    } else {
        [$messages, $fixAttempted] = load_state($step);
        $messages ??= [user_message(prompt_synthesis_user($candidates, $watch, $searched))];
        $resp = $claude->send(['max_tokens' => 8000, 'system' => prompt_synthesis_system($cfg), 'messages' => $messages]);
        $messages = append_assistant($messages, $resp['content_raw']);
        $answer = handle_json_answer($db, $step, $messages, $fixAttempted, $resp, 'validate_synthesis', $now);
        if (!$answer['ok']) {
            if ($answer['result']['state'] === 'error') {
                search_set_status($db, $searchId, 'error');
            }
            return $answer['result'];
        }
        $result = $answer['value'];
    }

    $topics = array_map(function (array $t) use ($today) {
        $u = urgency($t['effective_date'], $t['status'], $today);
        return $t + ['urgency' => $u['label'], 'urgency_code' => $u['code']];
    }, $result['topics']);
    topics_replace($db, $searchId, $topics);
    step_update($db, (int)$step['id'], ['status' => 'done', 'state_json' => null, 'error' => null], $now);
    search_set_status($db, $searchId, 'done', [
        'watch' => $result['watch'],
        'no_news' => array_merge($result['no_news'], $failedNotes),
    ]);
    return ['state' => 'done', 'message' => null];
}
