<?php
declare(strict_types=1);

function db_connect(string $path): PDO
{
    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS searches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL,
            query TEXT,
            days INTEGER NOT NULL,
            note TEXT,
            status TEXT NOT NULL DEFAULT 'running',
            extras_json TEXT
        );
        CREATE TABLE IF NOT EXISTS search_steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            search_id INTEGER NOT NULL REFERENCES searches(id) ON DELETE CASCADE,
            idx INTEGER NOT NULL,
            label TEXT NOT NULL,
            kind TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            state_json TEXT,
            result_json TEXT,
            error TEXT,
            updated_at INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE IF NOT EXISTS topics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            search_id INTEGER NOT NULL REFERENCES searches(id) ON DELETE CASCADE,
            nr INTEGER NOT NULL,
            title TEXT NOT NULL,
            area TEXT NOT NULL,
            status TEXT NOT NULL,
            effective_date TEXT,
            urgency TEXT NOT NULL,
            urgency_code TEXT NOT NULL,
            summary TEXT NOT NULL,
            sources_json TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS login_attempts (
            ip TEXT PRIMARY KEY,
            failed_count INTEGER NOT NULL DEFAULT 0,
            locked_until INTEGER NOT NULL DEFAULT 0
        );
        SQL);
    return $db;
}

function db_one(PDO $db, string $sql, array $params): ?array
{
    $st = $db->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function db_all(PDO $db, string $sql, array $params): array
{
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function search_create(PDO $db, ?string $query, int $days, ?string $note, array $stepDefs, int $now): int
{
    $db->beginTransaction();
    $db->prepare('INSERT INTO searches (created_at, query, days, note) VALUES (?, ?, ?, ?)')
        ->execute([date('Y-m-d H:i', $now), $query, $days, $note]);
    $id = (int)$db->lastInsertId();
    $st = $db->prepare('INSERT INTO search_steps (search_id, idx, label, kind, updated_at) VALUES (?, ?, ?, ?, ?)');
    foreach (array_values($stepDefs) as $i => $s) {
        $st->execute([$id, $i, $s['label'], $s['kind'], $now]);
    }
    $db->commit();
    return $id;
}

function search_get(PDO $db, int $id): ?array
{
    return db_one($db, 'SELECT * FROM searches WHERE id = ?', [$id]);
}

function search_list(PDO $db, int $limit = 30): array
{
    $st = $db->prepare('SELECT s.*, (SELECT COUNT(*) FROM topics t WHERE t.search_id = s.id) AS topic_count
        FROM searches s ORDER BY s.id DESC LIMIT ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function search_set_status(PDO $db, int $id, string $status, ?array $extras = null): void
{
    if ($extras === null) {
        $db->prepare('UPDATE searches SET status = ? WHERE id = ?')->execute([$status, $id]);
        return;
    }
    $db->prepare('UPDATE searches SET status = ?, extras_json = ? WHERE id = ?')
        ->execute([$status, json_encode($extras, JSON_UNESCAPED_UNICODE), $id]);
}

function steps_for(PDO $db, int $searchId): array
{
    return db_all($db, 'SELECT * FROM search_steps WHERE search_id = ? ORDER BY idx', [$searchId]);
}

function step_get(PDO $db, int $id): ?array
{
    return db_one($db, 'SELECT * FROM search_steps WHERE id = ?', [$id]);
}

function step_next(PDO $db, int $searchId): ?array
{
    return db_one($db, "SELECT * FROM search_steps WHERE search_id = ? AND status IN ('pending', 'paused', 'running')
        ORDER BY idx LIMIT 1", [$searchId]);
}

function step_update(PDO $db, int $id, array $fields, int $now): void
{
    $allowed = ['status', 'state_json', 'result_json', 'error'];
    $sets = [];
    $params = [];
    foreach ($fields as $k => $v) {
        if (!in_array($k, $allowed, true)) {
            throw new InvalidArgumentException("Niedozwolone pole kroku: $k");
        }
        $sets[] = "$k = ?";
        $params[] = $v;
    }
    $sets[] = 'updated_at = ?';
    $params[] = $now;
    $params[] = $id;
    $db->prepare('UPDATE search_steps SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
}

/** Atomowo przejmuje krok. Krok `running` uznajemy za porzucony po 2 × timeout + 60 s (wywołanie + ponowienie). */
function step_claim(PDO $db, int $id, int $now, int $timeout): bool
{
    $st = $db->prepare("UPDATE search_steps SET status = 'running', updated_at = ? WHERE id = ?
        AND (status IN ('pending', 'paused') OR (status = 'running' AND updated_at < ?))");
    $st->execute([$now, $id, $now - (2 * $timeout + 60)]);
    return $st->rowCount() === 1;
}

function step_retry(PDO $db, int $stepId, int $now): ?int
{
    $step = step_get($db, $stepId);
    if ($step === null) {
        return null;
    }
    $searchId = (int)$step['search_id'];
    $db->beginTransaction();
    $reset = 'UPDATE search_steps SET status = \'pending\', state_json = NULL, result_json = NULL, error = NULL, updated_at = ? WHERE ';
    $db->prepare($reset . 'id = ?')->execute([$now, $stepId]);
    $db->prepare($reset . "search_id = ? AND kind = 'synthesis'")->execute([$now, $searchId]);
    $db->prepare('DELETE FROM topics WHERE search_id = ?')->execute([$searchId]);
    $db->prepare("UPDATE searches SET status = 'running' WHERE id = ?")->execute([$searchId]);
    $db->commit();
    return $searchId;
}

function steps_summary(PDO $db, int $searchId): array
{
    return array_map(fn($s) => [
        'id' => (int)$s['id'],
        'label' => $s['label'],
        'kind' => $s['kind'],
        'status' => $s['status'],
        'error' => $s['error'],
    ], steps_for($db, $searchId));
}

function topics_replace(PDO $db, int $searchId, array $topics): void
{
    $db->beginTransaction();
    $db->prepare('DELETE FROM topics WHERE search_id = ?')->execute([$searchId]);
    $st = $db->prepare('INSERT INTO topics (search_id, nr, title, area, status, effective_date, urgency, urgency_code, summary, sources_json)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach (array_values($topics) as $i => $t) {
        $st->execute([$searchId, $i + 1, $t['title'], $t['area'], $t['status'], $t['effective_date'],
            $t['urgency'], $t['urgency_code'], $t['summary'], json_encode($t['sources'], JSON_UNESCAPED_UNICODE)]);
    }
    $db->commit();
}

function topic_row(array $row): array
{
    $row['sources'] = json_decode($row['sources_json'], true) ?: [];
    unset($row['sources_json']);
    return $row;
}

function topics_for(PDO $db, int $searchId): array
{
    return array_map('topic_row', db_all($db, 'SELECT * FROM topics WHERE search_id = ? ORDER BY nr', [$searchId]));
}

function topic_get(PDO $db, int $id): ?array
{
    $row = db_one($db, 'SELECT * FROM topics WHERE id = ?', [$id]);
    return $row === null ? null : topic_row($row);
}

function login_is_locked(PDO $db, string $ip, int $now): bool
{
    $row = db_one($db, 'SELECT locked_until FROM login_attempts WHERE ip = ?', [$ip]);
    return $row !== null && (int)$row['locked_until'] > $now;
}

function login_register_failure(PDO $db, string $ip, int $now): void
{
    $db->prepare('INSERT INTO login_attempts (ip, failed_count) VALUES (?, 1)
        ON CONFLICT(ip) DO UPDATE SET failed_count = failed_count + 1')->execute([$ip]);
    $db->prepare('UPDATE login_attempts SET locked_until = ?, failed_count = 0 WHERE ip = ? AND failed_count >= 5')
        ->execute([$now + 900, $ip]);
}

function login_reset(PDO $db, string $ip): void
{
    $db->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
}
